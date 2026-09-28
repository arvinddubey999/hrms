<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->get('status');
        $q = $request->get('q');

        $items = Task::query()
            ->with(['assignee', 'others'])
            ->when($status && $status !== 'all', fn ($query) => $query->where('status', $status))
            ->when($q, fn ($query) => $query->where(function ($inner) use ($q) {
                $inner->where('title', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%");
            }))
            ->latest()
            ->paginate(15);

        $counts = [
            'all' => Task::count(),
            'pending' => Task::where('status', 'pending')->count(),
            'in_progress' => Task::where('status', 'in_progress')->count(),
            'completed' => Task::where('status', 'completed')->count(),
        ];

        return view('tasks.index', [
            'items' => $items,
            'counts' => $counts,
            'status' => $status,
            'q' => $q,
            'employees' => User::where('status', 'active')->orderBy('first_name')->get(),
            'categories' => Category::orderBy('name')->get(),
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
            'assigned_to' => 'nullable|exists:users,id',
            'others' => 'nullable|array',
        ]);
        $others = $data['others'] ?? [];
        unset($data['others']);
        $data['created_by'] = $request->user()->id;
        $task = Task::query()->create($data);
        $task->others()->sync($others);

        return back()->with('ok', 'Task created.');
    }

    public function show(Task $task)
    {
        $task->load(['assignee', 'others']);

        return view('tasks.show', [
            'task' => $task,
            'employees' => User::where('status', 'active')->orderBy('first_name')->get(),
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
            'assigned_to' => 'nullable|exists:users,id',
            'others' => 'nullable|array',
        ]);
        $others = $data['others'] ?? [];
        unset($data['others']);
        $task->update($data);
        $task->others()->sync($others);

        return back()->with('ok', 'Task updated.');
    }

    public function destroy(Task $task)
    {
        $task->delete();

        return redirect()->route('tasks.index')->with('ok', 'Task deleted.');
    }
}
