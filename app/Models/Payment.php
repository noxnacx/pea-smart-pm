<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'payment_date',
        'amount',
        'description',
        'user_id' // <--- 1. เพิ่มตรงนี้
    ];

    // 2. เพิ่มฟังก์ชันนี้
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function attachments() {

        return $this->morphMany(Attachment::class, 'attachable');

    }

}
