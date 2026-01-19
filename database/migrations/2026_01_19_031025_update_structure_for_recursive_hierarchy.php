<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. ปรับปรุงตาราง Strategies (ที่สร้างจากคำสั่ง make:model) หรือถ้ายังไม่มีก็สร้างใหม่ตรงนี้
        if (!Schema::hasTable('strategies')) {
            Schema::create('strategies', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->text('description')->nullable();
                $table->unsignedBigInteger('fiscal_year_id')->nullable(); // ผูกปีงบประมาณ (ถ้ามี)
                $table->timestamps();
            });
        }

        // 2. อัปเดตตาราง Programs (แผนงาน)
        Schema::table('programs', function (Blueprint $table) {
            // เพิ่มความสัมพันธ์กับ Strategy
            $table->foreignId('strategy_id')->nullable()->constrained('strategies')->nullOnDelete();

            // เพิ่ม Recursive: แผนงานแม่ (Parent Program)
            $table->foreignId('parent_id')->nullable()->constrained('programs')->nullOnDelete();
        });

        // 3. อัปเดตตาราง Projects (โครงการ)
        Schema::table('projects', function (Blueprint $table) {
            // เพิ่ม Recursive: โครงการแม่ (Sub-Project)
            $table->foreignId('parent_id')->nullable()->constrained('projects')->nullOnDelete();

            // เพิ่ม Link: โครงการนี้แตกออกมาจาก Task ไหน (Task -> Project)
            // ใช้ unsignedBigInteger และ index ไว้เชื่อมโยง (ไม่ทำ constrained อัตโนมัติเพื่อป้องกัน circular dependency error ในบาง database แต่เราจัดการใน logic ได้)
            $table->unsignedBigInteger('task_parent_id')->nullable()->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['parent_id', 'task_parent_id']);
        });

        Schema::table('programs', function (Blueprint $table) {
            $table->dropForeign(['strategy_id']);
            $table->dropForeign(['parent_id']);
            $table->dropColumn(['strategy_id', 'parent_id']);
        });

        Schema::dropIfExists('strategies');
    }
};
