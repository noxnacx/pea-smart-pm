<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('programs', function (Blueprint $table) {
            $table->string('fiscal_year', 4)->nullable()->after('name'); // ปีงบประมาณ (เช่น 2567)
            $table->date('start_date')->nullable()->after('total_budget');
            $table->date('end_date')->nullable()->after('start_date');
            $table->text('description')->nullable()->after('end_date');
            $table->string('status')->default('active')->after('description'); // active, closed
        });
    }

    public function down()
    {
        Schema::table('programs', function (Blueprint $table) {
            $table->dropColumn(['fiscal_year', 'start_date', 'end_date', 'description', 'status']);
        });
    }
};
