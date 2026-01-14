<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Task;
use App\Models\TaskProgressLog;
use Illuminate\Support\Facades\DB;

class TaskController extends Controller
{
    // 1. สร้างงานใหม่
    public function store(Request $request)
    {
        $validated = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'name' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'weight' => 'required|numeric|min:0|max:100',
            'progress' => 'nullable|numeric|min:0|max:100',
            'predecessor_id' => 'nullable|exists:tasks,id',
            'user_ids' => 'nullable|array',
            'user_ids.*' => 'exists:users,id'
        ]);

        if (!empty($validated['predecessor_id'])) {
            $predecessor = Task::find($validated['predecessor_id']);
            if ($predecessor && strtotime($validated['start_date']) < strtotime($predecessor->end_date)) {
                return response()->json(['message' => "ติดเงื่อนไขงานก่อนหน้า (${predecessor->name})"], 422);
            }
        }

        DB::transaction(function () use ($validated, $request) {
            // สร้าง Task และเก็บ user_id (คนสร้าง)
            $task = Task::create(array_merge($validated, [
                'user_id' => $request->user()->id
            ]));

            // บันทึกผู้รับผิดชอบหลายคน (ถ้ามี)
            if (!empty($validated['user_ids'])) {
                $task->users()->sync($validated['user_ids']);
            }

            TaskProgressLog::create([
                'task_id' => $task->id,
                'progress_percent' => $task->progress ?? 0,
                'note' => 'สร้างงานใหม่',
                'user_id' => $request->user()->id,
            ]);
        });

        return response()->json(['message' => 'เพิ่มงานสำเร็จ'], 201);
    }

    // 2. แก้ไขงาน
    public function update(Request $request, $id)
    {
        $task = Task::findOrFail($id);
        $oldProgress = $task->progress;

        $validated = $request->validate([
            'name' => 'sometimes|required|string',
            'start_date' => 'sometimes|required|date',
            'end_date' => 'sometimes|required|date|after_or_equal:start_date',
            'weight' => 'sometimes|required|numeric|min:0|max:100',
            'progress' => 'nullable|numeric|min:0|max:100',
            'predecessor_id' => 'nullable|exists:tasks,id',
            'user_ids' => 'nullable|array',
            'user_ids.*' => 'exists:users,id'
        ]);

        // Dependency Check... (ละไว้เหมือนเดิม)

        DB::transaction(function () use ($task, $validated, $request, $oldProgress) {
            $task->update($validated);

            if (isset($validated['user_ids'])) {
                $task->users()->sync($validated['user_ids']);
            }

            if (isset($validated['progress']) && $validated['progress'] != $oldProgress) {
                TaskProgressLog::create([
                    'task_id' => $task->id,
                    'progress_percent' => $validated['progress'],
                    'note' => 'อัปเดตความคืบหน้า',
                    'user_id' => $request->user()->id,
                ]);
            }
        });

        return response()->json(['message' => 'แก้ไขงานสำเร็จ']);
    }

    // 3. ลบงาน
    public function destroy($id)
    {
        $task = Task::findOrFail($id);
        $task->delete();
        return response()->json(['message' => 'ลบงานสำเร็จ']);
    }

    // 4. ดึง Log
    public function getLogs($id)
    {
        $task = Task::with(['progressLogs.user'])->findOrFail($id);
        return response()->json($task->progressLogs);
    }

    // ✅ 5. ดึงข้อมูลปฏิทิน (แก้ไขแล้ว)
    public function calendarEvents(Request $request)
    {
        $user = $request->user();

        // ดึงงานที่:
        // 1. เป็นผู้รับผิดชอบ (ในตาราง pivot task_user)
        // 2. หรือ เป็นคนสร้าง/เจ้าของงาน (user_id ในตาราง tasks)
        // 3. หรือ เป็น PM ของโครงการ
        $tasks = Task::with(['project', 'users'])
            ->where(function($query) use ($user) {
                $query->whereHas('users', function($q) use ($user) {
                    $q->where('users.id', $user->id);
                })
                ->orWhere('user_id', $user->id)
                ->orWhereHas('project', function($q) use ($user) {
                    $q->where('manager_id', $user->id);
                });
            })
            ->get();

        $events = $tasks->map(function($task) {
            // กำหนดสี
            $color = '#3B82F6'; // ฟ้า
            if ($task->progress == 100) $color = '#10B981'; // เขียว
            else if (strtotime($task->end_date) < now()->timestamp) $color = '#EF4444'; // แดง

            // ป้องกัน Error กรณี project ถูกลบไปแล้ว
            $projectCode = $task->project ? $task->project->code : '-';
            $projectName = $task->project ? $task->project->name : 'ไม่ระบุโครงการ';

            return [
                'id' => $task->id,
                'title' => $task->name . " ($projectCode)",
                'start' => $task->start_date,
                'end' => date('Y-m-d', strtotime($task->end_date . ' +1 day')),
                'color' => $color,
                'allDay' => true,
                'extendedProps' => [
                    'project_name' => $projectName,
                    'project_id' => $task->project_id,
                    'progress' => $task->progress
                ]
            ];
        });

        return response()->json($events);
    }
}
