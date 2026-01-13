<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    // 1. ตรวจสอบชื่อผู้ใช้และรหัสผ่าน
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials)) {
            /** @var \App\Models\User $user */
            $user = Auth::user();

            // 2. สร้าง Token (หัวใจสำคัญ)
            // 'auth_token' คือชื่อของ Token ตั้งอะไรก็ได้
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message' => 'เข้าสู่ระบบสำเร็จ',
                'user' => $user,
                'token' => $token // <--- ส่งกุญแจกลับไปให้ Vue.js เก็บไว้
            ]);
        }

        return response()->json([
            'message' => 'อีเมลหรือรหัสผ่านไม่ถูกต้อง'
        ], 401);
    }

    // 2. ออกจากระบบ (แก้ไขใหม่สำหรับ API)
    public function logout(Request $request)
    {
        // ลบ Token ปัจจุบันที่ใช้อยู่ทิ้งไป (Revoke Current Token)
        // คำสั่งนี้จะทำให้ Token ที่แนบมากับ Request นี้ใช้งานไม่ได้อีกต่อไป
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'ออกจากระบบสำเร็จ']);
    }

    // 3. เช็คว่าตอนนี้ใครล็อกอินอยู่?
    public function me(Request $request)
    {
        return response()->json($request->user());
    }
}
