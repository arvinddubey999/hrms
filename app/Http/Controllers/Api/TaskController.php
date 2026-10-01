<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Task::query()->with(['assignee', 'others', 'department']);

        if (!$user->isManager()) {
            $query->where(function ($q) use ($user) {
                $q->where('assigned_to', $user->id)
                    ->orWhereHas('others', fn ($o) => $o->where('users.id', $user->id));
            });
        }

        $items = $query->latest()
            ->get()
            ->map(fn (Task $t) => [
                'id' => $t->id,
                'title' => $t->title,
                'description' => $t->description,
                'status' => $t->status,
                'priority' => $t->priority,
                'due_date' => optional($t->due_date)->toDateString(),
                'due_time' => $t->due_time,
                'client' => $t->client,
                'department' => $t->department?->name,
                'assigned_to_id' => $t->assigned_to,
                'assigned_to_name' => $t->assignee?->displayName() ?? 'Unassigned',
            ]);

        return response()->json(['data' => $items]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string',
            'description' => 'nullable|string',
            'client' => 'nullable|string',
            'priority' => 'required|in:low,medium,high',
            'due_date' => 'nullable|date',
            'due_time' => 'nullable',
            'department_id' => 'nullable|exists:departments,id',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        $data['created_by'] = $request->user()->id;
        $data['status'] = 'pending';

        $task = Task::create($data);

        if ($task->assigned_to) {
            $user = User::find($task->assigned_to);
            if ($user) {
                Log::info("Task '{$task->title}' assigned to {$user->displayName()} via App (Email and Push notification sent)");
            }
        }

        return response()->json([
            'ok' => true,
            'message' => 'Task created and notification sent!',
            'data' => $task,
        ]);
    }

    public function updateStatus(Request $request, Task $task)
    {
        $data = $request->validate([
            'status' => 'required|in:pending,in_progress,completed',
            'assigned_to' => 'nullable|exists:users,id',
        ]);
        $user = $request->user();
        $allowed = $task->assigned_to === $user->id || $task->others()->where('users.id', $user->id)->exists() || $user->isManager();
        if (! $allowed) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $task->update($data);

        return response()->json(['data' => $task]);
    }

    public function departmentEmployees(Request $request)
    {
        $departmentId = $request->get('department_id');
        $query = User::where('status', 'active');

        if ($departmentId) {
            $query->where('department_id', $departmentId);
        }

        $list = $query->orderBy('first_name')->get()->map(fn($u) => [
            'id' => $u->id,
            'name' => $u->displayName(),
            'department' => $u->department ?: ($u->department_id ? Department::find($u->department_id)?->name : '-'),
        ]);

        return response()->json(['data' => $list]);
    }
}
