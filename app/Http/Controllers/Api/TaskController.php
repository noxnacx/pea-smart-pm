<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Task;

class TaskController extends Controller
{
    // 1. สร้างงานใหม่
    // แก้ฟังก์ชัน store (เพิ่มงาน)
    public function store(Request $request)
    {
        $validated = $request->validate([
            // ... validation เดิม ...
            'project_id' => 'required|exists:projects,id',
            'name' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'weight' => 'required|numeric|min:0|max:100',
            'progress' => 'nullable|numeric|min:0|max:100',
        ]);

        // บันทึก User ID คนปัจจุบัน
        $task = Task::create(array_merge($validated, [
            'user_id' => $request->user()->id
        ]));

        return response()->json(['message' => 'เพิ่มงานสำเร็จ', 'task' => $task], 201);
    }

    // แก้ฟังก์ชัน update (แก้ไขงาน)
    public function update(Request $request, $id)
    {
        $task = Task::findOrFail($id);

        $validated = $request->validate([
            // ... validation เดิม ...
            'name' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'weight' => 'required|numeric|min:0|max:100',
            'progress' => 'nullable|numeric|min:0|max:100',
        ]);

        // อัปเดตข้อมูล + เปลี่ยนชื่อคนแก้ไขล่าสุดเป็นคนปัจจุบัน
        $task->update(array_merge($validated, [
            'user_id' => $request->user()->id
        ]));

        return response()->json(['message' => 'แก้ไขงานสำเร็จ', 'task' => $task]);
    }
    // 3. ลบงาน
    public function destroy($id)
    {
        $task = Task::findOrFail($id);
        $task->delete();
        return response()->json(['message' => 'ลบงานสำเร็จ']);
    }
}
