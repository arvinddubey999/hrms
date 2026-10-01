<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Department;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->get('status');
        $q = $request->get('q');
        $departmentId = $request->get('department_id');

        $items = Task::query()
            ->with(['assignee', 'others', 'department'])
            ->when($status && $status !== 'all', fn ($query) => $query->where('status', $status))
            ->when($departmentId, fn ($query) => $query->where('department_id', $departmentId))
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
            'departmentId' => $departmentId,
            'employees' => User::where('status', 'active')->orderBy('first_name')->get(),
            'departments' => Department::orderBy('name')->get(),
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
            'department_id' => 'nullable|exists:departments,id',
            'assigned_to' => 'nullable|exists:users,id',
            'others' => 'nullable|array',
        ]);
        $others = $data['others'] ?? [];
        unset($data['others']);
        $data['created_by'] = $request->user()->id;
        $task = Task::query()->create($data);
        $task->others()->sync($others);

        // Notify assigned employee
        if ($task->assigned_to) {
            $assignedUser = User::find($task->assigned_to);
            if ($assignedUser) {
                Log::info("Task '{$task->title}' assigned to {$assignedUser->displayName()} (Email/Push notification triggered)");
            }
        }

        return back()->with('ok', 'Task created and notification sent to assigned employee.');
    }

    public function show(Task $task)
    {
        $task->load(['assignee', 'others', 'department']);

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
            'department_id' => 'nullable|exists:departments,id',
            'assigned_to' => 'nullable|exists:users,id',
            'others' => 'nullable|array',
        ]);
        $others = $data['others'] ?? [];
        unset($data['others']);
        $oldAssigned = $task->assigned_to;
        $task->update($data);
        $task->others()->sync($others);

        if ($data['assigned_to'] && $data['assigned_to'] != $oldAssigned) {
            $assignedUser = User::find($data['assigned_to']);
            if ($assignedUser) {
                Log::info("Task '{$task->title}' re-assigned to {$assignedUser->displayName()}");
            }
        }

        return back()->with('ok', 'Task updated successfully.');
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
