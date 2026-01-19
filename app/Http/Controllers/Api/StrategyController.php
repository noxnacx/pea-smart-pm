<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Strategy;
use Illuminate\Http\Request;

class StrategyController extends Controller
{
    // ดึงรายชื่อยุทธศาสตร์ทั้งหมด (พร้อมแผนงานลูก)
    public function index()
    {
        return Strategy::withCount('programs')->get();
    }

    // สร้างยุทธศาสตร์ใหม่
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'fiscal_year_id' => 'nullable|integer'
        ]);

        $strategy = Strategy::create($validated);
        return response()->json($strategy, 201);
    }

    // ดูรายละเอียด
    public function show($id)
    {
        return Strategy::with('programs')->findOrFail($id);
    }

    // แก้ไข
    public function update(Request $request, $id)
    {
        $strategy = Strategy::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $strategy->update($validated);
        return response()->json($strategy);
    }

    // ลบ
    public function destroy($id)
    {
        $strategy = Strategy::findOrFail($id);
        // อาจจะเช็คก่อนว่ามี Program ผูกอยู่ไหม ถ้ามีห้ามลบ หรือให้ Set Null
        $strategy->delete();
        return response()->json(['message' => 'ลบยุทธศาสตร์เรียบร้อย']);
    }
}
