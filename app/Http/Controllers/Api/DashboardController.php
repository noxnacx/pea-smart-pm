<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Project;
use App\Models\Task;
use App\Models\Payment;
use App\Models\Program; // ✅ เพิ่มการใช้งาน Model Program
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // --- 1. Query โครงการตามสิทธิ์ ---
        $projectQuery = Project::with('program'); // ดึงชื่อแผนงานมาด้วย

        if ($user->role !== 'admin' && $user->role !== 'program_manager') {
            $projectQuery->where(function($q) use ($user) {
                $q->where('manager_id', $user->id)
                  ->orWhereHas('members', function($m) use ($user) {
                      $m->where('user_id', $user->id);
                  });
            });
        }
        $projects = $projectQuery->get();
        $projectIds = $projects->pluck('id');

        // --- 2. สรุปตัวเลข (Stats Cards) ---
        $totalBudget = $projects->sum('contract_amount');
        $totalPaid = Payment::whereIn('project_id', $projectIds)->sum('amount');
        $budgetUsagePercent = $totalBudget > 0 ? ($totalPaid / $totalBudget) * 100 : 0;
        $myPendingTasks = Task::whereHas('users', function($q) use ($user) {
            $q->where('users.id', $user->id);
        })->where('progress', '<', 100)->count();

        $stats = [
            'total_projects' => $projects->count(),
            'total_budget' => $totalBudget,
            'total_paid' => $totalPaid,
            'budget_usage' => round($budgetUsagePercent, 2),
            'ongoing' => $projects->where('status', 'ongoing')->count(),
            'late' => $projects->where('status', 'late')->count(),
            'completed' => $projects->where('status', 'completed')->count(),
            'my_pending_tasks' => $myPendingTasks
        ];

        // --- 3. ข้อมูลกราฟ Donut (สัดส่วนสถานะโครงการ) ---
        $donutData = [
            'labels' => ['เสร็จสิ้น', 'กำลังดำเนินการ', 'ล่าช้า', 'ร่าง'],
            'series' => [
                $projects->where('status', 'completed')->count(),
                $projects->where('status', 'ongoing')->count(),
                $projects->where('status', 'late')->count(),
                $projects->where('status', 'draft')->count(),
            ]
        ];

        // --- 4. [UPDATED] ข้อมูลกราฟแท่ง (เปรียบเทียบ งบแผน vs งบใช้จริง Top 5) ---
        // ดึงข้อมูลแผนงานทั้งหมด พร้อมรวมยอด contract_amount ของโครงการภายในแผนงานนั้น
        $programsData = Program::with('projects')
            ->get()
            ->map(function($program) {
                return [
                    'name' => $program->name,
                    'total_budget' => $program->total_budget, // งบเต็มตามแผน
                    'used_budget' => $program->projects->sum('contract_amount') // งบที่ถูกใช้สร้างโครงการแล้ว
                ];
            })
            ->sortByDesc('total_budget') // เรียงลำดับตามงบแผนงานจากมากไปน้อย
            ->take(5); // เอาแค่ 5 อันดับแรก

        $barData = [
            'categories' => $programsData->pluck('name')->values(),
            'series' => [
                [
                    'name' => 'งบตามแผน (Total Plan)',
                    'data' => $programsData->pluck('total_budget')->values()
                ],
                [
                    'name' => 'ใช้สร้างโครงการแล้ว (Allocated)',
                    'data' => $programsData->pluck('used_budget')->values()
                ]
            ]
        ];

        // --- 5. กราฟ S-Curve (เหมือนเดิม) ---
        $highlightProject = Project::whereIn('id', $projectIds)
                            ->where('status', '!=', 'draft')
                            ->orderBy('contract_amount', 'desc')
                            ->with('progressHistory')
                            ->first();

        $sCurveData = [];
        $highlightProjectName = "";

        if ($highlightProject && $highlightProject->progressHistory->isNotEmpty()) {
            $highlightProjectName = $highlightProject->name;
            $sCurveData = $highlightProject->progressHistory->map(function ($history) {
                return [
                    'date' => $history->date_logged->format('Y-m-d'),
                    'planned' => $history->planned_percent,
                    'actual' => $history->actual_percent,
                ];
            });
        }

        // --- 6. โครงการล่าช้า (Critical Projects) ---
        $criticalProjects = $projects->where('status', 'late')->take(5)->map(function($p) {
             return [
                 'id' => $p->id,
                 'name' => $p->name,
                 'progress' => $p->progress_actual
             ];
        })->values();

        // --- 7. โครงการล่าสุด ---
        $recentProjects = Project::with(['manager'])
            ->whereIn('id', $projectIds)
            ->orderBy('updated_at', 'desc')
            ->take(5)
            ->get()
            ->map(function ($project) {
                return [
                    'id' => $project->id,
                    'name' => $project->name,
                    'manager' => optional($project->manager)->name ?? '-',
                    'status' => $project->status,
                    'progress' => $project->progress_actual ?? 0,
                    'due_date' => $project->end_date ? $project->end_date->format('d/m/Y') : '-'
                ];
            });

        return response()->json([
            'role' => $user->role,
            'stats' => $stats,
            'chart_donut' => $donutData,
            'chart_bar' => $barData,     // ส่งข้อมูลกราฟแท่งแบบใหม่
            'chart_scurve' => $sCurveData,
            'chart_title' => $highlightProjectName,
            'critical_projects' => $criticalProjects,
            'recent_projects' => $recentProjects,
        ]);
    }
}
