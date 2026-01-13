<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskProgressLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_id',
        'progress_percent',
        'note',
        'user_id'
    ];

    // Log นี้เป็นของงาน (Task) ไหน
    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    // Log นี้ใครเป็นคนบันทึก
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
