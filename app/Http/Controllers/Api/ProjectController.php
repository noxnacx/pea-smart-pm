<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    // เพิ่มฟังก์ชันนี้ลงไป (หรือแก้ของเดิมถ้ามี)
    public function index(Request $request)
    {
        $query = Project::with('manager'); // ดึงข้อมูลผู้จัดการมาด้วย

        // 1. ระบบค้นหา (ถ้ามีการส่งคำค้นมา)
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        // 2. ระบบกรองสถานะ
        if ($request->has('status') && $request->status != 'all') {
            $query->where('status', $request->status);
        }

        // เรียงลำดับล่าสุดก่อน และแบ่งหน้า (Pagination) ทีละ 10 รายการ
        $projects = $query->orderBy('updated_at', 'desc')->paginate(10);

        return response()->json($projects);
    }

    public function show($id)
    {
        $project = \App\Models\Project::with(['manager', 'tasks.user', 'members'])->findOrFail($id);

        // ดึงค่า Risk Analysis จาก Model (ที่เราเพิ่งเขียนไป)
        $risk = $project->risk_analysis;

        $paidAmount = $project->payments()->sum('amount');
        $budgetSummary = [
            'contract_amount' => $project->contract_amount,
            'paid_amount' => $paidAmount,
            'remaining_amount' => $project->contract_amount - $paidAmount,
            'paid_percent' => $project->contract_amount > 0 ? ($paidAmount / $project->contract_amount) * 100 : 0,
        ];

        return response()->json([
            'project' => $project,
            'budget_summary' => $budgetSummary,
            'risk_analysis' => $risk, // <--- ส่งไปหน้าบ้าน
            'last_update' => $project->progressHistory()->with('user')->latest('date_logged')->first()
        ]);
    }


    // เพิ่มฟังก์ชันนี้สำหรับ "สร้างโครงการใหม่"
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'code' => 'required|string|unique:projects,code', // ให้กรอกรหัสเองหรือเจนตามรูปแบบ
            'contract_amount' => 'required|numeric',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'manager_id' => 'required|exists:users,id',
            'program_id' => 'required|exists:programs,id',
            'status' => 'required|in:draft,ongoing,late,completed,cancel',
        ]);

        $project = Project::create($validated);

        return response()->json(['message' => 'สร้างโครงการสำเร็จ', 'project' => $project], 201);
    }


    public function update(Request $request, $id)
    {
        $project = Project::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string',
            'status' => 'required|in:draft,ongoing,late,completed,cancel',
            'progress_actual' => 'required|numeric|min:0|max:100',
            'contract_amount' => 'required|numeric',
        ]);

        $project->update($validated);

        return response()->json(['message' => 'อัปเดตโครงการสำเร็จ', 'project' => $project]);
    }

    public function destroy($id)
    {
        $project = \App\Models\Project::findOrFail($id);

        // ลบข้อมูลที่เกี่ยวข้องให้หมด (Clean up)
        // 1. ลบงานย่อย (Tasks)
        $project->tasks()->delete();

        // 2. ลบประวัติความก้าวหน้า (Progress History)
        $project->progressHistory()->delete();

        // 3. ลบรายการงบประมาณ (Budget Items)
        $project->budgetItems()->delete();

        // สุดท้าย ลบตัวโครงการ
        $project->delete();

        return response()->json(['message' => 'ลบโครงการเรียบร้อยแล้ว']);
    }

    // อัปเดตความก้าวหน้าประจำเดือน (S-Curve)
    public function updateProgress(Request $request, $id)
    {
        $request->validate([
            'date_logged' => 'required|date',
            'actual_percent' => 'required|numeric|min:0|max:100',
        ]);

        $project = \App\Models\Project::findOrFail($id);

        $history = \App\Models\ProjectProgressHistory::updateOrCreate(
            [
                'project_id' => $id,
                'date_logged' => $request->date_logged
            ],
            [
                'actual_percent' => $request->actual_percent,
                'user_id' => $request->user()->id // <--- บันทึกคนอัปเดตตรงนี้
            ]
        );

        $project->update(['progress_actual' => $request->actual_percent]);

        return response()->json(['message' => 'Updated', 'history' => $history]);
    }

    public function getOptions()
    {
        return response()->json([
            'managers' => \App\Models\User::all(['id', 'name']),
            'programs' => \App\Models\Program::all(['id', 'name']),
        ]);
    }

    // --- เพิ่มต่อท้ายใน Class ProjectController ---

    // 1. ค้นหา User เพื่อเชิญเข้าทีม (พิมพ์แค่บางคำก็เจอ)
    public function searchUsers(Request $request)
    {
        $search = $request->get('q');
        return \App\Models\User::where('name', 'like', "%{$search}%")
            ->orWhere('email', 'like', "%{$search}%")
            ->limit(10) // เอามาแค่ 10 คนพอ ไม่ต้องเยอะ
            ->get();
    }

    // 2. เพิ่มสมาชิกเข้าโครงการ
    public function addMember(Request $request, $id)
    {
        $project = Project::findOrFail($id);

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'required|in:member,viewer' // กำหนดสิทธิ์ได้ (อนาคตใช้ได้)
        ]);

        // กันเหนียว: เช็คว่ามีอยู่แล้วหรือยัง? ถ้ายังค่อยเพิ่ม
        if (!$project->members()->where('user_id', $request->user_id)->exists()) {
            $project->members()->attach($request->user_id, ['role' => $request->role]);
        }

        return response()->json(['message' => 'เพิ่มสมาชิกเรียบร้อย']);
    }

    // 3. ลบสมาชิกออกจากโครงการ
    public function removeMember($id, $userId)
    {
        $project = Project::findOrFail($id);
        $project->members()->detach($userId);

        return response()->json(['message' => 'ลบสมาชิกเรียบร้อย']);
    }
}
