<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('programs', function (Blueprint $table) {
            // แก้ไขให้ fiscal_year_id เป็น Nullable (ไม่ต้องกรอกก็ได้)
            if (Schema::hasColumn('programs', 'fiscal_year_id')) {
                $table->bigInteger('fiscal_year_id')->nullable()->change();
            }
        });
    }

    public function down()
    {
        Schema::table('programs', function (Blueprint $table) {
            // (Optional) แปลงกลับถ้า rollback
            // $table->bigInteger('fiscal_year_id')->nullable(false)->change();
        });
    }
};
