<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Program extends Model
{
    use HasFactory;
    protected $guarded = [];

    // --- Relations เดิม ---

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

    // แผนงานนี้ มีโครงการย่อยอะไรบ้าง (Direct Children)
    public function projects()
    {
        return $this->hasMany(Project::class);
    }

    // --- Relations ใหม่ (Recursive Hierarchy) ---

    // 1. อยู่ภายใต้ยุทธศาสตร์อะไร
    public function strategy()
    {
        return $this->belongsTo(Strategy::class);
    }

    // 2. แผนงานแม่ (Parent Program)
    public function parent()
    {
        return $this->belongsTo(Program::class, 'parent_id');
    }

    // 3. แผนงานลูก (Sub-Programs)
    public function children()
    {
        return $this->hasMany(Program::class, 'parent_id');
    }

    // Helper: เช็คว่าเป็นแผนงานหลักหรือไม่ (ไม่มีแม่)
    public function isRoot()
    {
        return is_null($this->parent_id);
    }
}
