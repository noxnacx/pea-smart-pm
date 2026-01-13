<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'parent_id',
        'name',
        'weight',
        'progress',
        'start_date',
        'end_date',
        'user_id' // <--- 1. เพิ่มตรงนี้
    ];

    // 2. เพิ่มฟังก์ชันดึงชื่อคน
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // เพิ่มใน Task.php
    public function progressLogs()
    {
        return $this->hasMany(TaskProgressLog::class)->orderBy('created_at', 'desc');
    }

    public function attachments()
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

}
