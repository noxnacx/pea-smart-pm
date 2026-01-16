<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Project extends Model
{
    use HasFactory;
    use LogsActivity;

    protected $guarded = [];

    // แปลงวันที่ให้เป็น Carbon Object อัตโนมัติ
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'actual_start_date' => 'date',
        'actual_end_date' => 'date',
    ];

    /**
     * ระบบคำนวณความเสี่ยงอัตโนมัติ (Risk Analysis)
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

        // 2. คำนวณแผนงานที่ควรจะได้ (Expected Progress)
        $totalDuration = $start->diffInDays($end);
        $elapsed = $start->diffInDays($now);

        $expectedProgress = $totalDuration > 0 ? min(100, round(($elapsed / $totalDuration) * 100, 2)) : 0;
        $actualProgress = $this->progress_actual ?? 0;

        $gap = round($expectedProgress - $actualProgress, 2);

        // 3. ประเมินระดับความเสี่ยง
        if ($gap > 20 || ($now->gt($end) && $actualProgress < 100)) {
            return [
                'level' => 'high',
                'label' => 'วิกฤต (ล่าช้ากว่าแผนมาก)',
                'expected' => $expectedProgress,
                'gap' => $gap,
                'color' => 'red'
            ];
        } elseif ($gap > 10) {
            return [
                'level' => 'medium',
                'label' => 'เสี่ยง (เริ่มล่าช้า)',
                'expected' => $expectedProgress,
                'gap' => $gap,
                'color' => 'yellow'
            ];
        }

        return [
            'level' => 'low',
            'label' => 'ปกติ',
            'expected' => $expectedProgress,
            'gap' => $gap,
            'color' => 'green'
        ];
    }

    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    public function budgetItems()
    {
        return $this->hasMany(BudgetItem::class);
    }

    public function risks()
    {
        return $this->hasMany(Risk::class);
    }

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

    public function members()
    {
        return $this->belongsToMany(User::class, 'project_user')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable')->orderBy('created_at', 'desc');
    }

    // Config สำหรับ Activity Log
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "โครงการนี้ถูก {$eventName}");
    }
}
