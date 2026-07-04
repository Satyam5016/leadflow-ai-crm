<?php

namespace App\Console\Commands;

use App\Models\Task;
use App\Notifications\TaskDueReminderNotification;
use Illuminate\Console\Command;

class SendTaskDueReminders extends Command
{
    protected $signature = 'crm:send-task-reminders';

    protected $description = 'Notify users about CRM tasks due in the next day.';

    public function handle(): int
    {
        $tasks = Task::with('assignedTo')
            ->whereNotNull('assigned_to_id')
            ->where('status', '!=', 'completed')
            ->whereBetween('due_date', [now(), now()->addDay()])
            ->get();

        $tasks->each(fn (Task $task) => $task->assignedTo?->notify(new TaskDueReminderNotification($task)));
        $this->info("Queued {$tasks->count()} task reminder notifications.");

        return self::SUCCESS;
    }
}
