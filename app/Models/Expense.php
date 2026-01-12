<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    use HasFactory;
    protected $guarded = [];

    protected $casts = [
        'payment_date' => 'date',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    // ตัดจากงบก้อนไหน
    public function budgetItem()
    {
        return $this->belongsTo(BudgetItem::class);
    }
}
