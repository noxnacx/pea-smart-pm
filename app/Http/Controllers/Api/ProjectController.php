<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\Request;
use App\Models\Task;
use App\Models\TaskProgressLog;
use Illuminate\Support\Facades\DB;

class ProjectController extends Controller
{
    // แสดงรายการโครงการ (พร้อมระบบกรองสิทธิ์)
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Project::with('manager');

        // 1. กรองตามแผนงาน (ถ้ามีการส่ง program_id มา)
        if ($request->has('program_id')) {
            $query->where('program_id', $request->program_id);
        }

        // 2. กรอง "โครงการของฉัน" (My Projects)
        // ถ้าส่ง parameter ?scope=my_projects มา หรือ ถ้าไม่ใช่ Admin/Manager (ระบบบังคับดูได้แค่ของตัวเอง)
        $forceMyProjects = $request->get('scope') === 'my_projects';

        if ($forceMyProjects || ($user->role !== 'admin' && $user->role !== 'program_manager')) {
            $query->where(function($q) use ($user) {
                $q->where('manager_id', $user->id)
                  ->orWhereHas('members', function($m) use ($user) {
                      $m->where('user_id', $user->id);
                  });
            });
        }

        // 3. ระบบค้นหา (Search)
        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        // 4. กรองสถานะ
        if ($request->has('status') && $request->status != 'all') {
            $query->where('status', $request->status);
        }

        // เรียงลำดับและแบ่งหน้า
        $projects = $query->orderBy('updated_at', 'desc')->paginate(10);

        return response()->json($projects);
    }

    public function show($id)
    {
        $user = auth()->user();
        $project = \App\Models\Project::with(['manager', 'tasks.user', 'members','tasks.users'])->findOrFail($id);

        // เช็คสิทธิ์การแก้ไข (เผื่อเอาไปใช้ซ่อนปุ่มใน Frontend)
        $canEdit = $user->role === 'admin' || $project->manager_id === $user->id;

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
            'risk_analysis' => $risk,
            'last_update' => $project->progressHistory()->with('user')->latest('date_logged')->first(),
            'can_edit' => $canEdit // ส่งกลับไปบอกหน้าบ้าน
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'code' => 'required|string|unique:projects,code',
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

        // ควรเช็คสิทธิ์ก่อนอัปเดตด้วย (Optional but recommended)
        if ($request->user()->role !== 'admin' && $project->manager_id !== $request->user()->id) {
             return response()->json(['message' => 'ไม่มีสิทธิ์แก้ไข'], 403);
        }

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

        // เช็คสิทธิ์ (เฉพาะ Admin หรือ Manager)
        if (request()->user()->role !== 'admin' && $project->manager_id !== request()->user()->id) {
             return response()->json(['message' => 'ไม่มีสิทธิ์ลบ'], 403);
        }

        $project->tasks()->delete();
        $project->progressHistory()->delete();
        $project->budgetItems()->delete();
        $project->delete();

        return response()->json(['message' => 'ลบโครงการเรียบร้อยแล้ว']);
    }

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
                'user_id' => $request->user()->id
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

    // --- Team Management ---

    public function searchUsers(Request $request)
    {
        $search = $request->get('q');
        return \App\Models\User::where('name', 'like', "%{$search}%")
            ->orWhere('email', 'like', "%{$search}%")
            ->limit(10)
            ->get();
    }

    public function addMember(Request $request, $id)
    {
        $project = Project::findOrFail($id);
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'required|in:member,viewer'
        ]);

        if (!$project->members()->where('user_id', $request->user_id)->exists()) {
            $project->members()->attach($request->user_id, ['role' => $request->role]);
        }
        return response()->json(['message' => 'เพิ่มสมาชิกเรียบร้อย']);
    }

    public function removeMember($id, $userId)
    {
        $project = Project::findOrFail($id);
        $project->members()->detach($userId);
        return response()->json(['message' => 'ลบสมาชิกเรียบร้อย']);
    }
}
