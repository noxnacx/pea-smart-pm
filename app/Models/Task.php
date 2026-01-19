<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;

    protected $guarded = [];

    // --- Relations เดิม ---

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'task_user');
    }

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

    // --- Relations ใหม่ (Hierarchy) ---

    // Task นี้แตกเป็นโครงการย่อยๆ อะไรบ้าง (Task -> Projects)
    public function childProjects()
    {
        return $this->hasMany(Project::class, 'task_parent_id');
    }
}
