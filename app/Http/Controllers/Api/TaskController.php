<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Task;
use App\Models\TaskAttachment;
use App\Models\TaskCategory;
use App\Models\TaskReply;
use App\Models\User;
use App\Services\FirebaseNotificationService;
use App\Services\MailService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $baseQuery = Task::query()->with([
            'creator',
            'assignee',
            'assignees',
            'others',
            'department',
            'category',
            'replies.user',
            'attachments.user',
            'histories.user',
        ]);

        $canViewAll = $user->isAdmin() || $user->isSuperAdmin() || $user->hasPermission('tasks.manage');

        // Scope filter
        $scope = $request->get('scope', 'all'); // 'all', 'mine', 'created'
        if (!$canViewAll || $scope === 'mine') {
            $baseQuery->where(function ($q) use ($user) {
                $q->where('assigned_to', $user->id)
                    ->orWhere('created_by', $user->id)
                    ->orWhereHas('assignees', fn ($a) => $a->where('users.id', $user->id))
                    ->orWhereHas('others', fn ($o) => $o->where('users.id', $user->id));
            });
        } elseif ($scope === 'created') {
            $baseQuery->where('created_by', $user->id);
        }

        // Search filter (title, description, client)
        $search = trim($request->get('q') ?: $request->get('search', ''));
        if (!empty($search)) {
            $baseQuery->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('client', 'like', "%{$search}%");
            });
        }

        // Department filter
        if ($request->filled('department_id')) {
            $baseQuery->where('department_id', $request->get('department_id'));
        }

        // Category filter
        if ($request->filled('category_id')) {
            $baseQuery->where('category_id', $request->get('category_id'));
        }

        // Priority filter
        if ($request->filled('priority') && in_array($request->get('priority'), ['low', 'medium', 'high'])) {
            $baseQuery->where('priority', $request->get('priority'));
        }

        // Calculate counts for tab badges based on current search & filters (except status)
        $todayStr = now()->toDateString();
        $countsQuery = clone $baseQuery;
        $allTasksForCounts = $countsQuery->select(['id', 'status', 'due_date'])->get();

        $counts = [
            'all' => $allTasksForCounts->count(),
            'pending' => $allTasksForCounts->where('status', 'pending')->count(),
            'in_progress' => $allTasksForCounts->where('status', 'in_progress')->count(),
            'completed' => $allTasksForCounts->where('status', 'completed')->count(),
            'overdue' => $allTasksForCounts->filter(function ($t) use ($todayStr) {
                return $t->status !== 'completed' && !empty($t->due_date) && optional($t->due_date)->toDateString() < $todayStr;
            })->count(),
        ];

        // Apply Status Filter to final list
        $status = $request->get('status', 'all');
        if ($status === 'pending') {
            $baseQuery->where('status', 'pending');
        } elseif ($status === 'in_progress') {
            $baseQuery->where('status', 'in_progress');
        } elseif ($status === 'completed') {
            $baseQuery->where('status', 'completed');
        } elseif ($status === 'overdue') {
            $baseQuery->where('status', '!=', 'completed')
                ->whereNotNull('due_date')
                ->where('due_date', '<', $todayStr);
        }

        // Sort: Pinned first, then newest
        $items = $baseQuery->orderByDesc('is_pinned')
            ->orderByDesc('id')
            ->get()
            ->map(fn (Task $t) => $this->formatTask($t));

        return response()->json([
            'data' => $items,
            'counts' => $counts,
        ]);
    }

    public function show(Task $task)
    {
        $user = auth()->user();
        $canManage = $user && ($user->isAdmin() || $user->isSuperAdmin() || $user->hasPermission('tasks.manage'));
        if (!$canManage) {
            $isAssigned = $task->assigned_to == $user->id
                || $task->created_by == $user->id
                || $task->assignees()->where('users.id', $user->id)->exists()
                || $task->others()->where('users.id', $user->id)->exists();
            if (!$isAssigned) {
                return response()->json(['message' => 'Unauthorized to view this task'], 403);
            }
        }

        $task->load([
            'creator',
            'assignee',
            'assignees',
            'others',
            'department',
            'category',
            'replies.user',
            'attachments.user',
            'histories.user',
        ]);

        return response()->json(['data' => $this->formatTask($task)]);
    }

    public function store(Request $request)
    {
        $u = $request->user();
        if (!$u->isSuperAdmin() && !$u->isAdmin() && !$u->hasPermission('tasks.create')) {
            return response()->json(['message' => 'You do not have permission to create tasks.'], 403);
        }

        $data = $request->validate([
            'title' => 'required|string',
            'description' => 'nullable|string',
            'client' => 'nullable|string',
            'status' => 'nullable|in:pending,in_progress,completed',
            'priority' => 'required|in:low,medium,high',
            'category_id' => 'nullable|exists:task_categories,id',
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

        $data['created_by'] = $u->id;
        $data['status'] = $data['status'] ?? 'pending';
        $data['repeat_type'] = $data['repeat_type'] ?? 'none';

        // Gather all uploaded files
        $allUploadedFiles = [];
        if ($request->hasFile('attachment_files')) {
            $allUploadedFiles = array_merge($allUploadedFiles, (array)$request->file('attachment_files'));
        }
        if ($request->hasFile('attachment_file')) {
            $allUploadedFiles[] = $request->file('attachment_file');
        }

        if (!empty($allUploadedFiles)) {
            $first = $allUploadedFiles[0];
            if ($first && $first->isValid()) {
                $data['attachment'] = $first->store('task_attachments', 'public');
            }
        }

        $task = Task::create($data);

        $task->others()->sync($others);
        if (!empty($assigneeIds)) {
            $task->assignees()->sync($assigneeIds);
            if (empty($task->assigned_to)) {
                $task->update(['assigned_to' => $assigneeIds[0]]);
            }
        } elseif (!empty($task->assigned_to)) {
            $task->assignees()->sync([$task->assigned_to]);
        }

        // Store attachment records
        foreach ($allUploadedFiles as $idx => $file) {
            if ($file && $file->isValid()) {
                $p = ($idx === 0 && !empty($data['attachment'])) ? $data['attachment'] : $file->store('task_attachments', 'public');
                $task->attachments()->create([
                    'user_id' => $u->id,
                    'file_path' => $p,
                    'file_name' => $file->getClientOriginalName(),
                ]);
            }
        }

        $task->logHistory('created', 'Task created via Mobile App', $u->id);

        // Send FCM & Email notifications
        $targetUserIds = array_unique(array_filter(array_merge([$task->assigned_to], $assigneeIds, $others)));
        $notifyUsers = User::whereIn('id', $targetUserIds)->where('id', '!=', $u->id)->get();

        foreach ($notifyUsers as $emp) {
            FirebaseNotificationService::sendTaskNotification(
                $emp,
                'New Task Assigned: ' . $task->title,
                'You have been assigned a new task due on ' . ($task->due_date ? Carbon::parse($task->due_date)->format('d M Y') : 'N/A'),
                $task->id
            );
            MailService::sendTaskAssignedMail($emp, $task, $u);
        }

        return response()->json([
            'ok' => true,
            'message' => 'Task created successfully and notifications dispatched!',
            'data' => $this->formatTask($task->fresh(['creator', 'assignee', 'assignees', 'others', 'department', 'category', 'replies.user', 'attachments.user', 'histories.user'])),
        ]);
    }

    public function updateStatus(Request $request, Task $task)
    {
        $user = $request->user();
        $allowed = $task->assigned_to === $user->id
            || $task->created_by === $user->id
            || $task->others()->where('users.id', $user->id)->exists()
            || $task->assignees()->where('users.id', $user->id)->exists()
            || $user->isAdmin()
            || $user->isSuperAdmin()
            || $user->hasPermission('tasks.manage');

        if (!$allowed) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        // Support toggle_pin via /status endpoint
        if ($request->has('toggle_pin') || $request->has('is_pinned')) {
            $newPin = $request->has('is_pinned') ? (bool)$request->is_pinned : !$task->is_pinned;
            $task->update(['is_pinned' => $newPin]);
            $state = $newPin ? 'pinned to top' : 'unpinned';
            $task->logHistory('pinned', "Task {$state} by " . $user->displayName(), $user->id);
            return response()->json([
                'ok' => true,
                'is_pinned' => (bool)$task->is_pinned,
                'message' => "Task {$state} successfully.",
                'data' => $this->formatTask($task->fresh(['creator', 'assignee', 'assignees', 'others', 'department', 'category', 'replies.user', 'attachments.user', 'histories.user'])),
            ]);
        }

        // Support update deadline via /status endpoint
        if ($request->filled('due_date') || $request->filled('due_time')) {
            $up = [];
            $logDetails = [];
            if ($request->filled('due_date')) {
                $up['due_date'] = $request->due_date;
                $logDetails[] = "New deadline: " . Carbon::parse($request->due_date)->format('d M Y');
            }
            if ($request->filled('due_time')) {
                $up['due_time'] = $request->due_time;
                $logDetails[] = "Time: " . $request->due_time;
            }
            if ($request->filled('status')) {
                $up['status'] = $request->status;
                $logDetails[] = "Status: " . strtoupper(str_replace('_', ' ', $request->status));
            }
            if (!empty($up)) {
                $task->update($up);
                $msg = "Deadline/Status updated by " . $user->displayName() . ": " . implode(', ', $logDetails);
                if ($request->filled('remarks')) {
                    $msg .= " (Note: {$request->remarks})";
                }
                $task->logHistory('deadline_updated', $msg, $user->id);
            }
            return response()->json([
                'ok' => true,
                'message' => 'Task deadline and status updated successfully.',
                'data' => $this->formatTask($task->fresh(['creator', 'assignee', 'assignees', 'others', 'department', 'category', 'replies.user', 'attachments.user', 'histories.user'])),
            ]);
        }

        // Support update title/description via /status endpoint
        if ($request->has('title') || $request->has('description')) {
            $descUpdates = [];
            if ($request->filled('title')) $descUpdates['title'] = $request->title;
            if ($request->has('description')) $descUpdates['description'] = $request->description;
            if (!empty($descUpdates)) {
                $task->update($descUpdates);
                $task->logHistory('description_updated', "Task details updated by " . $user->displayName(), $user->id);
            }
            if (!$request->filled('status')) {
                return response()->json([
                    'ok' => true,
                    'message' => 'Task details updated successfully.',
                    'data' => $this->formatTask($task->fresh(['creator', 'assignee', 'assignees', 'others', 'department', 'category', 'replies.user', 'attachments.user', 'histories.user'])),
                ]);
            }
        }

        $data = $request->validate([
            'status' => 'required|in:pending,in_progress,completed',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        $oldStatus = $task->status;
        $task->update($data);

        $statusLabels = [
            'pending' => 'Pending',
            'in_progress' => 'In Progress',
            'completed' => 'Completed',
        ];
        $oldLabel = $statusLabels[$oldStatus] ?? $oldStatus;
        $newLabel = $statusLabels[$task->status] ?? $task->status;

        if ($oldStatus !== $task->status) {
            $task->logHistory('status_updated', "Status updated from '{$oldLabel}' to '{$newLabel}' by " . $user->displayName(), $user->id);

            // Notify participants
            $participants = $task->getParticipants($user->id);
            foreach ($participants as $notifyUser) {
                FirebaseNotificationService::sendTaskNotification(
                    $notifyUser,
                    'Task Status Updated: ' . $task->title,
                    "Status changed from '{$oldLabel}' to '{$newLabel}' by " . $user->displayName(),
                    $task->id
                );
                MailService::sendTaskStatusUpdatedMail($notifyUser, $task, $oldStatus, $task->status, $user);
            }

            // Recurrence on completion
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
                        'category_id' => $task->category_id,
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

                    $newTask->logHistory('created', "Auto-created recurring task ({$task->repeat_type}) for " . $nextDate->format('d M Y') . " as Task #{$task->id} was completed.", $user->id);
                }
            }
        }

        return response()->json([
            'ok' => true,
            'message' => "Status updated to '{$newLabel}' successfully.",
            'data' => $this->formatTask($task->fresh(['creator', 'assignee', 'assignees', 'others', 'department', 'category', 'replies.user', 'attachments.user', 'histories.user'])),
        ]);
    }

    public function reply(Request $request, Task $task)
    {
        $data = $request->validate([
            'message' => 'nullable|string',
            'reply_attachment' => 'nullable|file|max:10240',
            'reply_attachments' => 'nullable|array',
            'reply_attachments.*' => 'nullable|file|max:10240',
        ]);

        $message = trim($data['message'] ?? '');
        $storedPaths = [];

        if ($request->hasFile('reply_attachments')) {
            foreach ($request->file('reply_attachments') as $file) {
                if ($file && $file->isValid()) {
                    $storedPaths[] = $file->store('task_replies', 'public');
                }
            }
        } elseif ($request->hasFile('reply_attachment')) {
            $storedPaths[] = $request->file('reply_attachment')->store('task_replies', 'public');
        }

        if (empty($message) && empty($storedPaths)) {
            return response()->json(['message' => 'Remark text or attachment is required.'], 422);
        }

        $reply = $task->replies()->create([
            'user_id' => $request->user()->id,
            'message' => $message ?: 'Attachment uploaded',
            'attachment' => !empty($storedPaths) ? $storedPaths[0] : null,
            'attachments' => $storedPaths,
        ]);

        $task->logHistory('replied', "Reply added: " . Str::limit($reply->message, 50), $request->user()->id);

        // Notify participants
        $participants = $task->getParticipants($request->user()->id);
        foreach ($participants as $u) {
            FirebaseNotificationService::sendTaskNotification(
                $u,
                'New Reply on Task: ' . $task->title,
                $request->user()->displayName() . ': ' . Str::limit($reply->message, 60),
                $task->id
            );
            MailService::sendTaskReplyMail($u, $task, $reply, $request->user());
        }

        return response()->json([
            'ok' => true,
            'message' => 'Reply posted successfully!',
            'data' => $this->formatTask($task->fresh(['creator', 'assignee', 'assignees', 'others', 'department', 'category', 'replies.user', 'attachments.user', 'histories.user'])),
        ]);
    }

    public function reassign(Request $request, Task $task)
    {
        $u = $request->user();
        $canReassign = $u->isAdmin() || $u->isSuperAdmin() || $u->hasPermission('tasks.manage') || $task->created_by == $u->id;
        if (!$canReassign) {
            return response()->json(['message' => 'You do not have permission to reassign this task.'], 403);
        }

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

        $task->logHistory('reassigned', $logMsg, $u->id);

        FirebaseNotificationService::sendTaskNotification(
            $newAssignee,
            'Task Re-Assigned To You: ' . $task->title,
            $logMsg,
            $task->id
        );
        MailService::sendTaskAssignedMail($newAssignee, $task, $u);

        return response()->json([
            'ok' => true,
            'message' => "Task re-assigned to {$newAssignee->displayName()} successfully!",
            'data' => $this->formatTask($task->fresh(['creator', 'assignee', 'assignees', 'others', 'department', 'category', 'replies.user', 'attachments.user', 'histories.user'])),
        ]);
    }

    public function remind(Request $request, Task $task)
    {
        $assigneeUserIds = array_filter(array_unique(array_merge(
            !empty($task->assigned_to) ? [(int)$task->assigned_to] : [],
            $task->assignees()->pluck('users.id')->toArray(),
            $task->others()->pluck('users.id')->toArray()
        )));
        $assignees = User::whereIn('id', $assigneeUserIds)->get();

        $senderName = $request->user()->displayName();
        $msg = "Reminder from {$senderName}: Please update progress on task '{$task->title}'";

        foreach ($assignees as $u) {
            FirebaseNotificationService::sendTaskNotification(
                $u,
                'Task Reminder: ' . $task->title,
                $msg,
                $task->id
            );
            MailService::sendTaskReminderMail($u, $task, $request->user());
        }

        $task->logHistory('reminded', "Reminder notification sent by {$senderName}", $request->user()->id);

        return response()->json([
            'ok' => true,
            'message' => 'Reminder notification sent successfully to assigned members!',
        ]);
    }

    public function togglePin(Request $request, Task $task)
    {
        $task->update(['is_pinned' => !$task->is_pinned]);
        $state = $task->is_pinned ? 'pinned to top' : 'unpinned';
        $task->logHistory('pinned', "Task {$state} by " . $request->user()->displayName(), $request->user()->id);

        return response()->json([
            'ok' => true,
            'is_pinned' => (bool)$task->is_pinned,
            'message' => "Task {$state} successfully.",
        ]);
    }

    public function reopen(Request $request, Task $task)
    {
        $u = $request->user();
        $canReopen = $u->isAdmin() || $u->isSuperAdmin() || $u->hasPermission('tasks.manage') || $task->created_by == $u->id;
        if (!$canReopen) {
            return response()->json(['message' => 'You do not have permission to re-open this task.'], 403);
        }

        $task->update(['status' => 'pending']);
        $task->logHistory('reopened', "Task re-opened by " . $u->displayName(), $u->id);

        $participants = $task->getParticipants($u->id);
        foreach ($participants as $notifyUser) {
            FirebaseNotificationService::sendTaskNotification(
                $notifyUser,
                'Task Re-Opened: ' . $task->title,
                "Task has been re-opened by " . $u->displayName(),
                $task->id
            );
        }

        return response()->json([
            'ok' => true,
            'message' => 'Task re-opened successfully.',
            'data' => $this->formatTask($task->fresh(['creator', 'assignee', 'assignees', 'others', 'department', 'category', 'replies.user', 'attachments.user', 'histories.user'])),
        ]);
    }

    public function updateDeadline(Request $request, Task $task)
    {
        $u = $request->user();
        $canManage = $u->isAdmin() || $u->isSuperAdmin() || $u->hasPermission('tasks.manage') || $task->created_by == $u->id || $task->assigned_to == $u->id;
        if (!$canManage) {
            return response()->json(['message' => 'You do not have permission to update deadline or status.'], 403);
        }

        $data = $request->validate([
            'due_date' => 'nullable|date',
            'due_time' => 'nullable|string',
            'status' => 'nullable|in:pending,in_progress,completed',
            'remarks' => 'nullable|string',
        ]);

        $update = [];
        $logDetails = [];
        if (!empty($data['due_date'])) {
            $update['due_date'] = $data['due_date'];
            $logDetails[] = "New deadline: " . Carbon::parse($data['due_date'])->format('d M Y');
        }
        if (!empty($data['due_time'])) {
            $update['due_time'] = $data['due_time'];
            $logDetails[] = "Time: " . $data['due_time'];
        }
        if (!empty($data['status'])) {
            $update['status'] = $data['status'];
            $logDetails[] = "Status: " . strtoupper(str_replace('_', ' ', $data['status']));
        }

        if (!empty($update)) {
            $task->update($update);
            $msg = "Deadline/Status updated by " . $u->displayName() . ": " . implode(', ', $logDetails);
            if (!empty($data['remarks'])) {
                $msg .= " (Note: {$data['remarks']})";
            }
            $task->logHistory('deadline_updated', $msg, $u->id);

            $participants = $task->getParticipants($u->id);
            foreach ($participants as $notifyUser) {
                FirebaseNotificationService::sendTaskNotification(
                    $notifyUser,
                    'Task Deadline Updated: ' . $task->title,
                    $msg,
                    $task->id
                );
                MailService::sendTaskUpdatedMail($notifyUser, $task, $msg, $u);
            }
        }

        return response()->json([
            'ok' => true,
            'message' => 'Task deadline and status updated successfully.',
            'data' => $this->formatTask($task->fresh(['creator', 'assignee', 'assignees', 'others', 'department', 'category', 'replies.user', 'attachments.user', 'histories.user'])),
        ]);
    }

    public function updateDescription(Request $request, Task $task)
    {
        $u = $request->user();
        if ((int)$task->created_by !== (int)$u->id && !$u->isAdmin() && !$u->isSuperAdmin()) {
            return response()->json(['message' => 'Only the user who created this task or an Admin can edit the description.'], 403);
        }

        $data = $request->validate([
            'title' => 'nullable|string',
            'description' => 'nullable|string',
        ]);

        $updates = [];
        if ($request->filled('title')) {
            $updates['title'] = $data['title'];
        }
        if ($request->has('description')) {
            $updates['description'] = $data['description'];
        }

        if (!empty($updates)) {
            $task->update($updates);
            $task->logHistory('description_updated', "Task details updated by " . $u->displayName(), $u->id);
        }

        return response()->json([
            'ok' => true,
            'message' => 'Task details updated successfully.',
            'data' => $this->formatTask($task->fresh(['creator', 'assignee', 'assignees', 'others', 'department', 'category', 'replies.user', 'attachments.user', 'histories.user'])),
        ]);
    }

    public function updateCategory(Request $request, Task $task)
    {
        $u = $request->user();
        $isAssignee = $task->assigned_to == $u->id
            || $task->assignees()->where('users.id', $u->id)->exists();

        if (!$u->isAdmin() && !$u->isSuperAdmin() && $task->created_by != $u->id && !$isAssignee) {
            return response()->json(['message' => 'You do not have permission to update category for this task.'], 403);
        }

        $data = $request->validate([
            'category_id' => 'nullable|exists:task_categories,id',
        ]);

        $oldCatName = $task->category ? $task->category->name : 'General';
        $task->update(['category_id' => $data['category_id'] ?? null]);
        $task->load('category');
        $newCatName = $task->category ? $task->category->name : 'General';

        $task->logHistory('category_updated', "Category changed from '{$oldCatName}' to '{$newCatName}' by " . $u->displayName(), $u->id);

        return response()->json([
            'ok' => true,
            'message' => 'Task category updated successfully.',
            'data' => $this->formatTask($task->fresh(['creator', 'assignee', 'assignees', 'others', 'department', 'category', 'replies.user', 'attachments.user', 'histories.user'])),
        ]);
    }

    public function uploadAttachment(Request $request, Task $task)
    {
        $files = [];
        if ($request->hasFile('attachment_files')) {
            $files = array_merge($files, (array)$request->file('attachment_files'));
        }
        if ($request->hasFile('files')) {
            $files = array_merge($files, (array)$request->file('files'));
        }
        if ($request->hasFile('file')) {
            $files[] = $request->file('file');
        }

        if (empty($files)) {
            return response()->json(['message' => 'No files provided for upload.'], 422);
        }

        $uploaded = [];
        foreach ($files as $file) {
            if ($file && $file->isValid()) {
                $p = $file->store('task_attachments', 'public');
                $att = $task->attachments()->create([
                    'user_id' => $request->user()->id,
                    'file_path' => $p,
                    'file_name' => $file->getClientOriginalName(),
                ]);
                $uploaded[] = $att;
            }
        }

        $count = count($uploaded);
        $task->logHistory('attachment_added', "Uploaded {$count} attachment(s) by " . $request->user()->displayName(), $request->user()->id);

        return response()->json([
            'ok' => true,
            'message' => "{$count} file(s) attached successfully.",
            'data' => $this->formatTask($task->fresh(['creator', 'assignee', 'assignees', 'others', 'department', 'category', 'replies.user', 'attachments.user', 'histories.user'])),
        ]);
    }

    public function deleteAttachment(Request $request, TaskAttachment $attachment)
    {
        $u = $request->user();
        $task = $attachment->task;
        $canDelete = $u->isAdmin() || $u->isSuperAdmin() || $attachment->user_id == $u->id || ($task && $task->created_by == $u->id);
        if (!$canDelete) {
            return response()->json(['message' => 'You do not have permission to delete this attachment.'], 403);
        }

        if ($attachment->file_path && Storage::disk('public')->exists($attachment->file_path)) {
            Storage::disk('public')->delete($attachment->file_path);
        }
        $fName = $attachment->file_name;
        $attachment->delete();

        if ($task) {
            $task->logHistory('attachment_deleted', "Attachment '{$fName}' deleted by " . $u->displayName(), $u->id);
        }

        return response()->json(['ok' => true, 'message' => 'Attachment deleted successfully.']);
    }

    public function deleteReplyAttachment(Request $request, TaskReply $reply)
    {
        $u = $request->user();
        $isCreator = $reply->task && (int)$reply->task->created_by === (int)$u->id;
        $isAuthor = (int)$reply->user_id === (int)$u->id;
        $isAdmin = $u->isAdmin() || $u->isSuperAdmin();

        if (!$isAuthor && !$isCreator && !$isAdmin) {
            return response()->json(['ok' => false, 'message' => 'You do not have permission to delete this reply attachment.'], 403);
        }

        $filePath = $request->input('file_path');
        if (empty($filePath)) {
            return response()->json(['ok' => false, 'message' => 'File path is required.'], 400);
        }

        $cleanPath = ltrim(str_replace('/storage/', '', $filePath), '/');
        $allAtts = $reply->attachments_list;
        $foundKey = false;
        $matchedPath = null;

        foreach ($allAtts as $k => $att) {
            if ($att === $cleanPath || basename($att) === basename($cleanPath)) {
                $foundKey = $k;
                $matchedPath = $att;
                break;
            }
        }

        if ($foundKey === false) {
            return response()->json(['ok' => false, 'message' => 'Attachment not found.'], 404);
        }

        if (Storage::disk('public')->exists($matchedPath)) {
            Storage::disk('public')->delete($matchedPath);
        }

        unset($allAtts[$foundKey]);
        $remaining = array_values($allAtts);
        $reply->attachments = $remaining;
        $reply->attachment = count($remaining) > 0 ? $remaining[0] : null;
        $reply->save();

        return response()->json([
            'ok' => true,
            'message' => 'Reply attachment deleted successfully.',
            'remaining' => $remaining,
        ]);
    }

    public function destroy(Request $request, Task $task)
    {
        $u = $request->user();
        if ((int)$task->created_by !== (int)$u->id && !$u->isSuperAdmin() && !$u->isAdmin()) {
            return response()->json(['message' => 'Only the creator or Admin can delete this task.'], 403);
        }

        $task->delete();

        return response()->json(['ok' => true, 'message' => 'Task deleted successfully.']);
    }

    public function departmentEmployees(Request $request)
    {
        $departmentId = $request->get('department_id');
        $query = User::where('status', 'active');

        if ($departmentId) {
            $query->where('department_id', $departmentId);
        }

        $list = $query->orderBy('first_name')->get()
            ->filter(function ($u) {
                return $u->isSuperAdmin()
                    || $u->hasPermission('tasks.view')
                    || $u->hasPermission('tasks.create')
                    || $u->hasPermission('tasks.manage');
            })
            ->values()
            ->map(fn ($u) => [
                'id' => $u->id,
                'name' => $u->displayName(),
                'department_id' => $u->department_id,
                'department' => $u->department ?: ($u->department_id ? Department::find($u->department_id)?->name : '-'),
            ]);

        return response()->json(['data' => $list]);
    }

    public function categories(Request $request)
    {
        $companyId = $request->user()->company_id;
        $categories = TaskCategory::active()
            ->when($companyId, fn ($q) => $q->where(fn ($sub) => $sub->whereNull('company_id')->orWhere('company_id', $companyId)))
            ->orderBy('name')
            ->get();

        return response()->json(['data' => $categories]);
    }

    public function storeCategory(Request $request)
    {
        $u = $request->user();
        if (!$u->isSuperAdmin() && !$u->isAdmin() && !$u->hasPermission('tasks.create')) {
            return response()->json(['message' => 'Permission denied. Only users with task create permission can add categories.'], 403);
        }

        $data = $request->validate([
            'name' => 'required|string|max:100',
            'color' => 'nullable|string|max:20',
            'description' => 'nullable|string|max:255',
        ]);

        $category = TaskCategory::create([
            'name' => trim($data['name']),
            'color' => $data['color'] ?? '#6366f1',
            'description' => $data['description'] ?? null,
            'company_id' => $u->company_id,
            'created_by' => $u->id,
            'is_active' => true,
        ]);

        return response()->json([
            'ok' => true,
            'message' => 'Task category created successfully!',
            'data' => $category,
        ]);
    }

    private function formatTask(Task $t): array
    {
        return [
            'id' => $t->id,
            'title' => $t->title,
            'description' => $t->description,
            'status' => $t->status,
            'priority' => $t->priority,
            'is_pinned' => (bool)$t->is_pinned,
            'category_id' => $t->category_id,
            'category_name' => $t->category?->name,
            'category_color' => $t->category?->color,
            'repeat_type' => $t->repeat_type ?? 'none',
            'due_date' => optional($t->due_date)->toDateString(),
            'due_time' => $t->due_time,
            'client' => $t->client,
            'department_id' => $t->department_id,
            'department' => $t->department?->name,
            'assigned_to_id' => $t->assigned_to,
            'assigned_to_name' => $t->assignee?->displayName() ?? 'Unassigned',
            'created_by' => $t->created_by,
            'created_by_name' => $t->creator?->displayName() ?? 'Admin',
            'attachment_url' => $t->attachment ? asset('storage/' . $t->attachment) : null,
            'attachments' => $t->attachments ? $t->attachments->map(fn ($a) => [
                'id' => $a->id,
                'file_name' => $a->file_name ?? basename($a->file_path),
                'file_path' => $a->file_path,
                'file_url' => asset('storage/' . $a->file_path),
                'user_id' => $a->user_id,
                'user_name' => $a->user?->displayName() ?? 'Staff',
            ])->values() : [],
            'replies_count' => $t->replies ? $t->replies->count() : 0,
            'replies' => $t->replies ? $t->replies->map(fn ($r) => [
                'id' => $r->id,
                'user_id' => $r->user_id,
                'user_name' => $r->user?->displayName() ?? 'User',
                'user_photo' => $r->user?->profile_photo ? asset('storage/' . $r->user->profile_photo) : null,
                'message' => $r->message,
                'attachment_url' => $r->attachment ? asset('storage/' . $r->attachment) : null,
                'attachment_urls' => array_map(fn($path) => asset('storage/' . $path), $r->attachments_list ?? ($r->attachment ? [$r->attachment] : [])),
                'attachments' => array_map(fn($path) => [
                    'path' => $path,
                    'name' => basename($path),
                    'url' => asset('storage/' . $path),
                ], $r->attachments_list ?? ($r->attachment ? [$r->attachment] : [])),
                'created_at' => $r->created_at ? $r->created_at->format('d M Y, h:i A') : null,
            ]) : [],
            'histories' => $t->histories ? $t->histories->map(fn ($h) => [
                'id' => $h->id,
                'user_name' => $h->user?->displayName() ?? 'System',
                'action' => $h->action,
                'details' => $h->details,
                'created_at' => $h->created_at ? $h->created_at->format('d M Y, h:i A') : null,
            ]) : [],
            'assignees' => $t->assignees ? $t->assignees->map(fn ($u) => [
                'id' => $u->id,
                'name' => $u->displayName(),
            ]) : [],
            'others' => $t->others ? $t->others->map(fn ($u) => [
                'id' => $u->id,
                'name' => $u->displayName(),
            ]) : [],
            'created_at' => $t->created_at ? $t->created_at->format('d M Y, h:i A') : null,
        ];
    }
}
