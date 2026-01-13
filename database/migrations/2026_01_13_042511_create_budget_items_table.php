<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB; // <--- 1. เพิ่มบรรทัดนี้

return new class extends Migration
{
    public function up()
    {
        // 2. แก้บรรทัดนี้: ใช้ Raw SQL สั่งลบแบบ CASCADE (แรงกว่า dropIfExists ปกติ)
        DB::statement('DROP TABLE IF EXISTS budget_items CASCADE');

        // 3. สร้างตารางใหม่เหมือนเดิม
        Schema::create('budget_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->string('category')->nullable();
            $table->string('name');
            $table->decimal('amount', 15, 2);
            $table->timestamps();
        });
    }

    public function down()
    {
        // ตอน rollback ก็ต้อง CASCADE เหมือนกัน
        DB::statement('DROP TABLE IF EXISTS budget_items CASCADE');
    }
};
