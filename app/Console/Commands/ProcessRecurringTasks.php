<?php

namespace App\Console\Commands;

use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ProcessRecurringTasks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tasks:process-recurring';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process and create next iterations for recurring tasks';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking for recurring tasks to process...');

        $now = now('Asia/Kolkata');
        $recurringTasks = Task::where('status', 'completed')
            ->whereNotNull('repeat_type')
            ->where('repeat_type', '!=', 'none')
            ->get();

        $count = 0;
        foreach ($recurringTasks as $task) {
            $baseDate = $task->due_date ? Carbon::parse($task->due_date) : $task->created_at;
            $nextDate = match ($task->repeat_type) {
                'daily' => $baseDate->copy()->addDay(),
                'weekly' => $baseDate->copy()->addWeek(),
                'monthly' => $baseDate->copy()->addMonth(),
                'quarterly' => $baseDate->copy()->addMonths(3),
                'half_yearly' => $baseDate->copy()->addMonths(6),
                'yearly' => $baseDate->copy()->addYear(),
                default => null,
            };

            if (!$nextDate) {
                continue;
            }

            // Check if next recurrence already exists to avoid duplicate spawning
            $alreadyExists = Task::where('title', $task->title)
                ->where('assigned_to', $task->assigned_to)
                ->whereDate('due_date', $nextDate->toDateString())
                ->exists();

            if (!$alreadyExists) {
                $newTask = Task::create([
                    'title' => $task->title,
                    'description' => $task->description,
                    'client' => $task->client,
                    'status' => 'pending',
                    'priority' => $task->priority,
                    'is_pinned' => false,
                    'due_date' => $nextDate->toDateString(),
                    'due_time' => $task->due_time,
                    'repeat_type' => $task->repeat_type,
                    'department_id' => $task->department_id,
                    'assigned_to' => $task->assigned_to,
                    'created_by' => $task->created_by,
                ]);

                if ($task->assignees()->exists()) {
                    $newTask->assignees()->sync($task->assignees->pluck('id'));
                }
                if ($task->others()->exists()) {
                    $newTask->others()->sync($task->others->pluck('id'));
                }

                $newTask->logHistory('created', "Auto-created recurring task ({$task->repeat_type}) from Task #{$task->id} for due date " . $nextDate->format('d M Y'), 1);
                $count++;
            }
        }

        $this->info("Processed recurring tasks. Spawned {$count} new task iterations.");
        return 0;
    }
}
