<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'file_name',      // ชื่อไฟล์เดิม (เช่น contract.pdf)
        'file_path',      // ที่อยู่ใน Storage (เช่น attachments/xyz.pdf)
        'file_type',      // นามสกุลไฟล์ (pdf, png)
        'attachable_id',  // ID ของสิ่งที่แนบ (Task ID หรือ Payment ID)
        'attachable_type',// ชื่อ Model (App\Models\Task)
        'user_id'         // ID คนอัปโหลด
    ];

    /**
     * ความสัมพันธ์แบบ Polymorphic (ใช้ร่วมกับ Task, Payment, Project ได้หมด)
     * ตัวนี้จะรู้เองว่าต้องไปดึงข้อมูลจากตารางไหน โดยดูจาก attachable_type
     */
    public function attachable()
    {
        return $this->morphTo();
    }

    /**
     * ไฟล์นี้เป็นของใคร (คนอัปโหลด)
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
