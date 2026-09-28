@extends('layouts.app')
@section('content')
<div class="page-head">
    <h1>Tasks</h1>
    <button class="btn" onclick="document.getElementById('newtask').classList.add('open')">+ New Task</button>
</div>
<div class="card">
    <form class="row" method="get">
        <input name="q" value="{{ $q }}" placeholder="Search by title, desc, due date or assign" style="flex:1">
        <button class="btn light">Search</button>
    </form>
    <div class="tabs">
        <a class="{{ !$status?'active':'' }}" href="{{ route('tasks.index') }}">All ({{ $counts['all'] }})</a>
        <a class="{{ $status==='pending'?'active':'' }}" href="{{ route('tasks.index',['status'=>'pending']) }}">Pending ({{ $counts['pending'] }})</a>
        <a class="{{ $status==='in_progress'?'active':'' }}" href="{{ route('tasks.index',['status'=>'in_progress']) }}">In Progress ({{ $counts['in_progress'] }})</a>
        <a class="{{ $status==='completed'?'active':'' }}" href="{{ route('tasks.index',['status'=>'completed']) }}">Completed ({{ $counts['completed'] }})</a>
    </div>
    <table class="table">
        <thead><tr><th>Task</th><th>Description</th><th>Due Date</th><th>Priority</th><th>Status</th></tr></thead>
        <tbody>
        @foreach($items as $task)
            <tr>
                <td><a href="{{ route('tasks.show', $task) }}"><b>{{ $task->title }}</b></a></td>
                <td>{{ $task->description ?: '-' }}</td>
                <td>{{ optional($task->due_date)->toDateString() }}</td>
                <td><span class="badge {{ $task->priority==='high'?'high':($task->priority==='low'?'low':'med') }}">{{ ucfirst($task->priority) }}</span></td>
                <td><span class="badge warn">{{ str_replace('_',' ', ucfirst($task->status)) }}</span></td>
            </tr>
        @endforeach
        </tbody>
    </table>
    {{ $items->links() }}
</div>

<div class="modal-bg" id="newtask">
    <form class="modal" method="post" action="{{ route('tasks.store') }}">
        @csrf
        <h2>New Task</h2>
        <label>Client</label><input name="client" placeholder="Select a client">
        <label>Task Status</label>
        <select name="status"><option value="pending">Pending</option><option value="in_progress">In Progress</option><option value="completed">Completed</option></select>
        <label>Task Title</label><input name="title" placeholder="What needs to be done?" required>
        <label>Description</label><textarea name="description" rows="3"></textarea>
        <div class="grid-2">
            <div><label>Due Date</label><input type="date" name="due_date" value="{{ now()->toDateString() }}"></div>
            <div><label>Due Time</label><input type="time" name="due_time" value="12:00"></div>
        </div>
        <label>Priority Level</label>
        <select name="priority">
            <option value="low">Low</option>
            <option value="medium" selected>Medium – Important task</option>
            <option value="high">High</option>
        </select>
        <label>Assigned To</label>
        <select name="assigned_to">
            <option value="">Select Employee</option>
            @foreach($employees as $e)<option value="{{ $e->id }}">{{ $e->displayName() }}</option>@endforeach
        </select>
        <label>Assign To Others</label>
        <select name="others[]" multiple size="6">
            @foreach($employees as $e)<option value="{{ $e->id }}">{{ $e->displayName() }}</option>@endforeach
        </select>
        <div class="row" style="margin-top:12px;justify-content:flex-end">
            <button type="button" class="btn light" onclick="document.getElementById('newtask').classList.remove('open')">Cancel</button>
            <button class="btn">Create Task</button>
        </div>
    </form>
</div>
@endsection
