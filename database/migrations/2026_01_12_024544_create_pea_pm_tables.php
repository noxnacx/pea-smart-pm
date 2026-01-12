<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // 1. หน่วยงาน (Departments/Divisions)
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // เช่น "กองบริหารโครงการ", "ฝ่ายบัญชี"
            $table->string('code')->nullable();
            $table->timestamps();
        });

        // 2. ปีงบประมาณ (Fiscal Years) - หัวใจของรัฐวิสาหกิจ
        Schema::create('fiscal_years', function (Blueprint $table) {
            $table->id();
            $table->string('name', 4); // "2567"
            $table->date('start_date'); // 2023-10-01
            $table->date('end_date');   // 2024-09-30
            $table->boolean('is_active')->default(false); // ปีปัจจุบันหรือไม่
            $table->timestamps();
        });

        // 3. แผนงานหลัก (Programs) - ระดับผู้บริหารดู
        Schema::create('programs', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('fiscal_year_id')->constrained(); // ผูกกับปีงบ
            $table->foreignId('owner_id')->nullable()->constrained('users'); // ผอ. ผู้ดูแล
            $table->decimal('total_budget', 20, 2)->default(0); // งบรวมทั้งแผน
            $table->text('strategic_goal')->nullable(); // ความเชื่อมโยงยุทธศาสตร์องค์กร
            $table->timestamps();
        });

        // 4. โครงการ (Projects) - ระดับปฏิบัติการ
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained()->onDelete('cascade'); // อยู่ใต้แผนงานไหน
            $table->string('code')->unique(); // รหัสโครงการ เช่น PEA-67-001
            $table->string('name');
            $table->string('contract_number')->nullable(); // เลขที่สัญญา

            // สถานะ (สำคัญมาก)
            $table->enum('status', ['draft', 'approval', 'ongoing', 'late', 'completed', 'cancel'])->default('draft');

            // เก็บ % ความคืบหน้าโครงการ
            $table->integer('progress_actual')->default(0);

            // วันที่สำคัญ
            $table->date('start_date');
            $table->date('end_date');
            $table->date('actual_start_date')->nullable();
            $table->date('actual_end_date')->nullable();

            // ข้อมูลการเงินสรุป
            $table->decimal('contract_amount', 20, 2)->default(0); // มูลค่าสัญญา
            $table->decimal('paid_amount', 20, 2)->default(0); // จ่ายจริงสะสม

            $table->foreignId('manager_id')->nullable()->constrained('users'); // PM ผู้รับผิดชอบ
            $table->timestamps();
        });

        // 5. รายการงบประมาณ (Budget Items) - แยกหมวดค่าใช้จ่าย
        Schema::create('budget_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->string('cost_center')->nullable(); // รหัสศูนย์ต้นทุน
            $table->string('name'); // "ค่าจ้างที่ปรึกษา", "ค่าครุภัณฑ์"
            $table->decimal('amount', 20, 2); // ยอดงบที่ตั้งไว้
            $table->timestamps();
        });

        // 6. การเบิกจ่าย (Transactions/Expenses) - ตัดงบจริง
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->foreignId('budget_item_id')->constrained(); // ตัดจากงบก้อนไหน
            $table->string('description'); // รายละเอียดการจ่าย
            $table->string('invoice_number')->nullable();
            $table->decimal('amount', 20, 2);
            $table->date('payment_date');
            $table->enum('status', ['pending', 'approved', 'paid'])->default('pending');
            $table->timestamps();
        });

        // 7. งานย่อย (Tasks / WBS) - สำหรับทำ Gantt Chart
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('parent_id')->nullable(); // เพื่อทำ Sub-task ซ้อนกันได้
            $table->string('name');

            // น้ำหนักงาน (ใช้คำนวณ S-Curve)
            $table->decimal('weight', 5, 2)->default(0); // เช่น 10.5%
            $table->integer('progress')->default(0); // 0-100%

            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamps();
        });

        // 8. ความเสี่ยง (Risks)
        Schema::create('risks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->string('description');
            $table->integer('impact')->default(1); // ผลกระทบ (1-5)
            $table->integer('chance')->default(1); // โอกาสเกิด (1-5)
            $table->text('mitigation_plan')->nullable(); // แผนรับมือ
            $table->enum('status', ['active', 'mitigated', 'closed'])->default('active');
            $table->timestamps();
        });

        // 9. ประวัติความก้าวหน้า (Snapshot for S-Curve Graph)
        Schema::create('project_progress_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->date('date_logged');
            $table->decimal('planned_percent', 5, 2)->nullable();
            $table->decimal('actual_percent', 5, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        // ลบตารางเมื่อ Rollback (ต้องลบย้อนศร เพื่อไม่ให้ติด Foreign Key)
        Schema::dropIfExists('project_progress_history');
        Schema::dropIfExists('risks');
        Schema::dropIfExists('tasks');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('budget_items');
        Schema::dropIfExists('projects');
        Schema::dropIfExists('programs');
        Schema::dropIfExists('fiscal_years');
        Schema::dropIfExists('departments');
    }
};
