<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\Task;
use App\Models\TaskReply;
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
            $actionUrl = url('/tasks?open_task=' . $task->id . '&tab=details');

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
                        <a href='{$actionUrl}' style='background: #2563eb; color: #ffffff; padding: 11px 26px; text-decoration: none; border-radius: 8px; font-weight: bold; font-size: 13px; display: inline-block;'>View Task Details</a>
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
            $actionUrl = url('/tasks?open_task=' . $task->id . '&tab=reports');

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
                        <a href='{$actionUrl}' style='background: #ea580c; color: #ffffff; padding: 11px 26px; text-decoration: none; border-radius: 8px; font-weight: bold; font-size: 13px; display: inline-block;'>Update Task Status / Reply</a>
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

    /**
     * Send email notification when a reply/remark is posted on a task.
     */
    public static function sendTaskReplyMail(User $employee, Task $task, TaskReply $reply, ?User $repliedBy = null): void
    {
        try {
            if (empty($employee->email) || !filter_var($employee->email, FILTER_VALIDATE_EMAIL)) {
                Log::info("Task reply email skipped: User #{$employee->id} has no valid email address.");
                return;
            }

            $senderName = $repliedBy ? $repliedBy->displayName() : ($reply->user ? $reply->user->displayName() : 'A team member');
            $companyName = Setting::current()->company_name ?: config('app.name', 'HRMS');
            $dueDateStr = $task->due_date ? $task->due_date->format('d M Y') : 'N/A';
            if ($task->due_time) {
                $dueDateStr .= ' at ' . $task->due_time;
            }
            $statusLabel = strtoupper(str_replace('_', ' ', $task->status));
            $subject = "New Reply on Task: {$task->title} [by {$senderName}]";
            $actionUrl = url('/tasks?open_task=' . $task->id . '&tab=reports');

            $attachmentHtml = '';
            $replyAttachments = $reply->attachments_list ?? ($reply->attachment ? [$reply->attachment] : []);
            if (!empty($replyAttachments)) {
                $attachmentHtml = "
                <div style='margin-top: 12px; font-size: 13px;'>
                    📎 <b>Attachments (" . count($replyAttachments) . "):</b>
                    <ul style='margin: 6px 0 0 0; padding-left: 20px;'>";
                foreach ($replyAttachments as $attPath) {
                    $attUrl = url('/storage/' . $attPath);
                    $attName = basename($attPath);
                    $attachmentHtml .= "<li style='margin-bottom: 4px;'><a href='{$attUrl}' target='_blank' style='color: #4f46e5; text-decoration: underline; font-weight: 600;'>{$attName}</a></li>";
                }
                $attachmentHtml .= "</ul>
                </div>";
            }

            $htmlContent = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; border: 1px solid #e0e7ff; border-radius: 12px; overflow: hidden; background: #ffffff;'>
                <div style='background: #4338ca; padding: 20px 24px; color: #ffffff;'>
                    <h2 style='margin: 0; font-size: 18px; letter-spacing: 0.5px;'>{$companyName}</h2>
                    <p style='margin: 4px 0 0 0; font-size: 13px; color: #c7d2fe;'>💬 Task Discussion & Reply Update</p>
                </div>
                <div style='padding: 24px;'>
                    <p style='font-size: 14px; color: #334155; margin-top: 0;'>Hello <b>{$employee->displayName()}</b>,</p>
                    <p style='font-size: 14px; color: #334155;'><b>{$senderName}</b> has posted a new reply on task: <b>{$task->title}</b>.</p>
                    
                    <div style='background: #f8fafc; border-left: 4px solid #6366f1; padding: 14px 18px; border-radius: 6px; margin: 18px 0;'>
                        <div style='font-size: 12px; font-weight: bold; color: #4338ca; margin-bottom: 6px; text-transform: uppercase;'>
                            {$senderName} wrote:
                        </div>
                        <p style='margin: 0; font-size: 14px; color: #1e293b; line-height: 1.6; white-space: pre-wrap;'>" . nl2br(e($reply->message)) . "</p>
                        {$attachmentHtml}
                    </div>

                    <table style='width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 13px;'>
                        <tr>
                            <td style='padding: 6px 0; color: #64748b; width: 120px;'>Task:</td>
                            <td style='padding: 6px 0; font-weight: 600; color: #1e293b;'>{$task->title}</td>
                        </tr>
                        <tr>
                            <td style='padding: 6px 0; color: #64748b;'>Current Status:</td>
                            <td style='padding: 6px 0; font-weight: 600; color: #0284c7;'>{$statusLabel}</td>
                        </tr>
                        <tr>
                            <td style='padding: 6px 0; color: #64748b;'>Deadline:</td>
                            <td style='padding: 6px 0; font-weight: 600; color: #334155;'>{$dueDateStr}</td>
                        </tr>
                    </table>

                    <div style='text-align: center; margin: 25px 0 10px 0;'>
                        <a href='{$actionUrl}' style='background: #4f46e5; color: #ffffff; padding: 11px 26px; text-decoration: none; border-radius: 8px; font-weight: bold; font-size: 13px; display: inline-block;'>View Reply & Open Discussion</a>
                    </div>
                </div>
                <div style='background: #f1f5f9; padding: 12px 24px; text-align: center; font-size: 11px; color: #64748b; border-top: 1px solid #e2e8f0;'>
                    You can post replies and view the complete audit trail directly on the portal.
                </div>
            </div>";

            Mail::html($htmlContent, function ($message) use ($employee, $subject) {
                $message->to($employee->email, $employee->displayName())
                    ->subject($subject);
            });

            Log::info("Task reply email successfully sent to {$employee->email} for Task #{$task->id}");
        } catch (\Throwable $e) {
            Log::error("Failed to send task reply email to user #{$employee->id}: " . $e->getMessage());
        }
    }

    /**
     * Send email notification when task status is changed.
     */
    public static function sendTaskStatusUpdatedMail(User $employee, Task $task, string $oldStatus, string $newStatus, ?User $updatedBy = null): void
    {
        try {
            if (empty($employee->email) || !filter_var($employee->email, FILTER_VALIDATE_EMAIL)) {
                Log::info("Task status email skipped: User #{$employee->id} has no valid email address.");
                return;
            }

            $senderName = $updatedBy ? $updatedBy->displayName() : 'Management / User';
            $companyName = Setting::current()->company_name ?: config('app.name', 'HRMS');
            
            $statusLabels = [
                'pending' => 'Pending (Not Started)',
                'in_progress' => 'In Progress',
                'completed' => 'Closed / Done',
            ];

            $oldLabel = $statusLabels[$oldStatus] ?? strtoupper($oldStatus);
            $newLabel = $statusLabels[$newStatus] ?? strtoupper($newStatus);

            $subject = "Task Status Updated: {$task->title} [Now {$newLabel}]";
            $actionUrl = url('/tasks?open_task=' . $task->id . '&tab=details');

            $statusColor = match($newStatus) {
                'completed' => '#16a34a',
                'in_progress' => '#d97706',
                default => '#dc2626',
            };

            $htmlContent = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; border: 1px solid #cffafe; border-radius: 12px; overflow: hidden; background: #ffffff;'>
                <div style='background: #0891b2; padding: 20px 24px; color: #ffffff;'>
                    <h2 style='margin: 0; font-size: 18px; letter-spacing: 0.5px;'>{$companyName}</h2>
                    <p style='margin: 4px 0 0 0; font-size: 13px; color: #cffafe;'>🔄 Task Status Changed</p>
                </div>
                <div style='padding: 24px;'>
                    <p style='font-size: 14px; color: #334155; margin-top: 0;'>Hello <b>{$employee->displayName()}</b>,</p>
                    <p style='font-size: 14px; color: #334155;'><b>{$senderName}</b> has updated the status of task <b>{$task->title}</b>.</p>
                    
                    <div style='background: #f0fdfa; border-left: 4px solid #0891b2; padding: 14px 18px; border-radius: 6px; margin: 18px 0;'>
                        <div style='font-size: 13px; color: #64748b; margin-bottom: 4px;'>Status changed:</div>
                        <div style='font-size: 15px; font-weight: bold;'>
                            <span style='color: #64748b;'>{$oldLabel}</span> 
                            <span style='color: #0f172a; margin: 0 8px;'>➔</span> 
                            <span style='color: {$statusColor};'>{$newLabel}</span>
                        </div>
                    </div>

                    <div style='text-align: center; margin: 25px 0 10px 0;'>
                        <a href='{$actionUrl}' style='background: #0891b2; color: #ffffff; padding: 11px 26px; text-decoration: none; border-radius: 8px; font-weight: bold; font-size: 13px; display: inline-block;'>View Task Details</a>
                    </div>
                </div>
                <div style='background: #f1f5f9; padding: 12px 24px; text-align: center; font-size: 11px; color: #64748b; border-top: 1px solid #e2e8f0;'>
                    This is an automated status alert from the Task Management System.
                </div>
            </div>";

            Mail::html($htmlContent, function ($message) use ($employee, $subject) {
                $message->to($employee->email, $employee->displayName())
                    ->subject($subject);
            });

            Log::info("Task status change email successfully sent to {$employee->email} for Task #{$task->id}");
        } catch (\Throwable $e) {
            Log::error("Failed to send task status email to user #{$employee->id}: " . $e->getMessage());
        }
    }

    /**
     * Send email notification when task details/deadline are updated.
     */
    public static function sendTaskUpdatedMail(User $employee, Task $task, string $updateSummary, ?User $updatedBy = null): void
    {
        try {
            if (empty($employee->email) || !filter_var($employee->email, FILTER_VALIDATE_EMAIL)) {
                Log::info("Task update email skipped: User #{$employee->id} has no valid email address.");
                return;
            }

            $senderName = $updatedBy ? $updatedBy->displayName() : 'Management / Admin';
            $companyName = Setting::current()->company_name ?: config('app.name', 'HRMS');
            $subject = "Task Updated: {$task->title}";
            $actionUrl = url('/tasks?open_task=' . $task->id . '&tab=details');

            $htmlContent = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; border: 1px solid #bfdbfe; border-radius: 12px; overflow: hidden; background: #ffffff;'>
                <div style='background: #1d4ed8; padding: 20px 24px; color: #ffffff;'>
                    <h2 style='margin: 0; font-size: 18px; letter-spacing: 0.5px;'>{$companyName}</h2>
                    <p style='margin: 4px 0 0 0; font-size: 13px; color: #dbeafe;'>📝 Task Update Notification</p>
                </div>
                <div style='padding: 24px;'>
                    <p style='font-size: 14px; color: #334155; margin-top: 0;'>Hello <b>{$employee->displayName()}</b>,</p>
                    <p style='font-size: 14px; color: #334155;'><b>{$senderName}</b> has made updates to task: <b>{$task->title}</b>.</p>
                    
                    <div style='background: #eff6ff; border-left: 4px solid #1d4ed8; padding: 14px 18px; border-radius: 6px; margin: 18px 0;'>
                        <p style='margin: 0; font-size: 13.5px; color: #1e3a8a; line-height: 1.5; white-space: pre-wrap;'>" . nl2br(e($updateSummary)) . "</p>
                    </div>

                    <div style='text-align: center; margin: 25px 0 10px 0;'>
                        <a href='{$actionUrl}' style='background: #1d4ed8; color: #ffffff; padding: 11px 26px; text-decoration: none; border-radius: 8px; font-weight: bold; font-size: 13px; display: inline-block;'>View Task Details</a>
                    </div>
                </div>
                <div style='background: #f1f5f9; padding: 12px 24px; text-align: center; font-size: 11px; color: #64748b; border-top: 1px solid #e2e8f0;'>
                    Please review the updated task details on the portal.
                </div>
            </div>";

            Mail::html($htmlContent, function ($message) use ($employee, $subject) {
                $message->to($employee->email, $employee->displayName())
                    ->subject($subject);
            });

            Log::info("Task update email successfully sent to {$employee->email} for Task #{$task->id}");
        } catch (\Throwable $e) {
            Log::error("Failed to send task update email to user #{$employee->id}: " . $e->getMessage());
        }
    }
}
