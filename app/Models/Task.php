<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;
    protected $guarded = [];

    protected $casts = [
        'start_date' => 'date',
        'due_date' => 'date',
    ];

    // งานนี้สังกัดโครงการไหน
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    // งานนี้เป็นลูกของงานไหน (Parent Task)
    public function parent()
    {
        return $this->belongsTo(Task::class, 'parent_id');
    }

    // งานนี้มีลูกน้องไหม (Sub-tasks)
    public function children()
    {
        return $this->hasMany(Task::class, 'parent_id');
    }
}
