<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Strategy extends Model
{
    use HasFactory;

    protected $guarded = [];

    // ยุทธศาสตร์ มีหลาย แผนงาน
    public function programs()
    {
        return $this->hasMany(Program::class);
    }
}
