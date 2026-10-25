<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Company;
use App\Models\Department;
use App\Models\Task;
use App\Models\TaskAttachment;
use App\Models\TaskCategory;
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
        // When date_filter is overdue, default to 'all' so all overdue tasks (pending + in_progress) display
        $dateFilter = $request->get('date_filter'); // overdue, today, future
        $status = $request->has('status') ? $request->get('status') : ($dateFilter === 'overdue' ? 'all' : 'pending');
        $priority = $request->get('priority');
        $q = $request->get('q');
        $departmentId = $request->get('department_id');
        $categoryId = $request->get('category_id');
        $companyId = $request->get('company_id');
        $userId = $request->get('user_id');
        $viewMode = $request->get('view_mode'); // 'user_wise' or null
        $sortBy = $request->get('sort_by', 'deadline');
        $sortDir = strtolower($request->get('sort_dir', 'asc')) === 'desc' ? 'desc' : 'asc';

        $query = $this->getFilteredTasksQuery($request);
        $items = $query->paginate(20)->withQueryString();

        // In user_wise mode, group tasks by assignee and sort tasks by closest deadline first
        $userWiseGroups = null;
        if ($viewMode === 'user_wise') {
            $allMatchingTasks = (clone $query)->get();
            $userWiseGroups = $allMatchingTasks->groupBy(function ($task) {
                return $task->assignee ? $task->assignee->displayName() : 'Unassigned';
            })->map(function ($grpTasks) use ($sortDir) {
                if ($sortDir === 'desc') {
                    return $grpTasks->sort(function ($a, $b) {
                        $ad = $a->due_date ? $a->due_date->format('Y-m-d') : '0000-00-00';
                        $bd = $b->due_date ? $b->due_date->format('Y-m-d') : '0000-00-00';
                        if ($ad === $bd) {
                            $at = $a->due_time ?: '00:00:00';
                            $bt = $b->due_time ?: '00:00:00';
                            return $bt <=> $at;
                        }
                        return $bd <=> $ad;
                    })->values();
                } else {
                    return $grpTasks->sort(function ($a, $b) {
                        $ad = $a->due_date ? $a->due_date->format('Y-m-d') : '9999-99-99';
                        $bd = $b->due_date ? $b->due_date->format('Y-m-d') : '9999-99-99';
                        if ($ad === $bd) {
                            $at = $a->due_time ?: '23:59:59';
                            $bt = $b->due_time ?: '23:59:59';
                            return $at <=> $bt;
                        }
                        return $ad <=> $bd;
                    })->values();
                }
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

        // Any active employee who has task access permissions (tasks.view, tasks.create, tasks.manage) in their role or is superadmin, regardless of company
        $companyEmployees = User::where('status', 'active')
            ->orderBy('first_name')
            ->get()
            ->filter(function ($emp) {
                return $emp->isSuperAdmin() || $emp->hasPermission('tasks.view') || $emp->hasPermission('tasks.create') || $emp->hasPermission('tasks.manage');
            })
            ->values();

        // Support opening a specific task directly via email/notification link (e.g. /tasks?open_task=47&tab=reports)
        $openTask = null;
        if ($request->filled('open_task')) {
            $taskId = (int)$request->get('open_task');
            $openTask = Task::with([
                'creator',
                'assignee',
                'assignees',
                'others',
                'department',
                'category',
                'replies.user',
                'attachments',
                'histories.user'
            ])->find($taskId);

            if ($openTask && !$u->isAdmin()) {
                $hasAccess = $openTask->assigned_to == $u->id
                    || $openTask->created_by == $u->id
                    || $openTask->assignees->contains('id', $u->id)
                    || $openTask->others->contains('id', $u->id);
                if (!$hasAccess) {
                    $openTask = null;
                }
            }
        }

        return view('tasks.index', [
            'items' => $items,
            'counts' => $counts,
            'status' => $status,
            'priority' => $priority,
            'q' => $q,
            'departmentId' => $departmentId,
            'categoryId' => $categoryId,
            'companyId' => $companyId,
            'userId' => $userId,
            'dateFilter' => $dateFilter,
            'viewMode' => $viewMode,
            'sortBy' => $sortBy,
            'sortDir' => $sortDir,
            'userWiseGroups' => $userWiseGroups,
            'employees' => $companyEmployees,
            'openTask' => $openTask,
            'departments' => Department::orderBy('name')->get(),
            'companies' => (!$u->isSuperAdmin() && !empty($u->accessibleCompanyIds())) ? Company::whereIn('id', $u->accessibleCompanyIds())->orderBy('name')->get() : Company::orderBy('name')->get(),
            'categories' => \Illuminate\Support\Facades\Schema::hasTable('task_categories') ? TaskCategory::orderBy('name')->get() : collect(),
            'allTasksForCalendar' => (clone $baseCountQuery)->with(['assignee'])->select(['id', 'title', 'due_date', 'due_time', 'priority', 'status', 'assigned_to'])->get(),
        ]);
    }

    public function store(Request $request)
    {
        $u = $request->user();
        if (!$u || (!$u->hasPermission('tasks.create') && !$u->hasPermission('tasks.manage'))) {
            return back()->with('error', 'You do not have permission to create & assign tasks.');
        }

        try {
            $hasTaskCategoriesTable = \Illuminate\Support\Facades\Schema::hasTable('task_categories');
            $rules = [
                'title' => 'required|string',
                'description' => 'nullable|string',
                'client' => 'nullable|string',
                'status' => 'required|in:pending,in_progress,completed',
                'priority' => 'required|in:low,medium,high',
                'due_date' => 'nullable|date',
                'due_time' => 'nullable',
                'repeat_type' => 'nullable|in:none,daily,weekly,monthly,quarterly,half_yearly,yearly',
                'department_id' => 'nullable|exists:departments,id',
                'category_id' => $hasTaskCategoriesTable ? 'nullable|exists:task_categories,id' : 'nullable',
                'assigned_to' => 'nullable|exists:users,id',
                'others' => 'nullable|array',
                'assignee_ids' => 'nullable|array',
                'attachment_file' => 'nullable|file|max:15360',
                'attachment_files' => 'nullable|array',
                'attachment_files.*' => 'nullable|file|max:15360',
            ];

            $data = $request->validate($rules);

            $rawOthers = $request->input('assignee_ids') ?? $request->input('others') ?? [];
            $others = is_array($rawOthers) ? $rawOthers : [$rawOthers];
            $assigneeIds = (isset($data['assignee_ids']) && is_array($data['assignee_ids'])) ? $data['assignee_ids'] : [];
            unset($data['others'], $data['assignee_ids'], $data['attachment_file'], $data['attachment_files']);

            $data['created_by'] = $request->user()->id;
            $data['repeat_type'] = $data['repeat_type'] ?? 'none';

            // Sanitize empty strings to NULL for database columns
            if (empty($data['due_date'])) $data['due_date'] = null;
            if (empty($data['due_time'])) $data['due_time'] = null;
            if (empty($data['department_id'])) $data['department_id'] = null;
            if (empty($data['category_id'])) $data['category_id'] = null;
            if (empty($data['assigned_to'])) $data['assigned_to'] = null;
            if (empty($data['client'])) $data['client'] = null;
            if (empty($data['description'])) $data['description'] = null;

            // Safety check if category_id column hasn't been migrated yet on live
            if (!\Illuminate\Support\Facades\Schema::hasColumn('tasks', 'category_id')) {
                unset($data['category_id']);
            }

            if ($request->hasFile('attachment_files')) {
                $first = $request->file('attachment_files')[0] ?? null;
                if ($first) {
                    $data['attachment'] = $first->store('task_attachments', 'public');
                }
            } elseif ($request->hasFile('attachment_file')) {
                $data['attachment'] = $request->file('attachment_file')->store('task_attachments', 'public');
            }

            $task = Task::query()->create($data);

            // Consolidate primary assigned_to, assignee_ids, and others so all assignees are saved without conflict
            $allAssignedIds = array_filter(array_unique(array_merge(
                !empty($task->assigned_to) ? [(int)$task->assigned_to] : [],
                array_map('intval', (array)$assigneeIds),
                array_map('intval', (array)$others)
            )));

            if (!empty($allAssignedIds)) {
                $task->assignees()->sync($allAssignedIds);
                if (empty($task->assigned_to)) {
                    $task->update(['assigned_to' => $allAssignedIds[0]]);
                }
            } elseif (!empty($task->assigned_to)) {
                $task->assignees()->sync([(int)$task->assigned_to]);
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
            $targetUserIds = array_filter(array_unique(array_merge([$task->assigned_to], $allAssignedIds)));
            $notifyUsers = User::whereIn('id', $targetUserIds)->get();
            foreach ($notifyUsers as $user) {
                // 1. FCM Push Notification
                \App\Services\FirebaseNotificationService::sendTaskNotification(
                    $user,
                    'New Task Assigned: ' . $task->title,
                    'You have been assigned a new task due on ' . ($task->due_date ? $task->due_date->format('d M Y') : 'N/A'),
                    $task->id
                );

                // 2. Email Notification
                \App\Services\MailService::sendTaskAssignedMail($user, $task, $request->user());
            }

            return back()->with('ok', 'Task created successfully and notifications dispatched via Push & Email.');
        } catch (\Illuminate\Validation\ValidationException $ve) {
            throw $ve;
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Task Store Error: ' . $e->getMessage(), [
                'exception' => $e,
                'request' => $request->except(['attachment_file', 'attachment_files']),
            ]);
            return back()->with('error', 'Failed to create task: ' . $e->getMessage())->withInput();
        }
    }

    public function show(Task $task, Request $request)
    {
        $tab = $request->get('tab', 'details');
        return redirect()->route('tasks.index', array_filter([
            'open_task' => $task->id,
            'tab' => $tab,
        ]));
    }

    public function update(Request $request, Task $task)
    {
        $hasTaskCategoriesTable = \Illuminate\Support\Facades\Schema::hasTable('task_categories');
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
            'category_id' => $hasTaskCategoriesTable ? 'nullable|exists:task_categories,id' : 'nullable',
            'assigned_to' => 'nullable|exists:users,id',
            'others' => 'nullable|array',
            'assignee_ids' => 'nullable|array',
        ]);

        $rawOthers = $request->input('assignee_ids') ?? $request->input('others') ?? [];
        $others = is_array($rawOthers) ? $rawOthers : [$rawOthers];
        $assigneeIds = (isset($data['assignee_ids']) && is_array($data['assignee_ids'])) ? $data['assignee_ids'] : [];
        unset($data['others'], $data['assignee_ids']);

        if (empty($data['due_date'])) $data['due_date'] = null;
        if (empty($data['due_time'])) $data['due_time'] = null;
        if (empty($data['department_id'])) $data['department_id'] = null;
        if (empty($data['category_id'])) $data['category_id'] = null;
        if (empty($data['assigned_to'])) $data['assigned_to'] = null;
        if (empty($data['client'])) $data['client'] = null;
        if (empty($data['description'])) $data['description'] = null;

        if (!\Illuminate\Support\Facades\Schema::hasColumn('tasks', 'category_id')) {
            unset($data['category_id']);
        }

        $oldStatus = $task->status;
        $task->update($data);

        $allAssignedIds = array_filter(array_unique(array_merge(
            !empty($task->assigned_to) ? [(int)$task->assigned_to] : [],
            array_map('intval', (array)$assigneeIds),
            array_map('intval', (array)$others)
        )));

        if (!empty($allAssignedIds)) {
            $task->assignees()->sync($allAssignedIds);
        }

        if ($oldStatus !== $task->status) {
            $task->logHistory('status_updated', "Status updated from {$oldStatus} to {$task->status}", $request->user()->id);
        } else {
            $task->logHistory('updated', 'Task details updated', $request->user()->id);
        }

        // Notify participants via FCM and Email
        $participants = $task->getParticipants($request->user()->id);
        foreach ($participants as $notifyUser) {
            \App\Services\FirebaseNotificationService::sendTaskNotification(
                $notifyUser,
                'Task Updated: ' . $task->title,
                $request->user()->displayName() . ' updated the task details.',
                $task->id
            );
            \App\Services\MailService::sendTaskUpdatedMail($notifyUser, $task, 'Task details were updated by ' . $request->user()->displayName(), $request->user());
        }

        return redirect()->route('tasks.index', [
            'open_task' => $task->id,
            'tab' => 'details',
        ])->with('ok', 'Task updated successfully and notifications dispatched.');
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

        // Notify task creator and assignees via FCM Push Notification & Email
        $participants = $task->getParticipants($request->user()->id);

        foreach ($participants as $user) {
            // 1. FCM Push Notification
            \App\Services\FirebaseNotificationService::sendTaskNotification(
                $user,
                'New Reply on Task: ' . $task->title,
                $request->user()->displayName() . ': ' . Str::limit($data['message'], 60),
                $task->id
            );

            // 2. Email Notification
            \App\Services\MailService::sendTaskReplyMail($user, $task, $reply, $request->user());
        }

        return redirect()->route('tasks.index', [
            'open_task' => $task->id,
            'tab' => 'reports',
        ])->with('ok', 'Reply posted successfully and notifications dispatched via Push & Email.');
    }

    public function reassign(Request $request, Task $task)
    {
        $rawAssignees = $request->input('new_assigned_to') ?: $request->input('assignees') ?: $request->input('assignee_ids');
        if (empty($rawAssignees)) {
            return back()->with('error', 'The new assigned to field is required. Please select at least one employee to re-assign.');
        }

        $rawAssignees = is_array($rawAssignees) ? $rawAssignees : [$rawAssignees];
        $assigneeIds = array_filter(array_map('intval', $rawAssignees));

        if (empty($assigneeIds)) {
            return back()->with('error', 'The new assigned to field is required. Please select at least one employee to re-assign.');
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
        if ($request->filled('remarks')) {
            $logMsg .= " (Reason: " . $request->input('remarks') . ")";
        }

        $task->logHistory('reassigned', $logMsg, $request->user()->id);

        // Send FCM Notification & Email to all new assignees
        foreach ($assignees as $newAssignee) {
            \App\Services\FirebaseNotificationService::sendTaskNotification(
                $newAssignee,
                'Task Re-Assigned To You: ' . $task->title,
                $logMsg,
                $task->id
            );
            \App\Services\MailService::sendTaskAssignedMail($newAssignee, $task, $request->user());
        }

        return redirect()->route('tasks.index', [
            'open_task' => $task->id,
            'tab' => 'details',
        ])->with('ok', "Task re-assigned to {$newNames} successfully.");
    }

    public function storeCategory(Request $request)
    {
        if (!\Illuminate\Support\Facades\Schema::hasTable('task_categories')) {
            $msg = 'Task categories table does not exist. Please run database migrations first.';
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['ok' => false, 'message' => $msg], 400);
            }
            return back()->with('error', $msg);
        }

        $data = $request->validate([
            'name' => 'required|string|max:100|unique:task_categories,name',
        ]);

        $category = TaskCategory::create([
            'name' => $data['name'],
            'created_by' => $request->user()?->id,
        ]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'ok' => true,
                'category' => $category,
                'message' => "Category '{$category->name}' created successfully.",
            ]);
        }

        return back()->with('ok', "Category '{$category->name}' created successfully.");
    }

    public function updateDeadline(Request $request, Task $task)
    {
        $u = $request->user();
        if (!$u) {
            return redirect()->route('login');
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

            // Notify all participants (creator + assignees) via Push & Email
            $participants = $task->getParticipants($u->id);
            foreach ($participants as $notifyUser) {
                \App\Services\FirebaseNotificationService::sendTaskNotification(
                    $notifyUser,
                    'Task Deadline Updated: ' . $task->title,
                    $msg,
                    $task->id
                );
                \App\Services\MailService::sendTaskUpdatedMail($notifyUser, $task, $msg, $u);
            }
        }

        return redirect()->route('tasks.index', [
            'open_task' => $task->id,
            'tab' => 'details',
        ])->with('ok', 'Task deadline and status updated successfully.');
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

        // Notify creator & all assignees via Push & Email
        $participants = $task->getParticipants($u->id);
        foreach ($participants as $notifyUser) {
            \App\Services\FirebaseNotificationService::sendTaskNotification(
                $notifyUser,
                'Task Status Updated: ' . $task->title,
                "Status changed from '{$oldLabel}' to '{$newLabel}' by " . $u->displayName(),
                $task->id
            );
            \App\Services\MailService::sendTaskStatusUpdatedMail($notifyUser, $task, $oldStatus, $task->status, $u);
        }

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

                return redirect()->route('tasks.index', [
                    'open_task' => $task->id,
                    'tab' => 'details',
                ])->with('ok', "Task status updated to '{$newLabel}'. Next recurring task for {$nextDate->format('d M Y')} has been automatically created!");
            }
        }

        return redirect()->route('tasks.index', [
            'open_task' => $task->id,
            'tab' => 'details',
        ])->with('ok', "Task status updated to '{$newLabel}' successfully and notifications dispatched.");
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

        $participants = $task->getParticipants($u->id);
        foreach ($participants as $notifyUser) {
            \App\Services\FirebaseNotificationService::sendTaskNotification(
                $notifyUser,
                'Task Re-Opened: ' . $task->title,
                $msg,
                $task->id
            );
            \App\Services\MailService::sendTaskUpdatedMail($notifyUser, $task, $msg, $u);
        }

        return redirect()->route('tasks.index', [
            'open_task' => $task->id,
            'tab' => 'details',
        ])->with('ok', 'Task re-opened successfully with updated deadline.');
    }

    public function exportRepliesPdf(Task $task)
    {
        $task->load(['creator', 'assignee', 'assignees', 'others', 'department', 'replies.user', 'attachments.user', 'histories.user']);
        // dd($task);
        return view('tasks.replies-pdf', compact('task'));
    }

    public function deleteAttachment(TaskAttachment $attachment)
    {
        $taskId = $attachment->task_id;
        if ($attachment->file_path) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($attachment->file_path);
        }
        $attachment->delete();
        return redirect()->route('tasks.index', [
            'open_task' => $taskId,
            'tab' => 'details',
        ])->with('ok', 'Attachment removed successfully.');
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

        foreach ($assignees as $user) {
            // 1. FCM Push Notification
            \App\Services\FirebaseNotificationService::sendTaskNotification(
                $user,
                'Task Reminder: ' . $task->title,
                $msg,
                $task->id
            );

            // 2. Email Reminder
            \App\Services\MailService::sendTaskReminderMail($user, $task, $request->user());
        }

        $task->logHistory('reminded', "Reminder notification and email sent by {$senderName}", $request->user()->id);

        return redirect()->route('tasks.index', [
            'open_task' => $task->id,
            'tab' => 'reports',
        ])->with('ok', 'Reminder notification and email sent to assigned team members.');
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

        return redirect()->route('tasks.index', [
            'open_task' => $task->id,
            'tab' => 'details',
        ])->with('ok', 'Attachment uploaded.');
    }

    public function destroy(Request $request, Task $task)
    {
        $u = $request->user();
        if (!$u) {
            return redirect()->route('login');
        }

        // Only the creator who created this task (or Super Admin) can delete it
        if ((int)$task->created_by !== (int)$u->id && !$u->isSuperAdmin()) {
            return back()->with('error', 'Only the creator who created this task can delete it. Other employees cannot delete the task.');
        }

        $task->delete();

        return redirect()->route('tasks.index')->with('ok', 'Task deleted successfully.');
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

        $dateFilter = $request->get('date_filter'); // overdue, today, future
        // When filtering by overdue without explicit status, default to 'all' so all overdue tasks show
        $status = $request->has('status') ? $request->get('status') : ($dateFilter === 'overdue' ? 'all' : 'pending');
        $priority = $request->get('priority');
        $q = $request->get('q');
        $departmentId = $request->get('department_id');
        $categoryId = $request->get('category_id');
        $companyId = $request->get('company_id');
        $userId = $request->get('user_id');

        $sortBy = $request->get('sort_by', 'deadline');
        $sortDir = strtolower($request->get('sort_dir', 'asc')) === 'desc' ? 'desc' : 'asc';

        $query = Task::query()
            ->with(['creator', 'assignee', 'assignees', 'others', 'department', 'category', 'replies.user', 'attachments', 'histories.user'])
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
            ->when($categoryId, fn ($q) => $q->where('category_id', $categoryId))
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
                $query->orderByRaw('due_date IS NULL, due_date DESC, due_time DESC, id DESC');
            } else {
                // ASC: nearest upcoming deadline first (e.g. 16 Oct, then 20 Oct, then 10 Dec)
                $query->orderByRaw('due_date IS NULL, due_date ASC, due_time ASC, id ASC');
            }
        } else {
            $query->latest();
        }

        return $query;
    }
}
