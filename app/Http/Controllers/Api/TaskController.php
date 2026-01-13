<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Task;
use App\Models\TaskProgressLog; // <--- 1. ต้องบรรทัดนี้ ไม่งั้น Error
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
        ]);

        // ใส่ User ID คนสร้าง
        $validated['user_id'] = $request->user()->id;

        $task = Task::create($validated);

        // *** จุดสำคัญ 1: สร้างงานปุ๊บ บันทึกประวัติแรกปั๊บ ***
        TaskProgressLog::create([
            'task_id' => $task->id,
            'progress_percent' => $task->progress ?? 0,
            'note' => 'สร้างงานใหม่',
            'user_id' => $request->user()->id,
        ]);

        return response()->json(['message' => 'เพิ่มงานสำเร็จ', 'task' => $task], 201);
    }

    // 2. แก้ไขงาน (พร้อมบันทึกประวัติอัตโนมัติ)
    public function update(Request $request, $id)
    {
        $task = Task::findOrFail($id);
        $oldProgress = $task->progress; // จำค่าเดิมไว้ก่อนแก้

        $validated = $request->validate([
            'name' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'weight' => 'required|numeric|min:0|max:100',
            'progress' => 'nullable|numeric|min:0|max:100',
        ]);

        DB::transaction(function () use ($task, $validated, $request, $oldProgress) {
            // อัปเดตข้อมูลงานหลัก
            $task->update(array_merge($validated, [
                'user_id' => $request->user()->id
            ]));

            // *** จุดสำคัญ 2: เช็คว่าเปอร์เซ็นต์เปลี่ยนไหม? ถ้าเปลี่ยน ให้บันทึกประวัติ ***
            // ถ้าค่าใหม่ ($validated['progress']) ไม่เท่ากับ ค่าเก่า ($oldProgress)
            if (isset($validated['progress']) && $validated['progress'] != $oldProgress) {
                TaskProgressLog::create([
                    'task_id' => $task->id,
                    'progress_percent' => $validated['progress'],
                    'note' => 'อัปเดตความคืบหน้าจาก ' . $oldProgress . '% เป็น ' . $validated['progress'] . '%',
                    'user_id' => $request->user()->id,
                ]);
            }
        });

        return response()->json(['message' => 'แก้ไขงานสำเร็จ', 'task' => $task]);
    }

    // 3. ลบงาน
    public function destroy($id)
    {
        $task = Task::findOrFail($id);
        $task->delete();
        return response()->json(['message' => 'ลบงานสำเร็จ']);
    }

    // 4. ฟังก์ชันดึงประวัติ (ใช้ตอนกด Modal)
    public function getLogs($id)
    {
        $task = Task::with([
            'progressLogs.user', // ดึงประวัติ + ชื่อคนแก้
            'attachments.user'   // ดึงไฟล์แนบ + ชื่อคนอัป
        ])->findOrFail($id);

        return response()->json($task);
    }
}
