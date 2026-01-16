<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Spatie\Activitylog\Models\Activity;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    // 1. ดึง Log รวมทั้งระบบ (สำหรับหน้า Admin Audit)
    public function index()
    {
        return Activity::with('causer') // causer คือ User คนทำรายการ
            ->latest()
            ->paginate(20);
    }

    // 2. ดึง Log เฉพาะของ Project หนึ่งๆ (สำหรับหน้า History Modal)
    public function projectLogs($projectId)
    {
        return Activity::where('subject_type', 'App\Models\Project')
            ->where('subject_id', $projectId)
            ->with('causer')
            ->latest()
            ->get();
    }
}
