<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Program;
use Illuminate\Http\Request;

class ProgramController extends Controller
{
    public function index()
    {
        return response()->json(Program::orderBy('created_at', 'desc')->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'total_budget' => 'required|numeric|min:0',
        ]);

        $program = Program::create($validated);
        return response()->json(['message' => 'เพิ่มแผนงานสำเร็จ', 'program' => $program], 201);
    }

    public function update(Request $request, $id)
    {
        $program = Program::findOrFail($id);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'total_budget' => 'required|numeric|min:0',
        ]);

        $program->update($validated);
        return response()->json(['message' => 'อัปเดตแผนงานสำเร็จ', 'program' => $program]);
    }

    public function destroy($id)
    {
        $program = Program::findOrFail($id);
        // เช็คก่อนลบ: ถ้ามีโครงการผูกอยู่ ห้ามลบ
        if ($program->projects()->count() > 0) {
            return response()->json(['message' => 'ไม่สามารถลบแผนงานที่มีโครงการอยู่ได้'], 400);
        }
        $program->delete();
        return response()->json(['message' => 'ลบแผนงานสำเร็จ']);
    }
}
