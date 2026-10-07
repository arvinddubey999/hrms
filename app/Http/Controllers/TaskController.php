<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Company;
use App\Models\Department;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->get('status');
        $priority = $request->get('priority');
        $q = $request->get('q');
        $departmentId = $request->get('department_id');
        $companyId = $request->get('company_id');
        $userId = $request->get('user_id');
        $dateFilter = $request->get('date_filter'); // overdue, today, future

        $query = Task::query()
            ->with(['assignee', 'assignees', 'others', 'department', 'replies.user', 'attachments', 'histories.user'])
            ->when($status && $status !== 'all', fn ($q) => $q->where('status', $status))
            ->when($priority, fn ($q) => $q->where('priority', $priority))
            ->when($departmentId, fn ($q) => $q->where('department_id', $departmentId))
            ->when($companyId, fn ($q) => $q->whereHas('assignee', fn ($u) => $u->where('company_id', $companyId)))
            ->when($userId, fn ($q) => $q->where(fn ($uQuery) => $uQuery->where('assigned_to', $userId)->orWhereHas('assignees', fn ($p) => $p->where('users.id', $userId))))
            ->when($dateFilter === 'overdue', fn ($q) => $q->where('status', '!=', 'completed')->whereDate('due_date', '<', now()->toDateString()))
            ->when($dateFilter === 'today', fn ($q) => $q->whereDate('due_date', now()->toDateString()))
            ->when($dateFilter === 'future', fn ($q) => $q->whereDate('due_date', '>', now()->toDateString()))
            ->when($q, fn ($qQuery) => $qQuery->where(function ($inner) use ($q) {
                $inner->where('title', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%")
                    ->orWhere('client', 'like', "%{$q}%");
            }))
            ->latest();

        $items = $query->paginate(20);

        $counts = [
            'all' => Task::count(),
            'pending' => Task::where('status', 'pending')->count(),
            'in_progress' => Task::where('status', 'in_progress')->count(),
            'completed' => Task::where('status', 'completed')->count(),
            'overdue' => Task::where('status', '!=', 'completed')->whereDate('due_date', '<', now()->toDateString())->count(),
        ];

        return view('tasks.index', [
            'items' => $items,
            'counts' => $counts,
            'status' => $status,
            'priority' => $priority,
            'q' => $q,
            'departmentId' => $departmentId,
            'companyId' => $companyId,
            'userId' => $userId,
            'dateFilter' => $dateFilter,
            'employees' => User::where('status', 'active')->orderBy('first_name')->get(),
            'departments' => Department::orderBy('name')->get(),
            'companies' => Company::orderBy('name')->get(),
            'categories' => Category::orderBy('name')->get(),
            'allTasksForCalendar' => Task::with(['assignee'])->select(['id', 'title', 'due_date', 'due_time', 'priority', 'status', 'assigned_to'])->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string',
            'description' => 'nullable|string',
            'client' => 'nullable|string',
            'status' => 'required|in:pending,in_progress,completed',
            'priority' => 'required|in:low,medium,high',
            'due_date' => 'nullable|date',
            'due_time' => 'nullable',
            'repeat_type' => 'nullable|in:none,daily,weekly,monthly,quarterly,half_yearly,yearly',
            'department_id' => 'nullable|exists:departments,id',
            'assigned_to' => 'nullable|exists:users,id',
            'others' => 'nullable|array',
            'assignee_ids' => 'nullable|array',
            'attachment_file' => 'nullable|file|max:10240',
        ]);

        $others = $data['others'] ?? [];
        $assigneeIds = $data['assignee_ids'] ?? [];
        unset($data['others'], $data['assignee_ids'], $data['attachment_file']);

        $data['created_by'] = $request->user()->id;
        $data['repeat_type'] = $data['repeat_type'] ?? 'none';

        if ($request->hasFile('attachment_file')) {
            $data['attachment'] = $request->file('attachment_file')->store('task_attachments', 'public');
        }

        $task = Task::query()->create($data);
        $task->others()->sync($others);

        if (!empty($assigneeIds)) {
            $task->assignees()->sync($assigneeIds);
            if (empty($task->assigned_to)) {
                $task->update(['assigned_to' => $assigneeIds[0]]);
            }
        } elseif (!empty($task->assigned_to)) {
            $task->assignees()->sync([$task->assigned_to]);
        }

        // Store attachment in task_attachments table if uploaded
        if (!empty($data['attachment'])) {
            $task->attachments()->create([
                'user_id' => $request->user()->id,
                'file_path' => $data['attachment'],
                'file_name' => $request->file('attachment_file')->getClientOriginalName(),
            ]);
        }

        // Log Immutable History
        $task->logHistory('created', 'Task created and assigned', $request->user()->id);

        // Notify assigned employees via FCM Push & Email
        $notifyUsers = User::whereIn('id', array_filter(array_merge([$task->assigned_to], $assigneeIds)))->get();
        foreach ($notifyUsers as $user) {
            \App\Services\FirebaseNotificationService::sendTaskNotification(
                $user,
                'New Task Assigned: ' . $task->title,
                'You have been assigned a new task due on ' . ($task->due_date ? $task->due_date->format('d M Y') : 'N/A'),
                $task->id
            );
        }

        return back()->with('ok', 'Task created successfully and notifications dispatched.');
    }

    public function show(Task $task)
    {
        $task->load(['assignee', 'assignees', 'others', 'department', 'replies.user', 'attachments.user', 'histories.user']);

        return view('tasks.show', [
            'task' => $task,
            'employees' => User::where('status', 'active')->orderBy('first_name')->get(),
            'departments' => Department::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Task $task)
    {
        $data = $request->validate([
            'title' => 'required|string',
            'description' => 'nullable|string',
            'client' => 'nullable|string',
            'status' => 'required|in:pending,in_progress,completed',
            'priority' => 'required|in:low,medium,high',
            'due_date' => 'nullable|date',
            'due_time' => 'nullable',
            'repeat_type' => 'nullable|in:none,daily,weekly,monthly,quarterly,half_yearly,yearly',
            'department_id' => 'nullable|exists:departments,id',
            'assigned_to' => 'nullable|exists:users,id',
            'others' => 'nullable|array',
            'assignee_ids' => 'nullable|array',
        ]);

        $others = $data['others'] ?? [];
        $assigneeIds = $data['assignee_ids'] ?? [];
        unset($data['others'], $data['assignee_ids']);

        $oldStatus = $task->status;
        $task->update($data);
        $task->others()->sync($others);

        if (!empty($assigneeIds)) {
            $task->assignees()->sync($assigneeIds);
        }

        if ($oldStatus !== $task->status) {
            $task->logHistory('status_updated', "Status updated from {$oldStatus} to {$task->status}", $request->user()->id);
        } else {
            $task->logHistory('updated', 'Task details updated', $request->user()->id);
        }

        return back()->with('ok', 'Task updated successfully.');
    }

    public function reply(Request $request, Task $task)
    {
        $data = $request->validate([
            'message' => 'required|string',
            'reply_attachment' => 'nullable|file|max:10240',
        ]);

        $attachmentPath = null;
        if ($request->hasFile('reply_attachment')) {
            $attachmentPath = $request->file('reply_attachment')->store('task_replies', 'public');
        }

        $reply = $task->replies()->create([
            'user_id' => $request->user()->id,
            'message' => $data['message'],
            'attachment' => $attachmentPath,
        ]);

        $task->logHistory('replied', "Reply added: " . Str::limit($data['message'], 50), $request->user()->id);

        // Notify task creator and assignees via FCM Push Notification
        $assigneeIds = $task->assignees()->pluck('users.id')->toArray();
        $targetUserIds = array_unique(array_filter(array_merge([$task->created_by, $task->assigned_to], $assigneeIds)));
        $notifyUsers = User::whereIn('id', $targetUserIds)->where('id', '!=', $request->user()->id)->get();

        foreach ($notifyUsers as $user) {
            \App\Services\FirebaseNotificationService::sendTaskNotification(
                $user,
                'New Reply on Task: ' . $task->title,
                $request->user()->displayName() . ': ' . Str::limit($data['message'], 60),
                $task->id
            );
        }

        return back()->with('ok', 'Reply posted successfully.');
    }

    public function reassign(Request $request, Task $task)
    {
        $data = $request->validate([
            'new_assigned_to' => 'required|exists:users,id',
            'remarks' => 'nullable|string',
        ]);

        $newAssignee = User::findOrFail($data['new_assigned_to']);
        $oldAssigneeName = $task->assignee ? $task->assignee->displayName() : 'Unassigned';

        $task->update(['assigned_to' => $newAssignee->id]);
        $task->assignees()->syncWithoutDetaching([$newAssignee->id]);

        $logMsg = "Task re-assigned from {$oldAssigneeName} to {$newAssignee->displayName()}";
        if (!empty($data['remarks'])) {
            $logMsg .= " (Reason: {$data['remarks']})";
        }

        $task->logHistory('reassigned', $logMsg, $request->user()->id);

        // Send FCM Notification to new assignee
        \App\Services\FirebaseNotificationService::sendTaskNotification(
            $newAssignee,
            'Task Re-Assigned To You: ' . $task->title,
            $logMsg,
            $task->id
        );

        return back()->with('ok', "Task re-assigned to {$newAssignee->displayName()} successfully.");
    }

    public function remind(Request $request, Task $task)
    {
        $assignees = $task->assignees;
        if ($task->assignee) {
            $assignees->push($task->assignee);
        }
        $assignees = $assignees->unique('id');

        $senderName = $request->user()->displayName();
        $msg = "Reminder from {$senderName}: Please update progress on task '{$task->title}'";

        foreach ($assignees as $user) {
            \App\Services\FirebaseNotificationService::sendTaskNotification(
                $user,
                'Task Reminder: ' . $task->title,
                $msg,
                $task->id
            );
        }

        $task->logHistory('reminded', "Reminder notification sent by {$senderName}", $request->user()->id);

        return back()->with('ok', 'Reminder notification sent to assigned team members.');
    }

    public function uploadAttachment(Request $request, Task $task)
    {
        $request->validate([
            'file' => 'required|file|max:10240',
        ]);

        $file = $request->file('file');
        $path = $file->store('task_attachments', 'public');

        $task->attachments()->create([
            'user_id' => $request->user()->id,
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
        ]);

        $task->logHistory('attachment_added', "Uploaded file: {$file->getClientOriginalName()}", $request->user()->id);

        return back()->with('ok', 'Attachment uploaded.');
    }

    public function destroy(Task $task)
    {
        $task->delete();

        return redirect()->route('tasks.index')->with('ok', 'Task deleted.');
    }

    public function exportExcel()
    {
        $tasks = Task::with(['assignee', 'department'])->latest()->get();
        $filename = "tasks-report-" . date('Y-m-d') . ".csv";

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($tasks) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['ID', 'Task Title', 'Department', 'Assigned To', 'Priority', 'Status', 'Due Date']);
            foreach ($tasks as $t) {
                fputcsv($out, [
                    $t->id,
                    $t->title,
                    $t->department->name ?? '-',
                    $t->assignee->displayName() ?? 'Unassigned',
                    strtoupper($t->priority),
                    strtoupper($t->status),
                    $t->due_date ? $t->due_date->format('Y-m-d') : '-',
                ]);
            }
            fclose($out);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function exportPdf()
    {
        $tasks = Task::with(['assignee', 'department'])->latest()->get();
        return view('tasks.pdf', compact('tasks'));
    }
}
