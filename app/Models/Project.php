<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

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

    /**
     * ระบบคำนวณความเสี่ยงอัตโนมัติ (Risk Analysis)
     * คำนวณจาก Gap ระหว่าง "เปอร์เซ็นต์เวลาที่ผ่านไป" เทียบกับ "ความคืบหน้าจริง"
     */
    public function getRiskAnalysisAttribute()
    {
        $now = Carbon::now();
        $start = $this->start_date;
        $end = $this->end_date;

        // 1. กรณีโครงการยังไม่ถึงกำหนดเริ่ม
        if (!$start || $now->lt($start)) {
            return [
                'level' => 'low',
                'label' => 'ยังไม่ถึงกำหนดเริ่ม',
                'expected' => 0,
                'gap' => 0,
                'color' => 'gray'
            ];
        }

        // 2. คำนวณแผนงานที่ควรจะได้ (Expected Progress) ตามระยะเวลา
        $totalDuration = $start->diffInDays($end);
        $elapsed = $start->diffInDays($now);

        // แผนงานที่ควรได้ ณ วันนี้ (%) ไม่เกิน 100
        $expectedProgress = $totalDuration > 0 ? min(100, round(($elapsed / $totalDuration) * 100, 2)) : 0;
        $actualProgress = $this->progress_actual ?? 0;

        // ส่วนต่าง (Gap) ระหว่างแผนกับผล
        $gap = round($expectedProgress - $actualProgress, 2);

        // 3. ประเมินระดับความเสี่ยง
        // ความเสี่ยงสูง: ล่าช้ากว่าแผนเกิน 20% หรือ เลยกำหนดส่งแต่ยังไม่เสร็จ
        if ($gap > 20 || ($now->gt($end) && $actualProgress < 100)) {
            return [
                'level' => 'high',
                'label' => 'วิกฤต (ล่าช้ากว่าแผนมาก)',
                'expected' => $expectedProgress,
                'gap' => $gap,
                'color' => 'red'
            ];
        }
        // ความเสี่ยงปานกลาง: ล่าช้ากว่าแผนเกิน 10%
        elseif ($gap > 10) {
            return [
                'level' => 'medium',
                'label' => 'เสี่ยง (เริ่มล่าช้า)',
                'expected' => $expectedProgress,
                'gap' => $gap,
                'color' => 'yellow'
            ];
        }

        // สถานะปกติ
        return [
            'level' => 'low',
            'label' => 'ปกติ',
            'expected' => $expectedProgress,
            'gap' => $gap,
            'color' => 'green'
        ];
    }

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

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function attachments()
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    // *** เพิ่มฟังก์ชันนี้เพื่อแก้ Error 500 และให้ระบบจัดการทีมทำงานได้ ***
    public function members()
    {
        // เชื่อม Many-to-Many กับ User โดยใช้ตารางกลาง 'project_user'
        return $this->belongsToMany(User::class, 'project_user')
            ->withPivot('role') // ดึงข้อมูล role ในตารางกลางมาด้วย
            ->withTimestamps(); // ดึง created_at, updated_at มาด้วย
    }
}
