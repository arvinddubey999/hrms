<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $items = Task::query()
            ->with(['assignee', 'others'])
            ->where(function ($q) use ($user) {
                $q->where('assigned_to', $user->id)
                    ->orWhereHas('others', fn ($o) => $o->where('users.id', $user->id));
            })
            ->latest()
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
            ]);

        return response()->json(['data' => $items]);
    }

    public function updateStatus(Request $request, Task $task)
    {
        $data = $request->validate([
            'status' => 'required|in:pending,in_progress,completed',
        ]);
        $user = $request->user();
        $allowed = $task->assigned_to === $user->id || $task->others()->where('users.id', $user->id)->exists() || $user->isManager();
        if (! $allowed) {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        $task->update(['status' => $data['status']]);

        return response()->json(['data' => $task]);
    }
}
