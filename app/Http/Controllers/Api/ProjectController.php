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
    // แสดงรายการโครงการ
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Project::with('manager');

        // 1. กรองตามแผนงาน
        if ($request->has('program_id')) {
            $query->where('program_id', $request->program_id);
        }

        // 2. กรอง "โครงการของฉัน"
        if ($request->get('scope') === 'my_projects') {
            $query->where(function($q) use ($user) {
                $q->where('manager_id', $user->id)
                  ->orWhereHas('members', function($m) use ($user) {
                      $m->where('user_id', $user->id);
                  });
            });
        }

        // 3. ✅ กรองเฉพาะโครงการหลัก (Main Projects) ไม่เอา Sub-projects (ถ้าไม่ได้ระบุ)
        // หรือถ้าอยากเห็นทั้งหมดก็เอาบรรทัดนี้ออกได้ครับ
        // if (!$request->has('show_all')) {
        //    $query->whereNull('parent_id');
        // }

        // 4. ระบบค้นหา
        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        // 5. กรองสถานะ
        if ($request->has('status') && $request->status != 'all') {
            $query->where('status', $request->status);
        }

        $projects = $query->orderBy('updated_at', 'desc')->paginate(10);

        return response()->json($projects);
    }

    public function show($id)
    {
        $user = auth()->user();
        // ✅ เพิ่ม children (โครงการย่อย) และ parent (โครงการแม่)
        $project = Project::with([
            'manager',
            'tasks.user',
            'members',
            'tasks.users',
            'tasks.progressLogs.user',
            'tasks.attachments',
            'children.manager', // โหลดโครงการลูก
            'parent'            // โหลดโครงการแม่
        ])->findOrFail($id);

        $canEdit = $user->role === 'admin' || $user->role === 'program_manager' || $project->manager_id === $user->id;

        // คำนวณ Risk (เหมือนเดิม)
        $risk = $project->risk_analysis;

        // Budget (เอาออกตามที่คุณบอก หรือถ้ามี Model อยู่ก็ใส่ไว้ได้)
        // $paidAmount = ...

        return response()->json([
            'project' => $project,
            'risk_analysis' => $risk,
            'last_update' => $project->progressHistory()->with('user')->latest('date_logged')->first(),
            'can_edit' => $canEdit
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
            // ✅ เพิ่ม 2 field นี้
            'parent_id' => 'nullable|exists:projects,id',      // สร้างเป็น Sub-project
            'task_parent_id' => 'nullable|exists:tasks,id',   // สร้างจาก Task
        ]);

        $project = Project::create($validated);

        // ถ้าสร้างมาจาก Task อาจจะมีการ Auto Update สถานะ Task แม่ด้วย (Option)

        return response()->json(['message' => 'สร้างโครงการสำเร็จ', 'project' => $project], 201);
    }

    public function update(Request $request, $id)
    {
        $project = Project::findOrFail($id);
        $user = $request->user();

        if ($user->role !== 'admin' && $user->role !== 'program_manager' && $project->manager_id !== $user->id) {
             return response()->json(['message' => 'ไม่มีสิทธิ์แก้ไข'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string',
            'status' => 'required|in:draft,ongoing,late,completed,cancel',
            'progress_actual' => 'required|numeric|min:0|max:100',
            'contract_amount' => 'required|numeric',
            'parent_id' => 'nullable|exists:projects,id',
        ]);

        // ป้องกัน Circular
        if ($request->parent_id == $id) {
             return response()->json(['message' => 'ไม่สามารถเลือกตัวเองเป็นโครงการแม่ได้'], 422);
        }

        $project->update($validated);
        return response()->json(['message' => 'อัปเดตโครงการสำเร็จ', 'project' => $project]);
    }

    public function destroy($id)
    {
        $project = Project::findOrFail($id);
        $user = request()->user();

        if ($user->role !== 'admin' && $user->role !== 'program_manager' && $project->manager_id !== $user->id) {
             return response()->json(['message' => 'ไม่มีสิทธิ์ลบ'], 403);
        }

        $project->tasks()->delete();
        $project->progressHistory()->delete();
        $project->budgetItems()->delete();
        $project->delete();

        return response()->json(['message' => 'ลบโครงการเรียบร้อยแล้ว']);
    }

    // ... (Functions อื่นๆ: updateProgress, getOptions, searchUsers, addMember, removeMember คงเดิม) ...
    public function updateProgress(Request $request, $id)
    {
        $request->validate([
            'date_logged' => 'required|date',
            'actual_percent' => 'required|numeric|min:0|max:100',
        ]);

        $project = Project::findOrFail($id);

        $history = \App\Models\ProjectProgressHistory::updateOrCreate(
            ['project_id' => $id, 'date_logged' => $request->date_logged],
            ['actual_percent' => $request->actual_percent, 'user_id' => $request->user()->id]
        );

        $project->update(['progress_actual' => $request->actual_percent]);
        return response()->json(['message' => 'Updated', 'history' => $history]);
    }

    public function getOptions()
    {
        return response()->json([
            'managers' => \App\Models\User::all(['id', 'name']),
            'programs' => \App\Models\Program::all(['id', 'name']),
            'strategies' => \App\Models\Strategy::all(['id', 'name']), // ✅ เพิ่ม Strategies ส่งไปหน้าบ้าน
        ]);
    }

    public function searchUsers(Request $request)
    {
        $search = $request->get('q');
        return \App\Models\User::where('name', 'like', "%{$search}%")
            ->orWhere('email', 'like', "%{$search}%")
            ->limit(10)
            ->get();
    }

    // (ใส่ addMember, removeMember ตัวเดิมที่คุณมี Audit Log ได้เลยครับ)
    public function addMember(Request $request, $id)
    {
        $project = Project::findOrFail($id);
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'required|in:member,viewer'
        ]);

        if (!$project->members()->where('user_id', $request->user_id)->exists()) {
            $project->members()->attach($request->user_id, ['role' => $request->role]);

            $targetUser = \App\Models\User::find($request->user_id);
            activity()
                ->performedOn($project)
                ->causedBy($request->user())
                ->withProperties(['target_user' => $targetUser->name, 'role' => $request->role])
                ->log("เพิ่มสมาชิกทีม: {$targetUser->name} ({$request->role})");
        }
        return response()->json(['message' => 'เพิ่มสมาชิกเรียบร้อย']);
    }

    public function removeMember($id, $userId)
    {
        $project = Project::findOrFail($id);
        $targetUser = \App\Models\User::find($userId);

        $project->members()->detach($userId);

        if($targetUser) {
            activity()
                ->performedOn($project)
                ->causedBy(request()->user())
                ->withProperties(['target_user' => $targetUser->name])
                ->log("ลบสมาชิกทีม: {$targetUser->name}");
        }
        return response()->json(['message' => 'ลบสมาชิกเรียบร้อย']);
    }
}
