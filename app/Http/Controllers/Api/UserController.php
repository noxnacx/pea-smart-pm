<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index()
    {
        return response()->json(User::orderBy('name')->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'role' => 'required|in:admin,program_manager,user',
        ]);

        $validated['password'] = Hash::make($validated['password']);

        $user = User::create($validated);
        return response()->json(['message' => 'เพิ่มผู้ใช้งานสำเร็จ', 'user' => $user], 201);
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users,email,' . $id,
            'role' => 'required|in:admin,program_manager,user',
            'password' => 'nullable|min:6', // ถ้าไม่กรอก = ไม่เปลี่ยน
        ]);

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);
        return response()->json(['message' => 'อัปเดตข้อมูลสำเร็จ', 'user' => $user]);
    }

    public function destroy($id)
    {
        if ($id == auth()->id()) {
            return response()->json(['message' => 'ไม่สามารถลบตัวเองได้'], 400);
        }
        User::destroy($id);
        return response()->json(['message' => 'ลบผู้ใช้งานสำเร็จ']);
    }
}
