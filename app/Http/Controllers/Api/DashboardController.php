<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Project;
use App\Models\Task;
use App\Models\Payment; // ✅ เพิ่ม model Payment
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // --- 1. Query โครงการตามสิทธิ์ ---
        $projectQuery = Project::query();
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

        // --- 2. สรุปตัวเลข (Stats) ---
        $totalBudget = $projects->sum('contract_amount');

        // ✅ เพิ่ม: คำนวณยอดเบิกจ่ายจริง (Total Paid)
        $totalPaid = Payment::whereIn('project_id', $projectIds)->sum('amount');
        $budgetUsagePercent = $totalBudget > 0 ? ($totalPaid / $totalBudget) * 100 : 0;

        // ✅ เพิ่ม: งานที่ฉันต้องทำ (My Pending Tasks)
        $myPendingTasks = Task::whereHas('users', function($q) use ($user) {
            $q->where('users.id', $user->id);
        })->where('progress', '<', 100)->count();

        $stats = [
            'total_projects' => $projects->count(),
            'total_budget' => $totalBudget,
            'total_paid' => $totalPaid, // ส่งยอดจ่ายไปด้วย
            'budget_usage' => round($budgetUsagePercent, 2),
            'ongoing' => $projects->where('status', 'ongoing')->count(),
            'late' => $projects->where('status', 'late')->count(),
            'completed' => $projects->where('status', 'completed')->count(),
            'my_pending_tasks' => $myPendingTasks // ส่งจำนวนงานค้าง
        ];

        // --- 3. กราฟ S-Curve (ฉลาดขึ้น: เลือกโครงการที่งบเยอะสุดที่ยังไม่เสร็จ) ---
        $highlightProject = Project::whereIn('id', $projectIds)
                            ->where('status', '!=', 'draft')
                            ->orderBy('contract_amount', 'desc') // เอางบเยอะสุด
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

        // --- 4. โครงการล่าช้า (Critical Projects) ---
        $criticalProjects = $projects->where('status', 'late')->take(5)->map(function($p) {
             return [
                 'id' => $p->id,
                 'name' => $p->name,
                 'progress' => $p->progress_actual
             ];
        })->values();

        // --- 5. โครงการล่าสุด ---
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
            'chart_scurve' => $sCurveData,
            'chart_title' => $highlightProjectName, // ส่งชื่อโครงการของกราฟไปด้วย
            'critical_projects' => $criticalProjects,
            'recent_projects' => $recentProjects,
        ]);
    }
}
