<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Program;
use Illuminate\Http\Request;

class ProgramController extends Controller
{
    public function index(Request $request)
    {
        $query = Program::query();

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('fiscal_year', 'like', "%{$search}%");
            });
        }

        return response()->json(
            $query->orderBy('fiscal_year', 'desc')
                  ->orderBy('created_at', 'desc')
                  ->get()
        );
    }

    public function store(Request $request)
    {
        // 1. Validate
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'fiscal_year' => 'required|string|max:4', // ใช้ max:4 แทน size:4 เผื่อความยืดหยุ่น
            'total_budget' => 'required|numeric|min:0',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'description' => 'nullable|string',
            'status' => 'nullable|string'
        ]);

        // 2. Data Cleaning (สำคัญมากสำหรับแก้ Error 500)
        // แปลงค่า "" (empty string) ให้เป็น null สำหรับช่องวันที่
        $data = $validated;
        if (empty($data['start_date'])) $data['start_date'] = null;
        if (empty($data['end_date'])) $data['end_date'] = null;

        // กำหนด Default Status
        if (empty($data['status'])) $data['status'] = 'active';

        // 3. Create
        try {
            $program = Program::create($data);
            return response()->json(['message' => 'เพิ่มแผนงานสำเร็จ', 'program' => $program], 201);
        } catch (\Exception $e) {
            // จับ Error จริงๆ เพื่อดูว่าผิดที่อะไร (ดูใน Network Tab -> Response)
            return response()->json(['message' => 'Server Error: ' . $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        return response()->json(Program::with('projects')->findOrFail($id));
    }

    public function update(Request $request, $id)
    {
        $program = Program::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'fiscal_year' => 'sometimes|required|string|max:4',
            'total_budget' => 'sometimes|required|numeric|min:0',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'description' => 'nullable|string',
            'status' => 'nullable|string'
        ]);

        // Data Cleaning
        $data = $validated;
        if (array_key_exists('start_date', $data) && empty($data['start_date'])) $data['start_date'] = null;
        if (array_key_exists('end_date', $data) && empty($data['end_date'])) $data['end_date'] = null;

        $program->update($data);

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
}
