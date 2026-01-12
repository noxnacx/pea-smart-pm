<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;
    protected $guarded = [];

    // แปลงวันที่ให้เป็น Carbon Object อัตโนมัติ (เพื่อให้จัดการวันที่ง่าย)
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'actual_start_date' => 'date',
        'actual_end_date' => 'date',
    ];

    // อยู่ใต้แผนงานไหน
    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    // ใครเป็น PM
    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    // มี Task อะไรบ้าง
    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    // มีหมวดงบประมาณอะไรบ้าง
    public function budgetItems()
    {
        return $this->hasMany(BudgetItem::class);
    }

    // มีความเสี่ยงอะไรบ้าง
    public function risks()
    {
        return $this->hasMany(Risk::class);
    }

    // ประวัติกราฟ S-Curve
    public function progressHistory()
    {
        return $this->hasMany(ProjectProgressHistory::class);
    }
}
