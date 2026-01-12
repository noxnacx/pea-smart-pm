<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FiscalYear extends Model
{
    use HasFactory;
    protected $guarded = []; // ปลดล็อกให้บันทึกได้ทุกฟิลด์

    // ปีงบนี้ มีแผนงานอะไรบ้าง
    public function programs()
    {
        return $this->hasMany(Program::class);
    }
}
