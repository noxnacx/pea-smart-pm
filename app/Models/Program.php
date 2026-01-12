<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Program extends Model
{
    use HasFactory;
    protected $guarded = [];

    // แผนงานนี้ อยู่ในปีงบไหน
    public function fiscalYear()
    {
        return $this->belongsTo(FiscalYear::class);
    }

    // แผนงานนี้ มีใครเป็นเจ้าของ (ผอ.)
    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    // แผนงานนี้ มีโครงการย่อยอะไรบ้าง
    public function projects()
    {
        return $this->hasMany(Project::class);
    }
}
