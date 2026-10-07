<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Task;
use App\Models\User;
use App\Services\FirebaseNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Task::query()->with([
            'assignee',
            'assignees',
            'others',
            'department',
            'replies.user',
            'attachments',
            'histories.user',
        ]);

        if (!$user->isManager()) {
            $query->where(function ($q) use ($user) {
                $q->where('assigned_to', $user->id)
                    ->orWhere('created_by', $user->id)
                    ->orWhereHas('assignees', fn ($a) => $a->where('users.id', $user->id))
                    ->orWhereHas('others', fn ($o) => $o->where('users.id', $user->id));
            });
        }

        $items = $query->latest()->get()->map(fn (Task $t) => $this->formatTask($t));

        return response()->json(['data' => $items]);
    }

    public function show(Task $task)
    {
        $task->load([
            'assignee',
            'assignees',
            'others',
            'department',
            'replies.user',
            'attachments.user',
            'histories.user',
        ]);

        return response()->json(['data' => $this->formatTask($task)]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string',
            'description' => 'nullable|string',
            'client' => 'nullable|string',
            'status' => 'nullable|in:pending,in_progress,completed',
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
        $data['status'] = $data['status'] ?? 'pending';
        $data['repeat_type'] = $data['repeat_type'] ?? 'none';

        if ($request->hasFile('attachment_file')) {
            $data['attachment'] = $request->file('attachment_file')->store('task_attachments', 'public');
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

        if (!empty($data['attachment'])) {
            $task->attachments()->create([
                'user_id' => $request->user()->id,
                'file_path' => $data['attachment'],
                'file_name' => $request->file('attachment_file')->getClientOriginalName(),
            ]);
        }

        $task->logHistory('created', 'Task created via Mobile App', $request->user()->id);

        // Send FCM Push Notification to assigned users
        $targetUserIds = array_unique(array_filter(array_merge([$task->assigned_to], $assigneeIds, $others)));
        $notifyUsers = User::whereIn('id', $targetUserIds)->where('id', '!=', $request->user()->id)->get();

        foreach ($notifyUsers as $emp) {
            FirebaseNotificationService::sendTaskNotification(
                $emp,
                'New Task Assigned: ' . $task->title,
                'You have been assigned a new task due on ' . ($task->due_date ? $task->due_date->format('d M Y') : 'N/A'),
                $task->id
            );
        }

        return response()->json([
            'ok' => true,
            'message' => 'Task created successfully and notifications dispatched!',
            'data' => $this->formatTask($task->fresh(['assignee', 'assignees', 'others', 'department', 'replies.user', 'histories.user'])),
        ]);
    }

    public function updateStatus(Request $request, Task $task)
    {
        $data = $request->validate([
            'status' => 'required|in:pending,in_progress,completed',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        $user = $request->user();
        $allowed = $task->assigned_to === $user->id || $task->created_by === $user->id || $task->others()->where('users.id', $user->id)->exists() || $user->isManager();
        if (!$allowed) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $oldStatus = $task->status;
        $task->update($data);

        if ($oldStatus !== $task->status) {
            $task->logHistory('status_updated', "Status updated from {$oldStatus} to {$task->status}", $user->id);
        }

        return response()->json(['data' => $this->formatTask($task->fresh(['assignee', 'assignees', 'others', 'department', 'replies.user', 'histories.user']))]);
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

        // Notify creator & assignees
        $assigneeIds = $task->assignees()->pluck('users.id')->toArray();
        $targetUserIds = array_unique(array_filter(array_merge([$task->created_by, $task->assigned_to], $assigneeIds)));
        $notifyUsers = User::whereIn('id', $targetUserIds)->where('id', '!=', $request->user()->id)->get();

        foreach ($notifyUsers as $u) {
            FirebaseNotificationService::sendTaskNotification(
                $u,
                'New Reply on Task: ' . $task->title,
                $request->user()->displayName() . ': ' . Str::limit($data['message'], 60),
                $task->id
            );
        }

        return response()->json([
            'ok' => true,
            'message' => 'Reply posted successfully!',
            'data' => $this->formatTask($task->fresh(['assignee', 'assignees', 'others', 'department', 'replies.user', 'histories.user'])),
        ]);
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

        FirebaseNotificationService::sendTaskNotification(
            $newAssignee,
            'Task Re-Assigned To You: ' . $task->title,
            $logMsg,
            $task->id
        );

        return response()->json([
            'ok' => true,
            'message' => "Task re-assigned to {$newAssignee->displayName()} successfully!",
            'data' => $this->formatTask($task->fresh(['assignee', 'assignees', 'others', 'department', 'replies.user', 'histories.user'])),
        ]);
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

        foreach ($assignees as $u) {
            FirebaseNotificationService::sendTaskNotification(
                $u,
                'Task Reminder: ' . $task->title,
                $msg,
                $task->id
            );
        }

        $task->logHistory('reminded', "Reminder notification sent by {$senderName}", $request->user()->id);

        return response()->json([
            'ok' => true,
            'message' => 'Reminder notification sent successfully!',
        ]);
    }

    public function departmentEmployees(Request $request)
    {
        $departmentId = $request->get('department_id');
        $query = User::where('status', 'active');

        if ($departmentId) {
            $query->where('department_id', $departmentId);
        }

        $list = $query->orderBy('first_name')->get()->map(fn ($u) => [
            'id' => $u->id,
            'name' => $u->displayName(),
            'department_id' => $u->department_id,
            'department' => $u->department ?: ($u->department_id ? Department::find($u->department_id)?->name : '-'),
        ]);

        return response()->json(['data' => $list]);
    }

    private function formatTask(Task $t): array
    {
        return [
            'id' => $t->id,
            'title' => $t->title,
            'description' => $t->description,
            'status' => $t->status,
            'priority' => $t->priority,
            'repeat_type' => $t->repeat_type ?? 'none',
            'due_date' => optional($t->due_date)->toDateString(),
            'due_time' => $t->due_time,
            'client' => $t->client,
            'department_id' => $t->department_id,
            'department' => $t->department?->name,
            'assigned_to_id' => $t->assigned_to,
            'assigned_to_name' => $t->assignee?->displayName() ?? 'Unassigned',
            'created_by' => $t->created_by,
            'attachment_url' => $t->attachment ? asset('storage/' . $t->attachment) : null,
            'replies_count' => $t->replies ? $t->replies->count() : 0,
            'replies' => $t->replies ? $t->replies->map(fn ($r) => [
                'id' => $r->id,
                'user_id' => $r->user_id,
                'user_name' => $r->user?->displayName() ?? 'User',
                'user_photo' => $r->user?->profile_photo ? asset('storage/' . $r->user->profile_photo) : null,
                'message' => $r->message,
                'attachment_url' => $r->attachment ? asset('storage/' . $r->attachment) : null,
                'created_at' => $r->created_at ? $r->created_at->format('Y-m-d H:i') : null,
            ]) : [],
            'histories' => $t->histories ? $t->histories->map(fn ($h) => [
                'id' => $h->id,
                'user_name' => $h->user?->displayName() ?? 'System',
                'action' => $h->action,
                'details' => $h->details,
                'created_at' => $h->created_at ? $h->created_at->format('Y-m-d H:i') : null,
            ]) : [],
            'assignees' => $t->assignees ? $t->assignees->map(fn ($u) => [
                'id' => $u->id,
                'name' => $u->displayName(),
            ]) : [],
            'others' => $t->others ? $t->others->map(fn ($u) => [
                'id' => $u->id,
                'name' => $u->displayName(),
            ]) : [],
        ];
    }
}
