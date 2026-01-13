<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // 1. อัปเดตตาราง Users (เพิ่ม Role)
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'role')) {
                // role: admin, program_manager, user
                $table->string('role')->default('user')->after('email');
            }
        });

        // 2. อัปเดตตาราง Projects (เพิ่ม Manager ถ้ายังไม่มี)
        Schema::table('projects', function (Blueprint $table) {
            if (!Schema::hasColumn('projects', 'manager_id')) {
                $table->foreignId('manager_id')->nullable()->constrained('users')->onDelete('set null');
            }
        });

        // 3. สร้างตาราง Programs (ถ้ายังไม่มี) หรือเพิ่ม total_budget
        if (!Schema::hasTable('programs')) {
            Schema::create('programs', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->decimal('total_budget', 15, 2)->default(0); // เงินรวมแผนงาน
                $table->timestamps();
            });
        } else {
            Schema::table('programs', function (Blueprint $table) {
                if (!Schema::hasColumn('programs', 'total_budget')) {
                    $table->decimal('total_budget', 15, 2)->default(0);
                }
            });
        }

        // 4. สร้างตาราง Pivot เก็บสมาชิกทีม (project_user)
        if (!Schema::hasTable('project_user')) {
            Schema::create('project_user', function (Blueprint $table) {
                $table->id();
                $table->foreignId('project_id')->constrained()->onDelete('cascade');
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->string('role')->default('member'); // member, viewer
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        // ย้อนกลับ (ลบสิ่งที่เพิ่ม)
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
        Schema::dropIfExists('project_user');
        // ส่วนอื่นๆ ละไว้ เพื่อความปลอดภัยข้อมูล
    }
};
