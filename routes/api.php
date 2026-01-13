<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Import Controllers
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\BudgetController;
use App\Http\Controllers\Api\AttachmentController;
use App\Http\Controllers\Api\ProgramController;
use App\Http\Controllers\Api\UserController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// 1. PUBLIC ROUTES
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout']);

// 2. PROTECTED ROUTES
Route::middleware('auth:sanctum')->group(function () {

    // --- User Info ---
    Route::get('/user', [AuthController::class, 'me']);

    // --- Dashboard ---
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // --- Projects (โครงการ) ---
    Route::prefix('projects')->group(function () {
        Route::get('/', [ProjectController::class, 'index']);
        Route::post('/', [ProjectController::class, 'store']);

        // ✅ แก้ไข: เส้นทางดูรายละเอียดโครงการ (Project Show)
        Route::get('/{id}', [ProjectController::class, 'show']);

        Route::put('/{id}', [ProjectController::class, 'update']);
        Route::delete('/{id}', [ProjectController::class, 'destroy']);

        // Project Sub-features
        Route::post('/{id}/progress', [ProjectController::class, 'updateProgress']);
        Route::post('/{id}/members', [ProjectController::class, 'addMember']);
        Route::delete('/{id}/members/{userId}', [ProjectController::class, 'removeMember']);

        // Financials inside Project
        Route::get('/{id}/payments', [PaymentController::class, 'index']);
        Route::get('/{id}/budget', [BudgetController::class, 'index']);
    });

    // --- Master Data Options ---
    Route::get('/master-data/project-options', [ProjectController::class, 'getOptions']);
    Route::get('/users/search', [ProjectController::class, 'searchUsers']);

    // --- Tasks ---
    Route::post('/tasks', [TaskController::class, 'store']);
    Route::put('/tasks/{id}', [TaskController::class, 'update']);
    Route::delete('/tasks/{id}', [TaskController::class, 'destroy']);
    Route::get('/tasks/{id}/logs', [TaskController::class, 'getLogs']);

    // --- Financials ---
    Route::post('/payments', [PaymentController::class, 'store']);
    Route::delete('/payments/{id}', [PaymentController::class, 'destroy']);
    Route::get('/payments/{id}/files', [PaymentController::class, 'getFiles']);

    Route::post('/budget-items', [BudgetController::class, 'store']);
    Route::delete('/budget-items/{id}', [BudgetController::class, 'destroy']);

    // --- Files ---
    Route::get('/attachments', [AttachmentController::class, 'index']);
    Route::post('/attachments/upload', [AttachmentController::class, 'upload']);
    Route::delete('/attachments/{id}', [AttachmentController::class, 'destroy']);

    // --- Admin Section ---
    // Manage Programs (เส้นทางนี้จะสร้าง /api/programs/{id} ให้เอง ไม่ต้องไปใส่ใน projects)
    Route::apiResource('programs', ProgramController::class);

    // Manage Users
    Route::apiResource('users', UserController::class);

});
