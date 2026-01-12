<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Task;

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

        $task = Task::create($validated);
        return response()->json(['message' => 'เพิ่มงานสำเร็จ', 'task' => $task], 201);
    }

    // 2. *** (เพิ่มใหม่) แก้ไขงาน ***
    public function update(Request $request, $id)
    {
        $task = Task::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'weight' => 'required|numeric|min:0|max:100',
            'progress' => 'nullable|numeric|min:0|max:100',
        ]);

        $task->update($validated);

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
