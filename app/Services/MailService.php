<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class MailService
{
    /**
     * Send email to assigned employee when a new task is created.
     */
    public static function sendTaskAssignedMail(User $employee, Task $task, ?User $assignedBy = null): void
    {
        try {
            if (empty($employee->email) || !filter_var($employee->email, FILTER_VALIDATE_EMAIL)) {
                Log::info("Task assignment email skipped: User #{$employee->id} has no valid email address.");
                return;
            }

            $creatorName = $assignedBy ? $assignedBy->displayName() : ($task->creator ? $task->creator->displayName() : 'Management / Admin');
            $deptName = $task->department ? $task->department->name : '-';
            $dueDateStr = $task->due_date ? $task->due_date->format('d M Y') : 'Not Specified';
            if ($task->due_time) {
                $dueDateStr .= ' at ' . $task->due_time;
            }
            $priorityStr = strtoupper($task->priority);
            $companyName = Setting::current()->company_name ?: config('app.name', 'HRMS');

            $subject = "New Task Assigned: {$task->title} [{$priorityStr} Priority]";

            $htmlContent = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; background: #ffffff;'>
                <div style='background: #111827; padding: 20px 24px; color: #ffffff;'>
                    <h2 style='margin: 0; font-size: 18px; letter-spacing: 0.5px;'>{$companyName}</h2>
                    <p style='margin: 4px 0 0 0; font-size: 13px; color: #94a3b8;'>Task Management System</p>
                </div>
                <div style='padding: 24px;'>
                    <p style='font-size: 14px; color: #334155; margin-top: 0;'>Hello <b>{$employee->displayName()}</b>,</p>
                    <p style='font-size: 14px; color: #334155;'>You have been assigned a new task by <b>{$creatorName}</b>.</p>
                    
                    <div style='background: #f8fafc; border-left: 4px solid #3b82f6; padding: 14px 18px; border-radius: 6px; margin: 18px 0;'>
                        <h3 style='margin: 0 0 8px 0; font-size: 16px; color: #0f172a;'>{$task->title}</h3>
                        " . ($task->description ? "<p style='margin: 0; font-size: 13px; color: #475569; line-height: 1.5;'>" . nl2br(e($task->description)) . "</p>" : "<p style='margin: 0; font-size: 13px; color: #94a3b8; font-style: italic;'>No description provided.</p>") . "
                    </div>

                    <table style='width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 13px;'>
                        <tr>
                            <td style='padding: 8px 0; color: #64748b; width: 130px;'>Priority:</td>
                            <td style='padding: 8px 0; font-weight: bold; color: " . ($task->priority === 'high' ? '#dc2626' : ($task->priority === 'medium' ? '#d97706' : '#16a34a')) . ";'>{$priorityStr}</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px 0; color: #64748b;'>Department:</td>
                            <td style='padding: 8px 0; color: #1e293b; font-weight: 600;'>{$deptName}</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px 0; color: #64748b;'>Deadline:</td>
                            <td style='padding: 8px 0; color: #1e293b; font-weight: 600;'>{$dueDateStr}</td>
                        </tr>
                        " . ($task->client ? "<tr><td style='padding: 8px 0; color: #64748b;'>Client:</td><td style='padding: 8px 0; color: #1e293b; font-weight: 600;'>" . e($task->client) . "</td></tr>" : "") . "
                    </table>

                    <div style='text-align: center; margin: 25px 0 10px 0;'>
                        <a href='" . url('/tasks/' . $task->id) . "' style='background: #2563eb; color: #ffffff; padding: 10px 24px; text-decoration: none; border-radius: 8px; font-weight: bold; font-size: 13px; display: inline-block;'>View Task Details</a>
                    </div>
                </div>
                <div style='background: #f1f5f9; padding: 12px 24px; text-align: center; font-size: 11px; color: #64748b; border-top: 1px solid #e2e8f0;'>
                    This is an automated notification. Please update your progress in the portal or mobile app.
                </div>
            </div>";

            Mail::html($htmlContent, function ($message) use ($employee, $subject) {
                $message->to($employee->email, $employee->displayName())
                    ->subject($subject);
            });

            Log::info("Task assignment email successfully sent to {$employee->email} for Task #{$task->id}");
        } catch (\Throwable $e) {
            Log::error("Failed to send task assigned email to user #{$employee->id}: " . $e->getMessage());
        }
    }

    /**
     * Send email reminder for a task.
     */
    public static function sendTaskReminderMail(User $employee, Task $task, ?User $sender = null): void
    {
        try {
            if (empty($employee->email) || !filter_var($employee->email, FILTER_VALIDATE_EMAIL)) {
                Log::info("Task reminder email skipped: User #{$employee->id} has no valid email address.");
                return;
            }

            $senderName = $sender ? $sender->displayName() : 'Management';
            $dueDateStr = $task->due_date ? $task->due_date->format('d M Y') : 'N/A';
            if ($task->due_time) {
                $dueDateStr .= ' at ' . $task->due_time;
            }
            $companyName = Setting::current()->company_name ?: config('app.name', 'HRMS');
            $subject = "Task Reminder: {$task->title} [Action Required]";

            $htmlContent = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; border: 1px solid #fed7aa; border-radius: 12px; overflow: hidden; background: #ffffff;'>
                <div style='background: #ea580c; padding: 20px 24px; color: #ffffff;'>
                    <h2 style='margin: 0; font-size: 18px; letter-spacing: 0.5px;'>{$companyName}</h2>
                    <p style='margin: 4px 0 0 0; font-size: 13px; color: #ffedd5;'>⚠️ Task Progress Reminder</p>
                </div>
                <div style='padding: 24px;'>
                    <p style='font-size: 14px; color: #334155; margin-top: 0;'>Hello <b>{$employee->displayName()}</b>,</p>
                    <p style='font-size: 14px; color: #334155;'><b>{$senderName}</b> has requested an urgent update on the task assigned to you.</p>
                    
                    <div style='background: #fff7ed; border-left: 4px solid #ea580c; padding: 14px 18px; border-radius: 6px; margin: 18px 0;'>
                        <h3 style='margin: 0 0 8px 0; font-size: 16px; color: #9a3412;'>{$task->title}</h3>
                        <p style='margin: 0; font-size: 13px; color: #7c2d12;'>Deadline: <b>{$dueDateStr}</b> | Current Status: <b>" . strtoupper(str_replace('_', ' ', $task->status)) . "</b></p>
                    </div>

                    <div style='text-align: center; margin: 25px 0 10px 0;'>
                        <a href='" . url('/tasks/' . $task->id) . "' style='background: #ea580c; color: #ffffff; padding: 10px 24px; text-decoration: none; border-radius: 8px; font-weight: bold; font-size: 13px; display: inline-block;'>Update Task Status / Reply</a>
                    </div>
                </div>
                <div style='background: #f1f5f9; padding: 12px 24px; text-align: center; font-size: 11px; color: #64748b; border-top: 1px solid #e2e8f0;'>
                    Please update your task progress as soon as possible.
                </div>
            </div>";

            Mail::html($htmlContent, function ($message) use ($employee, $subject) {
                $message->to($employee->email, $employee->displayName())
                    ->subject($subject);
            });

            Log::info("Task reminder email successfully sent to {$employee->email} for Task #{$task->id}");
        } catch (\Throwable $e) {
            Log::error("Failed to send task reminder email to user #{$employee->id}: " . $e->getMessage());
        }
    }
}
