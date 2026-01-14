<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Task;

class TaskAssigned extends Notification implements ShouldQueue
{
    use Queueable;

    protected $task;

    public function __construct(Task $task)
    {
        $this->task = $task;
    }

    // กำหนดช่องทางส่ง (Mail + Database)
    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    // 📧 1. ส่งอีเมล (Gmail)
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('📅 งานใหม่: ' . $this->task->name)
            ->greeting('สวัสดีครับคุณ ' . $notifiable->name)
            ->line('คุณได้รับมอบหมายให้รับผิดชอบงานใหม่ในระบบ PEA Smart PM')
            ->line('📝 ชื่องาน: ' . $this->task->name)
            ->line('📁 โครงการ: ' . ($this->task->project->name ?? '-'))
            ->line('📅 กำหนดส่ง: ' . \Carbon\Carbon::parse($this->task->end_date)->format('d/m/Y'))
            ->action('ดูรายละเอียดงาน', url('/project/' . $this->task->project_id))
            ->line('ขอบคุณครับ, ทีมงาน PEA Smart PM');
    }

    // 🔔 2. แจ้งเตือนบนเว็บ (Database)
    public function toArray($notifiable)
    {
        return [
            'task_id' => $this->task->id,
            'project_id' => $this->task->project_id,
            'message' => 'คุณได้รับมอบหมายงาน: ' . $this->task->name,
            'link' => '/project/' . $this->task->project_id,
            'created_at' => now(),
        ];
    }
}
