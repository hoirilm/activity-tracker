<?php

namespace App\Console\Commands;

use App\Models\Notification;
use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CheckTaskDeadlines extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-task-deadlines';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check task deadlines and send in-app notifications for due today and overdue tasks';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $todayCount = 0;
        $overdueCount = 0;

        // Fetch active tasks with deadlines (excluding done & archived)
        $tasks = Task::whereNotIn('status', [Task::STATUS_DONE, Task::STATUS_ARCHIVED])
            ->whereNotNull('due_at')
            ->with(['user'])
            ->get();

        foreach ($tasks as $task) {
            $user = $task->user;
            if (! $user) {
                continue;
            }

            // Check if notification already sent today for this task
            $notifiedToday = Notification::where('user_id', $user->id)
                ->where('title', 'like', "%{$task->title}%")
                ->whereDate('created_at', Carbon::today())
                ->exists();

            if ($notifiedToday) {
                continue;
            }

            if ($task->isOverdue()) {
                $user->notifications()->create([
                    'title' => "⚠️ Task Overdue: {$task->title}",
                    'body' => "Task '{$task->title}' was due on ".$task->due_at->format('d M Y H:i').' and is currently overdue.',
                    'type' => 'danger',
                ]);
                $overdueCount++;
            } elseif ($task->isDueToday()) {
                $timeStr = $task->due_at->format('H:i') !== '00:00' ? " at {$task->due_at->format('H:i')}" : '';
                $user->notifications()->create([
                    'title' => "⏰ Task Due Today: {$task->title}",
                    'body' => "Task '{$task->title}' is due today{$timeStr}. Don't forget to complete it!",
                    'type' => 'warning',
                ]);
                $todayCount++;
            }
        }

        $this->info("Task deadline check completed. Sent {$todayCount} due today and {$overdueCount} overdue notifications.");

        return Command::SUCCESS;
    }
}
