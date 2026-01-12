<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // 1. เพิ่ม user_id ในตาราง payments (ถ้ายังไม่มี)
        Schema::table('payments', function (Blueprint $table) {
            if (!Schema::hasColumn('payments', 'user_id')) {
                $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            }
        });

        // 2. เพิ่ม user_id ในตาราง project_progress_history (ชื่อนี้ต้องไม่มี s)
        Schema::table('project_progress_history', function (Blueprint $table) {
             if (!Schema::hasColumn('project_progress_history', 'user_id')) {
                $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
             }
        });
    }

    public function down()
    {
        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'user_id')) {
                $table->dropForeign(['user_id']);
                $table->dropColumn('user_id');
            }
        });

        Schema::table('project_progress_history', function (Blueprint $table) {
            if (Schema::hasColumn('project_progress_history', 'user_id')) {
                $table->dropForeign(['user_id']);
                $table->dropColumn('user_id');
            }
        });
    }
};
