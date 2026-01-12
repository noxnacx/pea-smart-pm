<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\AuthController;


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// เส้นทางสำหรับดึงข้อมูล Dashboard
Route::get('/dashboard', [DashboardController::class, 'index']);
Route::get('/projects/{id}', [ProjectController::class, 'show']);
Route::post('/tasks', [TaskController::class, 'store']); // เพิ่มงาน
Route::put('/tasks/{id}', [TaskController::class, 'update']);
Route::delete('/tasks/{id}', [TaskController::class, 'destroy']); // ลบงาน
Route::put('/projects/{id}', [ProjectController::class, 'update']); // แก้ไขโครงการ
Route::post('/projects', [App\Http\Controllers\Api\ProjectController::class, 'store']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout']);
Route::get('/user', [AuthController::class, 'me']); // เช็คข้อมูลตัวเอง
