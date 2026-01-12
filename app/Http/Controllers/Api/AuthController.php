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
            // 1. ดึง User ออกมา
            $user = Auth::user();

            // 2. *** สร้าง Token (หัวใจสำคัญที่หายไป) ***
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message' => 'เข้าสู่ระบบสำเร็จ',
                'user' => $user,
                'token' => $token // <--- ส่งกุญแจกลับไปให้ Vue.js
            ]);
        }

        return response()->json([
            'message' => 'อีเมลหรือรหัสผ่านไม่ถูกต้อง'
        ], 401);
    }

    // 2. ออกจากระบบ
    public function logout(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'ออกจากระบบสำเร็จ']);
    }

    // 3. เช็คว่าตอนนี้ใครล็อกอินอยู่?
    public function me(Request $request)
    {
        return response()->json($request->user());
    }
}
