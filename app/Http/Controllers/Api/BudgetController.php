<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\BudgetItem;
use App\Models\Project;

class BudgetController extends Controller
{
    // ดึงรายการ BOQ ทั้งหมดของโครงการ
    public function index($projectId)
    {
        $items = BudgetItem::where('project_id', $projectId)->orderBy('category')->get();
        return response()->json($items);
    }

    // เพิ่มรายการใหม่
    public function store(Request $request)
    {
        $validated = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'category' => 'required|string',
            'name' => 'required|string',
            'amount' => 'required|numeric|min:0',
        ]);

        // (Optional) เช็คว่ายอดรวมเกินวงเงินสัญญาหรือไม่?
        // $project = Project::find($request->project_id);
        // $currentTotal = BudgetItem::where('project_id', $request->project_id)->sum('amount');
        // if ($currentTotal + $request->amount > $project->contract_amount) { ... }

        $item = BudgetItem::create($validated);
        return response()->json(['message' => 'บันทึกรายการสำเร็จ', 'item' => $item], 201);
    }

    // ลบรายการ
    public function destroy($id)
    {
        BudgetItem::destroy($id);
        return response()->json(['message' => 'ลบรายการเรียบร้อย']);
    }
}
