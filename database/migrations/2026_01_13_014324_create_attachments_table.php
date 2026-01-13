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
        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            $table->string('file_name'); // ชื่อไฟล์เดิม
            $table->string('file_path'); // ที่อยู่ไฟล์ในเครื่อง Server
            $table->string('file_type'); // ประเภทไฟล์ (pdf, png, jpg)
            // morphs จะช่วยให้ตารางเดียวแนบได้ทั้ง Task, Payment และ Project
            $table->morphs('attachable');
            $table->foreignId('user_id')->constrained(); // ใครเป็นคนอัปโหลด
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attachments');
    }
};
