<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    use HasFactory;

    protected $fillable = ['body', 'user_id', 'commentable_id', 'commentable_type'];

    // คอมเมนต์นี้ต้องรู้วันเวลาแบบอ่านง่าย (Optional Helper)
    protected $appends = ['time_ago'];

    public function getTimeAgoAttribute()
    {
        return $this->created_at->diffForHumans();
    }

    // ใครเป็นคนคอมเมนต์
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Polymorphic relation
    public function commentable()
    {
        return $this->morphTo();
    }
}
