<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function show($id)
    {
        // ค้นหาโครงการตาม ID
        $project = Project::with([
            'manager',          // ใครคุม
            'tasks',            // งานย่อย (เอาไปทำ Gantt)
            'budgetItems',      // รายการงบ
            'risks',            // ความเสี่ยง
            'progressHistory'   // กราฟ S-Curve เฉพาะโครงการนี้
        ])->find($id);

        if (!$project) {
            return response()->json(['message' => 'ไม่พบข้อมูลโครงการ'], 404);
        }

        // เตรียมข้อมูลส่งกลับ
        return response()->json([
            'project' => $project,
            // คำนวณงบประมาณคงเหลือส่งไปด้วยเลย
            'budget_summary' => [
                'total' => $project->contract_amount,
                'used' => $project->paid_amount, // หรือจะดึงจาก expenses sum ก็ได้
                'remaining' => $project->contract_amount - $project->paid_amount
            ]
        ]);
    }

    // ... (ฟังก์ชันอื่นเดิม) ...

    // เพิ่มฟังก์ชันนี้สำหรับ "สร้างโครงการใหม่"
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'contract_amount' => 'required|numeric',
            'status' => 'required|in:draft,ongoing,late,completed,cancel',
            'progress_actual' => 'nullable|numeric|min:0|max:100',
        ]);

        // เนื่องจากเรายังไม่ได้ทำระบบ Login ขอ Hardcode ค่าเหล่านี้ไปก่อนครับ
        $validated['code'] = 'PEA-' . date('y') . '-' . rand(100, 999); // สุ่มรหัสโครงการ
        $validated['program_id'] = 1; // ผูกกับแผนงานแรก
        $validated['manager_id'] = 1; // ให้ Admin เป็นคนดูแล
        $validated['start_date'] = now();
        $validated['end_date'] = now()->addYear();

        $project = Project::create($validated);

        return response()->json(['message' => 'สร้างโครงการสำเร็จ', 'project' => $project], 201);
    }


    public function update(Request $request, $id)
    {
        $project = Project::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string',
            'status' => 'required|in:draft,ongoing,late,completed,cancel',
            'progress_actual' => 'required|numeric|min:0|max:100',
            'contract_amount' => 'required|numeric',
        ]);

        $project->update($validated);

        return response()->json(['message' => 'อัปเดตโครงการสำเร็จ', 'project' => $project]);
    }
}
