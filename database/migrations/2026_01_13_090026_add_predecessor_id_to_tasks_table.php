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
        Schema::table('tasks', function (Blueprint $table) {
            // สร้างความสัมพันธ์กับตาราง tasks เอง (Self-referencing)
            $table->foreignId('predecessor_id')
                ->nullable()
                ->constrained('tasks')
                ->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropForeign(['predecessor_id']);
            $table->dropColumn('predecessor_id');
        });
    }
};
