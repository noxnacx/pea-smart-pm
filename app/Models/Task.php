<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;

    protected $guarded = []; // ใช้ guarded แทน fillable เพื่อความยืดหยุ่น (หรือใช้ fillable แบบเดิมก็ได้)

    // 1. ความสัมพันธ์กับ Project (✅ ตัวที่ขาดหายไป)
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    // 2. ความสัมพันธ์กับ User (คนสร้าง/เจ้าของงาน)
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // 3. ความสัมพันธ์กับ Users (ทีมงานที่รับผิดชอบ - Many to Many)
    public function users()
    {
        return $this->belongsToMany(User::class, 'task_user');
    }

    // --- Relations อื่นๆ ---
    public function progressLogs()
    {
        return $this->hasMany(TaskProgressLog::class)->orderBy('created_at', 'desc');
    }

    public function attachments()
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable')->orderBy('created_at', 'desc');
    }

    public function predecessor()
    {
        return $this->belongsTo(Task::class, 'predecessor_id');
    }

    public function successors()
    {
        return $this->hasMany(Task::class, 'predecessor_id');
    }
}
