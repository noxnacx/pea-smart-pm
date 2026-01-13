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
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->text('body'); // ข้อความคอมเมนต์
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // ใครพิมพ์

            // Morph columns: จะสร้าง commentable_id และ commentable_type ให้อัตโนมัติ
            // เพื่อให้ตารางนี้ไปเกาะกับ Project หรือ Task ก็ได้
            $table->morphs('commentable');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('comments');
    }
};
