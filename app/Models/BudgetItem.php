<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BudgetItem extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    // งบก้อนนี้ ถูกเบิกจ่ายไปรายการไหนบ้าง
    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }
}
