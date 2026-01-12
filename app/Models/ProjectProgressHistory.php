<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectProgressHistory extends Model
{
    use HasFactory;

    protected $table = 'project_progress_history';

    protected $fillable = [
        'project_id',
        'date_logged',
        'planned_percent',
        'actual_percent',
        'user_id'
    ];

    // *** เพิ่มส่วนนี้ครับ (สำคัญมากสำหรับกราฟ) ***
    protected $casts = [
        'date_logged' => 'date', // แปลงเป็นวันที่อัตโนมัติ
        'planned_percent' => 'float',
        'actual_percent' => 'float',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
