<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\TaskCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class TaskCategoryController extends Controller
{
    /**
     * Display a listing of task categories.
     * Accessible only by users with 'tasks.create' ("Create & Assign Tasks") or Super Admin.
     */
    public function index(Request $request)
    {
        $u = $request->user();
        if (!$u || (!$u->isSuperAdmin() && !$u->hasPermission('tasks.create'))) {
            return redirect()->route('tasks.index')->with('error', 'You do not have permission to access Task Categories. Only users with "Create & Assign Tasks" permission can manage categories.');
        }

        if (!Schema::hasTable('task_categories')) {
            return redirect()->route('tasks.index')->with('error', 'Task categories table does not exist.');
        }

        $categories = TaskCategory::with('creator')
            ->withCount('tasks')
            ->orderBy('name')
            ->get();

        $totalCategories = $categories->count();
        $myCategoriesCount = $categories->filter(fn ($c) => $c->created_by == $u->id)->count();
        $totalTaggedTasks = $categories->sum('tasks_count');

        return view('tasks.categories', compact(
            'categories',
            'u',
            'totalCategories',
            'myCategoriesCount',
            'totalTaggedTasks'
        ))->with('nav', 'task_categories');
    }

    /**
     * Store a newly created task category.
     * Supports both JSON (for modal in New Task drawer) and standard HTTP requests.
     */
    public function store(Request $request)
    {
        $u = $request->user();
        if (!$u || (!$u->isSuperAdmin() && !$u->hasPermission('tasks.create'))) {
            $msg = 'You do not have permission to add task categories. Only users with "Create & Assign Tasks" permission can create categories.';
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['ok' => false, 'message' => $msg], 403);
            }
            return back()->with('error', $msg);
        }

        if (!Schema::hasTable('task_categories')) {
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
            'created_by' => $u->id,
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

    /**
     * Update the specified task category.
     * Only the creator of the category or Admin/SuperAdmin can edit.
     */
    public function update(Request $request, TaskCategory $category)
    {
        $u = $request->user();
        if (!$u || (!$u->isSuperAdmin() && !$u->hasPermission('tasks.create'))) {
            return back()->with('error', 'You do not have permission to edit task categories.');
        }

        $canEdit = $u->isAdmin() || $u->isSuperAdmin() || ($category->created_by && $category->created_by == $u->id);
        if (!$canEdit) {
            return back()->with('error', 'You can only edit categories created by you. Admin has permission to edit all categories.');
        }

        $data = $request->validate([
            'name' => 'required|string|max:100|unique:task_categories,name,' . $category->id,
        ]);

        $oldName = $category->name;
        $category->update(['name' => $data['name']]);

        return back()->with('ok', "Category '{$oldName}' updated to '{$category->name}' successfully.");
    }

    /**
     * Remove the specified task category.
     * Only the creator of the category or Admin/SuperAdmin can delete.
     * Safely unlinks any tasks associated with this category without deleting the tasks.
     */
    public function destroy(Request $request, TaskCategory $category)
    {
        $u = $request->user();
        if (!$u || (!$u->isSuperAdmin() && !$u->hasPermission('tasks.create'))) {
            return back()->with('error', 'You do not have permission to delete task categories.');
        }

        $canDelete = $u->isAdmin() || $u->isSuperAdmin() || ($category->created_by && $category->created_by == $u->id);
        if (!$canDelete) {
            return back()->with('error', 'You can only delete categories created by you. Admin has permission to delete all categories.');
        }

        // Safely detach from existing tasks before deletion
        Task::where('category_id', $category->id)->update(['category_id' => null]);

        $catName = $category->name;
        $category->delete();

        return back()->with('ok', "Category '{$catName}' deleted successfully.");
    }
}

