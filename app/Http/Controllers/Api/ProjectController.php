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
        $project = \App\Models\Project::with(['manager', 'tasks.user'])->findOrFail($id);

        // ดึงประวัติล่าสุด พร้อมชื่อคน
        $lastUpdate = \App\Models\ProjectProgressHistory::where('project_id', $id)
            ->with('user')
            ->orderBy('date_logged', 'desc')
            ->first();

        // (โค้ดคำนวณเงินคงเดิม...)
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
            'last_update' => $lastUpdate // <--- ส่งเพิ่มไป
        ]);
    }

    // ... (ฟังก์ชันอื่นเดิม) ...

    // เพิ่มฟังก์ชันนี้สำหรับ "สร้างโครงการใหม่"
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'contract_amount' => 'required|numeric',
            'status' => 'required|in:draft,ongoing,late,completed,cancel',
            'progress_actual' => 'nullable|numeric|min:0|max:100',
        ]);

        // เนื่องจากเรายังไม่ได้ทำระบบ Login ขอ Hardcode ค่าเหล่านี้ไปก่อนครับ
        $validated['code'] = 'PEA-' . date('y') . '-' . rand(100, 999); // สุ่มรหัสโครงการ
        $validated['program_id'] = 1; // ผูกกับแผนงานแรก
        $validated['manager_id'] = 1; // ให้ Admin เป็นคนดูแล
        $validated['start_date'] = now();
        $validated['end_date'] = now()->addYear();

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
}
