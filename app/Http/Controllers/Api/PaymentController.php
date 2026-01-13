<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Payment;
use App\Models\Project;

class PaymentController extends Controller
{
    // ดึงรายการจ่ายเงินของโครงการนั้นๆ
    public function index($projectId)
    {
        return Payment::with('user') // <--- เพิ่ม with('user')
            ->where('project_id', $projectId)
            ->orderBy('payment_date', 'desc')
            ->get();
    }

    // บันทึกการจ่ายเงิน
    public function store(Request $request)
    {
        $validated = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'payment_date' => 'required|date',
            'amount' => 'required|numeric|min:0',
            'description' => 'required|string',
        ]);

        // ใส่ User ID ของคนปัจจุบันลงไป
        $payment = Payment::create(array_merge($validated, [
            'user_id' => $request->user()->id // <--- จุดสำคัญอยู่ตรงนี้
        ]));

        return response()->json($payment, 201);
    }

    // ลบรายการ
    public function destroy($id)
    {
        Payment::destroy($id);
        return response()->json(['message' => 'Deleted']);
    }


    // เพิ่มใน PaymentController.php
    public function getFiles($id)
    {
        $payment = Payment::findOrFail($id);
        return $payment->attachments; // ดึงไฟล์ทั้งหมดของ Payment นี้
    }

}
