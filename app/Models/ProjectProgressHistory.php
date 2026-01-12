<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectProgressHistory extends Model
{
    use HasFactory;

    protected $table = 'project_progress_history'; // ชื่อตาราง
    protected $guarded = [];

    // เพิ่มตรงนี้ครับ! บอก Laravel ว่าให้มอง date_logged เป็นวันที่
    protected $casts = [
        'date_logged' => 'date',
    ];
}
