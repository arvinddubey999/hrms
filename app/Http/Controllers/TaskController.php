<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Company;
use App\Models\Department;
use App\Models\Task;
use App\Models\TaskAttachment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        $u = $request->user();
        if (!$u || (!$u->hasPermission('tasks.view') && !$u->hasPermission('tasks.create') && !$u->hasPermission('tasks.manage'))) {
            return back()->with('error', 'You do not have permission to view tasks.');
        }

        // By default cursor / active view should be on 'pending' unless explicitly requested
        $status = $request->has('status') ? $request->get('status') : 'pending';
        $priority = $request->get('priority');
        $q = $request->get('q');
        $departmentId = $request->get('department_id');
        $companyId = $request->get('company_id');
        $userId = $request->get('user_id');
        $dateFilter = $request->get('date_filter'); // overdue, today, future
        $viewMode = $request->get('view_mode'); // 'user_wise' or null
        $sortBy = $request->get('sort_by', 'deadline');
        $sortDir = strtolower($request->get('sort_dir', 'asc')) === 'desc' ? 'desc' : 'asc';

        $query = $this->getFilteredTasksQuery($request);
        $items = $query->paginate(20)->withQueryString();

        // In user_wise mode, group tasks by assignee
        $userWiseGroups = null;
        if ($viewMode === 'user_wise') {
            $allMatchingTasks = (clone $query)->get();
            $userWiseGroups = $allMatchingTasks->groupBy(function ($task) {
                return $task->assignee ? $task->assignee->displayName() : 'Unassigned';
            });
        }

        $baseCountQuery = Task::query();
        if (!$u->isAdmin()) {
            $baseCountQuery->where(function ($inner) use ($u) {
                $inner->where('assigned_to', $u->id)
                    ->orWhere('created_by', $u->id)
                    ->orWhereHas('assignees', fn ($p) => $p->where('users.id', $u->id))
                    ->orWhereHas('others', fn ($p) => $p->where('users.id', $u->id));
            });
        }

        $counts = [
            'all' => (clone $baseCountQuery)->count(),
            'pending' => (clone $baseCountQuery)->where('status', 'pending')->count(),
            'in_progress' => (clone $baseCountQuery)->where('status', 'in_progress')->count(),
            'completed' => (clone $baseCountQuery)->where('status', 'completed')->count(),
            'overdue' => (clone $baseCountQuery)->where('status', '!=', 'completed')->whereDate('due_date', '<', now()->toDateString())->count(),
        ];

        // Ensure all active employees of the company are included for task creation
        $compIds = $u->accessibleCompanyIds();
        $companyEmployeesQuery = User::where('status', 'active');
        if (!$u->isAdmin() && !empty($compIds)) {
            $companyEmployeesQuery->whereIn('company_id', $compIds);
        }
        $companyEmployees = $companyEmployeesQuery->orderBy('first_name')->get();

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
            'viewMode' => $viewMode,
            'sortBy' => $sortBy,
            'sortDir' => $sortDir,
            'userWiseGroups' => $userWiseGroups,
            'employees' => $companyEmployees,
            'departments' => Department::orderBy('name')->get(),
            'companies' => (!$u->isAdmin() && !empty($u->accessibleCompanyIds())) ? Company::whereIn('id', $u->accessibleCompanyIds())->orderBy('name')->get() : Company::orderBy('name')->get(),
            'categories' => Category::orderBy('name')->get(),
            'allTasksForCalendar' => (clone $baseCountQuery)->with(['assignee'])->select(['id', 'title', 'due_date', 'due_time', 'priority', 'status', 'assigned_to'])->get(),
        ]);
    }

    public function store(Request $request)
    {
        $u = $request->user();
        if (!$u || (!$u->hasPermission('tasks.create') && !$u->hasPermission('tasks.manage'))) {
            return back()->with('error', 'You do not have permission to create & assign tasks.');
        }
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
            'attachment_file' => 'nullable|file|max:15360',
            'attachment_files' => 'nullable|array',
            'attachment_files.*' => 'nullable|file|max:15360',
        ]);

        $others = $data['others'] ?? [];
        $assigneeIds = $data['assignee_ids'] ?? [];
        unset($data['others'], $data['assignee_ids'], $data['attachment_file'], $data['attachment_files']);

        $data['created_by'] = $request->user()->id;
        $data['repeat_type'] = $data['repeat_type'] ?? 'none';

        if ($request->hasFile('attachment_files')) {
            $first = $request->file('attachment_files')[0] ?? null;
            if ($first) {
                $data['attachment'] = $first->store('task_attachments', 'public');
            }
        } elseif ($request->hasFile('attachment_file')) {
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

        // Store attachments in task_attachments table if uploaded
        if ($request->hasFile('attachment_files')) {
            foreach ($request->file('attachment_files') as $file) {
                $p = $file->store('task_attachments', 'public');
                $task->attachments()->create([
                    'user_id' => $request->user()->id,
                    'file_path' => $p,
                    'file_name' => $file->getClientOriginalName(),
                ]);
            }
        } elseif ($request->hasFile('attachment_file')) {
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
        $u = auth()->user();
        $canManageAll = $u && ($u->isAdmin() || $u->hasPermission('tasks.manage'));
        if (!$canManageAll) {
            $isAssigned = $task->assigned_to == $u->id 
                || $task->created_by == $u->id 
                || $task->assignees()->where('users.id', $u->id)->exists()
                || $task->others()->where('users.id', $u->id)->exists();
            if (!$isAssigned) {
                return redirect()->route('tasks.index')->with('error', 'You are not authorized to view this task.');
            }
        }

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
            'new_assigned_to' => 'required',
            'remarks' => 'nullable|string',
        ]);

        $rawAssignees = is_array($data['new_assigned_to']) ? $data['new_assigned_to'] : [$data['new_assigned_to']];
        $assigneeIds = array_filter(array_map('intval', $rawAssignees));

        if (empty($assigneeIds)) {
            return back()->with('error', 'Please select at least one assignee.');
        }

        $assignees = User::whereIn('id', $assigneeIds)->get();
        if ($assignees->isEmpty()) {
            return back()->with('error', 'Selected employees not found.');
        }

        $primaryAssignee = $assignees->first();
        $oldAssigneeName = $task->assignee ? $task->assignee->displayName() : 'Unassigned';

        $task->update(['assigned_to' => $primaryAssignee->id]);
        $task->assignees()->sync($assigneeIds);

        $newNames = $assignees->map(fn ($u) => $u->displayName())->implode(', ');
        $logMsg = "Task re-assigned from {$oldAssigneeName} to {$newNames}";
        if (!empty($data['remarks'])) {
            $logMsg .= " (Reason: {$data['remarks']})";
        }

        $task->logHistory('reassigned', $logMsg, $request->user()->id);

        // Send FCM Notification to all new assignees
        foreach ($assignees as $newAssignee) {
            \App\Services\FirebaseNotificationService::sendTaskNotification(
                $newAssignee,
                'Task Re-Assigned To You: ' . $task->title,
                $logMsg,
                $task->id
            );
        }

        return back()->with('ok', "Task re-assigned to {$newNames} successfully.");
    }

    public function updateStatus(Request $request, Task $task)
    {
        $u = $request->user();
        if (!$u) {
            return redirect()->route('login');
        }

        $data = $request->validate([
            'status' => 'required|in:pending,in_progress,completed',
        ]);

        $oldStatus = $task->status;
        $task->update(['status' => $data['status']]);

        $statusLabels = [
            'pending' => 'Not Started / Pending',
            'in_progress' => 'In Progress',
            'completed' => 'Closed / Done',
        ];

        $oldLabel = $statusLabels[$oldStatus] ?? $oldStatus;
        $newLabel = $statusLabels[$task->status] ?? $task->status;

        $task->logHistory('status_updated', "Status updated from '{$oldLabel}' to '{$newLabel}' by " . $u->displayName(), $u->id);

        // Repeat Task Option: When task is closed (completed) and repeat_type is active, spawn next recurrence
        if ($data['status'] === 'completed' && !empty($task->repeat_type) && $task->repeat_type !== 'none') {
            $baseDate = $task->due_date ? Carbon::parse($task->due_date) : now('Asia/Kolkata');
            $nextDate = match($task->repeat_type) {
                'daily' => $baseDate->copy()->addDay(),
                'weekly' => $baseDate->copy()->addWeek(),
                'monthly' => $baseDate->copy()->addMonth(),
                'quarterly' => $baseDate->copy()->addMonths(3),
                'half_yearly' => $baseDate->copy()->addMonths(6),
                'yearly' => $baseDate->copy()->addYear(),
                default => null,
            };

            if ($nextDate) {
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

                $newTask->logHistory('created', "Auto-created next recurring task ({$task->repeat_type}) for due date " . $nextDate->format('d M Y') . " because Task #{$task->id} was completed.", $u->id);

                return back()->with('ok', "Task status updated to '{$newLabel}'. Next recurring task for {$nextDate->format('d M Y')} has been automatically created!");
            }
        }

        return back()->with('ok', "Task status updated to '{$newLabel}' successfully.");
    }

    public function togglePin(Request $request, Task $task)
    {
        $task->update(['is_pinned' => !$task->is_pinned]);
        $state = $task->is_pinned ? 'pinned to top' : 'unpinned';
        $task->logHistory('pinned', "Task {$state} by " . $request->user()->displayName(), $request->user()->id);
        return back()->with('ok', "Task {$state} successfully.");
    }

    public function reopen(Request $request, Task $task)
    {
        $u = $request->user();
        $data = $request->validate([
            'due_date' => 'nullable|date',
            'due_time' => 'nullable|string',
            'remarks' => 'nullable|string',
        ]);

        $updateData = ['status' => 'in_progress'];
        if (!empty($data['due_date'])) {
            $updateData['due_date'] = $data['due_date'];
        }
        if (!empty($data['due_time'])) {
            $updateData['due_time'] = $data['due_time'];
        }

        $task->update($updateData);

        $msg = "Task re-opened by " . $u->displayName();
        if (!empty($data['due_date'])) {
            $msg .= " with new deadline " . Carbon::parse($data['due_date'])->format('d M Y');
            if (!empty($data['due_time'])) {
                $msg .= " " . $data['due_time'];
            }
        }
        if (!empty($data['remarks'])) {
            $msg .= ". Note: " . $data['remarks'];
        }

        $task->logHistory('reopened', $msg, $u->id);

        return back()->with('ok', 'Task re-opened successfully with updated deadline.');
    }

    public function exportRepliesPdf(Task $task)
    {
        $task->load(['creator', 'assignee', 'assignees', 'others', 'department', 'replies.user', 'attachments.user', 'histories.user']);
        return view('tasks.replies-pdf', compact('task'));
    }

    public function deleteAttachment(TaskAttachment $attachment)
    {
        if ($attachment->file_path) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($attachment->file_path);
        }
        $attachment->delete();
        return back()->with('ok', 'Attachment removed successfully.');
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

    public function exportExcel(Request $request)
    {
        $u = $request->user();
        if (!$u || (!$u->hasPermission('tasks.view') && !$u->hasPermission('tasks.create') && !$u->hasPermission('tasks.manage'))) {
            return back()->with('error', 'You do not have permission to export tasks.');
        }

        $tasks = $this->getFilteredTasksQuery($request)->get();
        $filename = "tasks-report-" . date('Y-m-d') . ".csv";

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($tasks) {
            $out = fopen('php://output', 'w');
            // Write UTF-8 BOM so Excel opens Hindi / Unicode properly
            fputs($out, "\xEF\xBB\xBF");
            fputcsv($out, ['ID', 'Task Title', 'Department', 'Assigned To (To)', 'Assigned By (From)', 'Priority', 'Status', 'Due Date', 'Client / Description']);
            foreach ($tasks as $t) {
                $assigneeNames = [];
                if ($t->assignee) {
                    $assigneeNames[] = $t->assignee->displayName();
                }
                foreach ($t->assignees as $otherAssignee) {
                    if (!$t->assignee || $otherAssignee->id !== $t->assignee->id) {
                        $assigneeNames[] = $otherAssignee->displayName();
                    }
                }
                $assignedToStr = !empty($assigneeNames) ? implode(', ', $assigneeNames) : 'Unassigned';
                $assignedByStr = $t->creator ? $t->creator->displayName() : 'Admin';

                fputcsv($out, [
                    $t->id,
                    $t->title,
                    $t->department->name ?? '-',
                    $assignedToStr,
                    $assignedByStr,
                    strtoupper($t->priority),
                    strtoupper(str_replace('_', ' ', $t->status)),
                    $t->due_date ? $t->due_date->format('Y-m-d') : '-',
                    $t->client ? "Client: {$t->client}" : ($t->description ?? '-'),
                ]);
            }
            fclose($out);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function exportPdf(Request $request)
    {
        $u = $request->user();
        if (!$u || (!$u->hasPermission('tasks.view') && !$u->hasPermission('tasks.create') && !$u->hasPermission('tasks.manage'))) {
            return back()->with('error', 'You do not have permission to export tasks.');
        }

        $tasks = $this->getFilteredTasksQuery($request)->get();
        return view('tasks.pdf', compact('tasks'));
    }

    protected function getFilteredTasksQuery(Request $request)
    {
        $u = $request->user();

        // By default, status is 'pending' unless explicitly requested
        $status = $request->has('status') ? $request->get('status') : 'pending';
        $priority = $request->get('priority');
        $q = $request->get('q');
        $departmentId = $request->get('department_id');
        $companyId = $request->get('company_id');
        $userId = $request->get('user_id');
        $dateFilter = $request->get('date_filter'); // overdue, today, future

        $sortBy = $request->get('sort_by', 'deadline');
        $sortDir = strtolower($request->get('sort_dir', 'asc')) === 'desc' ? 'desc' : 'asc';

        $query = Task::query()
            ->with(['creator', 'assignee', 'assignees', 'others', 'department', 'replies.user', 'attachments', 'histories.user'])
            ->when(!$u || !$u->isAdmin(), function ($q) use ($u) {
                if (!$u) return;
                $q->where(function ($inner) use ($u) {
                    $inner->where('assigned_to', $u->id)
                        ->orWhere('created_by', $u->id)
                        ->orWhereHas('assignees', fn ($p) => $p->where('users.id', $u->id))
                        ->orWhereHas('others', fn ($p) => $p->where('users.id', $u->id));
                });
            })
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
            ->orderByDesc('is_pinned');

        if ($sortBy === 'deadline') {
            if ($sortDir === 'desc') {
                $query->orderByRaw('due_date IS NULL, due_date DESC, id DESC');
            } else {
                // ASC: nearest upcoming deadline first, tasks 2 months away are not on top
                $query->orderByRaw('due_date IS NULL, due_date ASC, id DESC');
            }
        } else {
            $query->latest();
        }

        return $query;
    }
}
