<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Program;
use Illuminate\Http\Request;

class ProgramController extends Controller
{
    public function index(Request $request)
    {
        $query = Program::with(['strategy', 'owner'])
            ->withCount('projects'); // นับจำนวนโครงการ

        // ถ้าต้องการดูแบบ Tree (เฉพาะ Root Program)
        if ($request->has('root_only')) {
            $query->whereNull('parent_id');
        }

        // กรองตามยุทธศาสตร์
        if ($request->has('strategy_id')) {
            $query->where('strategy_id', $request->strategy_id);
        }

        return $query->latest()->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'status' => 'required|in:active,inactive,completed',
            'owner_id' => 'nullable|exists:users,id',
            // ✅ เพิ่ม 2 field นี้
            'strategy_id' => 'nullable|exists:strategies,id',
            'parent_id' => 'nullable|exists:programs,id',
        ]);

        $program = Program::create($validated);
        return response()->json($program, 201);
    }

    public function show($id)
    {
        // ดึงข้อมูลพร้อมแผนงานลูก (Children) และโครงการ (Projects)
        return Program::with(['strategy', 'owner', 'children', 'projects.manager'])
            ->findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $program = Program::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'status' => 'required|in:active,inactive,completed',
            'owner_id' => 'nullable|exists:users,id',
            'strategy_id' => 'nullable|exists:strategies,id',
            'parent_id' => 'nullable|exists:programs,id',
        ]);

        // ป้องกันการตั้งตัวเองเป็นแม่ตัวเอง (Circular Reference)
        if ($request->parent_id == $id) {
            return response()->json(['message' => 'ไม่สามารถเลือกตัวเองเป็นแผนงานแม่ได้'], 422);
        }

        $program->update($validated);
        return response()->json($program);
    }

    public function destroy($id)
    {
        Program::destroy($id);
        return response()->json(['message' => 'Deleted successfully']);
    }
}
