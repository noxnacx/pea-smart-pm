<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController; // เรียกใช้ Controller

// 1. ทางลับสำหรับรีเซ็ตรหัสผ่าน (ใช้ครั้งสุดท้ายแล้วลบทิ้งได้เลย)
Route::get('/force-reset', function () {
    $user = \App\Models\User::where('email', 'admin@pea.co.th')->first();
    if (!$user) return 'ไม่พบ User!';

    // พอเราแก้ User.php แล้ว การสั่ง Hash::make ตรงนี้จะได้รหัสที่ถูกต้องชั้นเดียวครับ
    $user->password = \Illuminate\Support\Facades\Hash::make('password');
    $user->save();
    return '✅ รีเซ็ตรหัสผ่านสำเร็จ! (User.php ต้องไม่มี hashed cast นะครับ)';
});

// 2. ย้าย Login/Logout มาไว้ที่นี่ (เพราะ web.php มี Session ให้ใช้ ไม่ Error 500 แน่นอน)
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout']);

// 3. เส้นทางหลัก Vue.js (ต้องอยู่ล่างสุดเสมอ)
Route::get('/{any}', function () {
    return view('welcome');
})->where('any', '.*');
