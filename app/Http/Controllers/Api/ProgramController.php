<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Program;
use Illuminate\Http\Request;

class ProgramController extends Controller
{
    public function index()
    {
        // เรียงตามปีงบประมาณล่าสุด
        return response()->json(Program::orderBy('fiscal_year', 'desc')->orderBy('created_at', 'desc')->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'fiscal_year' => 'required|string|size:4', // บังคับกรอกปี 4 หลัก
            'total_budget' => 'required|numeric|min:0',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'description' => 'nullable|string',
        ]);

        $program = Program::create($validated);
        return response()->json(['message' => 'เพิ่มแผนงานสำเร็จ', 'program' => $program], 201);
    }

    public function update(Request $request, $id)
    {
        $program = Program::findOrFail($id);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'fiscal_year' => 'required|string|size:4',
            'total_budget' => 'required|numeric|min:0',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'description' => 'nullable|string',
            'status' => 'required|in:active,closed'
        ]);

        $program->update($validated);
        return response()->json(['message' => 'อัปเดตแผนงานสำเร็จ', 'program' => $program]);
    }

    public function destroy($id)
    {
        $program = Program::findOrFail($id);
        if ($program->projects()->count() > 0) {
            return response()->json(['message' => 'ไม่สามารถลบแผนงานที่มีโครงการอยู่ได้'], 400);
        }
        $program->delete();
        return response()->json(['message' => 'ลบแผนงานสำเร็จ']);
    }
    public function show($id)
    {
        return response()->json(Program::findOrFail($id));
    }
}
