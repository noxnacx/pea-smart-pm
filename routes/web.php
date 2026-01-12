<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController; // เรียกใช้ Controller

// 1. ทางลับสำหรับรีเซ็ตรหัสผ่าน (ใช้ครั้งสุดท้ายแล้วลบทิ้งได้เลย)
Route::get('/force-reset-db', function () {
    \Illuminate\Support\Facades\DB::table('users')
        ->where('email', 'admin@pea.co.th')
        ->update(['password' => \Illuminate\Support\Facades\Hash::make('password')]);

    return '✅ รีเซ็ตรหัสผ่านเรียบร้อย! (เจาะผ่าน Database โดยตรง)';
});

// 2. ย้าย Login/Logout มาไว้ที่นี่ (เพราะ web.php มี Session ให้ใช้ ไม่ Error 500 แน่นอน)
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout']);

// 3. เส้นทางหลัก Vue.js (ต้องอยู่ล่างสุดเสมอ)
Route::get('/{any}', function () {
    return view('welcome');
})->where('any', '.*');

// ทางลัดสำหรับสร้างตาราง Token ที่หายไป (กู้ชีพ Sanctum)
Route::get('/fix-sanctum', function () {
    try {
        $schema = Illuminate\Support\Facades\Schema::connection(null);

        if (!$schema->hasTable('personal_access_tokens')) {
            $schema->create('personal_access_tokens', function (Illuminate\Database\Schema\Blueprint $table) {
                $table->id();
                $table->morphs('tokenable');
                $table->string('name');
                $table->string('token', 64)->unique();
                $table->text('abilities')->nullable();
                $table->timestamp('last_used_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();
            });
            return '<h1 style="color:green">✅ สร้างตาราง personal_access_tokens สำเร็จแล้ว!</h1><br>ตอนนี้กลับไป Login ได้เลยครับ';
        }

        return '<h1 style="color:orange">⚠️ ตารางมีอยู่แล้วครับ (ปัญหาน่าจะอยู่ที่อื่น)</h1>';
    } catch (\Exception $e) {
        return '<h1 style="color:red">❌ เกิดข้อผิดพลาด: ' . $e->getMessage() . '</h1>';
    }
});

// ทางลัดสร้างข้อมูลตั้งต้น (Master Data Seeder)
Route::get('/seed-data', function () {
    // 1. สร้างปีงบประมาณ
    $fy = \App\Models\FiscalYear::firstOrCreate(
        ['name' => '2567'],
        ['start_date' => '2023-10-01', 'end_date' => '2024-09-30', 'is_active' => true]
    );

    // 2. สร้างแผนงานหลัก (Programs) - เพื่อให้งบประมาณรวมไม่เป็น 0
    \App\Models\Program::firstOrCreate(
        ['name' => 'แผนงานพัฒนาระบบไฟฟ้าอัจฉริยะ'],
        [
            'fiscal_year_id' => $fy->id,
            'owner_id' => 1,
            'total_budget' => 500000000, // 500 ล้านบาท
            'strategic_goal' => 'ยกระดับความมั่นคงระบบไฟฟ้า'
        ]
    );

    return '✅ สร้างข้อมูลตั้งต้น (ปีงบฯ + แผนงาน) เรียบร้อยแล้ว! กลับไปดู Dashboard ได้เลย';
});
