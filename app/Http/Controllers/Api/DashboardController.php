<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Project;
use App\Models\Program;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // เริ่มต้น Query
        $query = Project::query();

        // 🔒 LOGIC กรองสิทธิ์: ถ้าไม่ใช่ Admin และไม่ใช่ Program Manager
        // ให้ดูได้แค่ "โครงการที่เป็น PM" หรือ "โครงการที่เป็นสมาชิกทีม"
        if ($user->role !== 'admin' && $user->role !== 'program_manager') {
            $query->where(function($q) use ($user) {
                // 1. เป็น PM ของโครงการนั้น
                $q->where('manager_id', $user->id)
                  // 2. หรือ เป็นสมาชิกในทีม (เช็คผ่าน Relation members)
                  ->orWhereHas('members', function($m) use ($user) {
                      $m->where('user_id', $user->id);
                  });
            });
        }

        // ดึงข้อมูลโครงการตามสิทธิ์ที่กรองแล้ว
        $projects = $query->get();

        // 1. คำนวณตัวเลขสรุป (จากข้อมูลที่กรองแล้ว)
        $totalProjects = $projects->count();
        $totalBudget = $projects->sum('contract_amount'); // คำนวณจากโครงการที่เห็น
        $ongoingProjects = $projects->where('status', 'ongoing')->count();
        $lateProjects = $projects->where('status', 'late')->count();

        // 2. ข้อมูลกราฟวงกลม (สถานะ) - ต้องนับจาก Collection เพราะ Query ถูกกรองมาแล้ว
        $projectStatus = [
            ['status' => 'ongoing', 'total' => $ongoingProjects],
            ['status' => 'late', 'total' => $lateProjects],
            ['status' => 'completed', 'total' => $projects->where('status', 'completed')->count()],
            ['status' => 'draft', 'total' => $projects->where('status', 'draft')->count()],
        ];

        // 3. ข้อมูลกราฟ S-Curve (ดึงตัวอย่างจากโครงการแรกที่เห็น)
        $sCurveData = [];
        // เลือกโครงการที่มีประวัติความก้าวหน้ามาโชว์สักอัน (หรืออันที่ Active ล่าสุด)
        $sampleProject = Project::whereIn('id', $projects->pluck('id'))
                            ->with('progressHistory')
                            ->whereHas('progressHistory')
                            ->first();

        if ($sampleProject) {
            $sCurveData = $sampleProject->progressHistory->map(function ($history) {
                return [
                    'date' => $history->date_logged->format('Y-m-d'),
                    'planned' => $history->planned_percent,
                    'actual' => $history->actual_percent,
                ];
            });
        }

        // 4. รายชื่อโครงการล่าสุด (5 อันดับแรกของผู้ใช้นั้น)
        $recentProjects = Project::with(['manager'])
            ->whereIn('id', $projects->pluck('id')) // กรองตาม ID ที่มีสิทธิ์
            ->orderBy('updated_at', 'desc')
            ->take(5)
            ->get()
            ->map(function ($project) {
                $managerName = optional($project->manager)->name ?? 'ไม่ระบุ';
                return [
                    'id' => $project->id,
                    'name' => $project->name,
                    'manager' => $managerName,
                    'budget' => number_format($project->contract_amount),
                    'status' => $project->status,
                    'progress' => $project->progress_actual ?? 0,
                ];
            });

        return response()->json([
            'role' => $user->role, // ส่ง Role กลับไปบอกหน้าบ้าน
            'stats' => [
                'total_projects' => $totalProjects,
                'total_budget' => $totalBudget,
                'ongoing' => $ongoingProjects,
                'late' => $lateProjects,
            ],
            'chart_status' => $projectStatus,
            'chart_scurve' => $sCurveData,
            'recent_projects' => $recentProjects,
        ]);
    }
}
