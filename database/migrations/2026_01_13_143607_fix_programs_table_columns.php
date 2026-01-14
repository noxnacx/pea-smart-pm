<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('programs', function (Blueprint $table) {
            // เช็คและเพิ่มคอลัมน์ที่จำเป็นสำหรับ Controller ใหม่
            if (!Schema::hasColumn('programs', 'fiscal_year')) {
                $table->string('fiscal_year', 4)->nullable()->after('name');
            }
            if (!Schema::hasColumn('programs', 'total_budget')) {
                $table->decimal('total_budget', 15, 2)->default(0)->after('fiscal_year');
            }
            if (!Schema::hasColumn('programs', 'start_date')) {
                $table->date('start_date')->nullable()->after('total_budget');
            }
            if (!Schema::hasColumn('programs', 'end_date')) {
                $table->date('end_date')->nullable()->after('start_date');
            }
            if (!Schema::hasColumn('programs', 'description')) {
                $table->text('description')->nullable()->after('end_date');
            }
            if (!Schema::hasColumn('programs', 'status')) {
                $table->string('status')->default('active')->after('description');
            }
        });
    }

    public function down()
    {
        // ไม่ต้องทำอะไร
    }
};
