<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\FiscalYear;
use App\Models\Program;
use App\Models\Project;
use App\Models\BudgetItem;
use App\Models\Task;
use App\Models\ProjectProgressHistory;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        // 1. สร้าง User สำหรับ Login
        $admin = User::create([
            'name' => 'Admin PEA',
            'email' => 'admin@pea.co.th',
            'password' => Hash::make('password'),
        ]);

        $pmUser = User::create([
            'name' => 'สมชาย ใจดี (PM)',
            'email' => 'somchai@pea.co.th',
            'password' => Hash::make('password'),
        ]);

        // 2. สร้างปีงบประมาณ
        $fiscalYear = FiscalYear::create([
            'name' => '2567',
            'start_date' => '2023-10-01',
            'end_date' => '2024-09-30',
            'is_active' => true,
        ]);

        // 3. สร้างแผนงานหลัก (Program)
        $program = Program::create([
            'name' => 'แผนงานพัฒนาระบบไฟฟ้าอัจฉริยะ ระยะที่ 1',
            'fiscal_year_id' => $fiscalYear->id,
            'owner_id' => $admin->id,
            'total_budget' => 500000000, // 500 ล้าน
            'strategic_goal' => 'ยุทธศาสตร์ที่ 1: Grid Modernization',
        ]);

        // 4. สร้างโครงการย่อย (Projects)
        $projectsData = [
            [
                'name' => 'โครงการก่อสร้างสถานีไฟฟ้าแรงสูง A',
                'code' => 'PEA-67-001',
                'budget' => 120000000,
                'status' => 'ongoing',
                'progress' => 45,
                'start_date' => Carbon::now()->subMonths(5),
            ],
            [
                'name' => 'โครงการวางระบบ Fiber Optic เคเบิลใต้น้ำ',
                'code' => 'PEA-67-002',
                'budget' => 85000000,
                'status' => 'late', // แกล้งให้ล่าช้า
                'progress' => 30,
                'start_date' => Carbon::now()->subMonths(8),
            ],
            [
                'name' => 'โครงการปรับปรุงระบบ ERP องค์กร',
                'code' => 'PEA-67-003',
                'budget' => 40000000,
                'status' => 'completed',
                'progress' => 100,
                'start_date' => Carbon::now()->subYear(1),
            ],
        ];

        foreach ($projectsData as $data) {
            // *** แก้ไขจุดสำคัญตรงนี้ครับ เพิ่ม progress_actual ***
            $project = Project::create([
                'program_id' => $program->id,
                'code' => $data['code'],
                'name' => $data['name'],
                'contract_number' => 'CN-' . rand(1000, 9999) . '/2567',
                'status' => $data['status'],
                'progress_actual' => $data['progress'], // <--- บรรทัดที่เพิ่มใหม่
                'start_date' => $data['start_date'],
                'end_date' => $data['start_date']->copy()->addMonths(12),
                'contract_amount' => $data['budget'],
                'manager_id' => $pmUser->id,
            ]);

            // 4.1 ใส่รายการงบประมาณ
            BudgetItem::create([
                'project_id' => $project->id,
                'name' => 'ค่าจ้างเหมาเบ็ดเสร็จ',
                'amount' => $data['budget'] * 0.8,
            ]);

            // 4.2 ใส่ Task ตัวอย่าง
            Task::create([
                'project_id' => $project->id,
                'name' => 'สำรวจหน้างานและออกแบบ',
                'start_date' => $project->start_date,
                'end_date' => $project->start_date->copy()->addMonth(),
                'progress' => 100,
                'weight' => 10,
            ]);

            // 4.3 สร้างข้อมูลกราฟ S-Curve ย้อนหลัง
            for ($i = 6; $i >= 0; $i--) {
                $date = Carbon::now()->subMonths($i);

                $planned = 10 + ((6 - $i) * 10);
                $actual = ($data['status'] == 'late') ? $planned - 15 : $planned - 2;

                ProjectProgressHistory::create([
                    'project_id' => $project->id,
                    'date_logged' => $date,
                    'planned_percent' => min($planned, 100),
                    'actual_percent' => max(0, min($actual, $data['progress'])),
                ]);
            }
        }
    }
}
