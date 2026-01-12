<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Project;
use App\Models\Program;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // 1. ตัวเลขสรุปด้านบน
        $totalProjects = Project::count();
        $totalBudget = Program::sum('total_budget');
        $ongoingProjects = Project::where('status', 'ongoing')->count();
        $lateProjects = Project::where('status', 'late')->count();

        // 2. ข้อมูลกราฟวงกลม
        $projectStatus = Project::select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->get();

        // 3. ข้อมูลกราฟ S-Curve
        $sCurveData = [];
        $sampleProject = Project::with('progressHistory')->first();

        // เช็คว่ามีข้อมูลประวัติหรือไม่
        if ($sampleProject && $sampleProject->progressHistory->isNotEmpty()) {
            $sCurveData = $sampleProject->progressHistory->map(function ($history) {
                return [
                    // ตรงนี้จะไม่ Error แล้ว เพราะเราแก้ Model แล้ว
                    'date' => $history->date_logged->format('Y-m-d'),
                    'planned' => $history->planned_percent,
                    'actual' => $history->actual_percent,
                ];
            });
        }

        // 4. รายชื่อโครงการล่าสุด
        $recentProjects = Project::with(['manager']) // โหลด manager มารอไว้
            ->orderBy('updated_at', 'desc')
            ->take(5)
            ->get()
            ->map(function ($project) {
                // ใช้ optional() เพื่อความปลอดภัยสูงสุด (ถ้า manager เป็น null ก็จะไม่พัง)
                $managerName = optional($project->manager)->name ?? 'ไม่ระบุ';

                return [
                    'id' => $project->id,
                    'name' => $project->name,
                    'manager' => $managerName, // ใช้ตัวแปรที่เช็คแล้ว
                    'budget' => number_format($project->contract_amount),
                    'status' => $project->status,
                    'progress' => $project->progress_actual ?? 0,
                ];
            });

        return response()->json([
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
