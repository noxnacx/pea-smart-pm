<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\AttachmentController;




// 1. เส้นทางสาธารณะ (ไม่ต้องล็อกอินก็เข้าได้)
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout']);

// 2. เส้นทางที่ "ต้องล็อกอิน" (Secure Zone)
Route::middleware('auth:sanctum')->group(function () {

    // ดึงข้อมูล User ปัจจุบัน
    Route::get('/user', [AuthController::class, 'me']);

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // Projects (โครงการ)
    Route::get('/projects', [ProjectController::class, 'index']);
    Route::post('/projects', [ProjectController::class, 'store']);
    Route::get('/projects/{id}', [ProjectController::class, 'show']);
    Route::put('/projects/{id}', [ProjectController::class, 'update']);
    Route::delete('/projects/{id}', [ProjectController::class, 'destroy']);
    Route::get('/master-data/project-options', [ProjectController::class, 'getOptions']);

    // Progress (อัปเดตความก้าวหน้า)
    Route::post('/projects/{id}/progress', [ProjectController::class, 'updateProgress']);

    // Tasks (งานย่อย)
    Route::post('/tasks', [TaskController::class, 'store']);
    Route::put('/tasks/{id}', [TaskController::class, 'update']);
    Route::delete('/tasks/{id}', [TaskController::class, 'destroy']);

    // Payments (การเงิน)
    Route::get('/projects/{id}/payments', [PaymentController::class, 'index']);
    Route::post('/payments', [PaymentController::class, 'store']);
    Route::delete('/payments/{id}', [PaymentController::class, 'destroy']);
    Route::get('/payments/{id}/files', [PaymentController::class, 'getFiles']);

    // อัปโหลดไฟล์
    Route::post('/attachments/upload', [AttachmentController::class, 'upload']);
    Route::delete('/attachments/{id}', [AttachmentController::class, 'destroy']);

    // ดึงประวัติของ Task รายตัว
    Route::get('/tasks/{id}/logs', [TaskController::class, 'getLogs']);
    Route::get('/attachments', [AttachmentController::class, 'index']);

    // Team Management
    Route::get('/users/search', [ProjectController::class, 'searchUsers']);
    Route::post('/projects/{id}/members', [ProjectController::class, 'addMember']);
    Route::delete('/projects/{id}/members/{userId}', [ProjectController::class, 'removeMember']);

    // Admin: Manage Programs
    Route::get('/programs', [App\Http\Controllers\Api\ProgramController::class, 'index']);
    Route::post('/programs', [App\Http\Controllers\Api\ProgramController::class, 'store']);
    Route::put('/programs/{id}', [App\Http\Controllers\Api\ProgramController::class, 'update']);
    Route::delete('/programs/{id}', [App\Http\Controllers\Api\ProgramController::class, 'destroy']);

    // Admin: Manage Users
    Route::get('/users', [App\Http\Controllers\Api\UserController::class, 'index']);
    Route::post('/users', [App\Http\Controllers\Api\UserController::class, 'store']);
    Route::put('/users/{id}', [App\Http\Controllers\Api\UserController::class, 'update']);
    Route::delete('/users/{id}', [App\Http\Controllers\Api\UserController::class, 'destroy']);
});
