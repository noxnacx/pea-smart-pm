<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Project;
use App\Models\Program;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // 1. ตัวเลขสรุปด้านบน (Card Stats)
        $totalProjects = Project::count();
        $totalBudget = Program::sum('total_budget');
        $ongoingProjects = Project::where('status', 'ongoing')->count();
        $lateProjects = Project::where('status', 'late')->count();

        // 2. ข้อมูลกราฟวงกลม (สถานะโครงการ)
        $projectStatus = Project::select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->get();

        // 3. ข้อมูลกราฟ S-Curve (เอาโครงการแรกมาโชว์เป็นตัวอย่างก่อน)
        $sCurveData = [];
        $sampleProject = Project::with('progressHistory')->first();
        if ($sampleProject) {
            $sCurveData = $sampleProject->progressHistory->map(function ($history) {
                return [
                    'date' => $history->date_logged->format('Y-m-d'), // จัดรูปแบบวันที่
                    'planned' => $history->planned_percent,
                    'actual' => $history->actual_percent,
                ];
            });
        }

        // 4. รายชื่อโครงการล่าสุด (Watchlist)
        $recentProjects = Project::with(['manager', 'program'])
            ->orderBy('updated_at', 'desc')
            ->take(5)
            ->get()
            ->map(function ($project) {
                return [
                    'name' => $project->name,
                    'manager' => $project->manager->name,
                    'budget' => number_format($project->contract_amount),
                    'status' => $project->status,
                    'progress' => $project->progress_actual ?? 0, // ถ้าไม่มีค่าให้เป็น 0
                ];
            });

        // ส่งข้อมูลกลับไปเป็น JSON
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
