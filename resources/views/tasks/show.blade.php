@extends('layouts.app')
@section('content')
<div class="page-head">
    <h1>Task details</h1>
    <div class="row">
        <a class="btn light" href="{{ route('tasks.index') }}">Back</a>
        <form method="post" action="{{ route('tasks.destroy', $task) }}" onsubmit="return confirm('Delete task?')">@csrf @method('delete')<button class="btn red">Delete</button></form>
    </div>
</div>
<div class="card">
    <p>Task Status <span class="badge ok" style="float:right">{{ $task->status }}</span></p>
    <p>Task Title <span style="float:right">{{ $task->title }}</span></p>
    <p>Description <span style="float:right">{{ $task->description }}</span></p>
    <p>Due {{ optional($task->due_date)->toDateString() }} {{ $task->due_time }}</p>
    <p>Priority <span class="badge med">{{ $task->priority }}</span></p>
    <p>Assigned To {{ $task->assignee?->displayName() }}</p>
    <p>Assigned To Others: {{ $task->others->pluck('name')->join(', ') }}</p>
</div>
<form class="card" style="margin-top:12px" method="post" action="{{ route('tasks.update', $task) }}">
    @csrf @method('put')
    <label>Title</label><input name="title" value="{{ $task->title }}">
    <label>Description</label><textarea name="description">{{ $task->description }}</textarea>
    <label>Status</label>
    <select name="status">
        @foreach(['pending','in_progress','completed'] as $s)<option value="{{ $s }}" {{ $task->status===$s?'selected':'' }}>{{ $s }}</option>@endforeach
    </select>
    <label>Priority</label>
    <select name="priority">
        @foreach(['low','medium','high'] as $s)<option value="{{ $s }}" {{ $task->priority===$s?'selected':'' }}>{{ $s }}</option>@endforeach
    </select>
    <input type="date" name="due_date" value="{{ optional($task->due_date)->toDateString() }}">
    <input type="time" name="due_time" value="{{ $task->due_time }}">
    <input name="client" value="{{ $task->client }}" placeholder="Client">
    <select name="assigned_to">
        @foreach($employees as $e)<option value="{{ $e->id }}" {{ $task->assigned_to==$e->id?'selected':'' }}>{{ $e->displayName() }}</option>@endforeach
    </select>
    <select name="others[]" multiple>
        @foreach($employees as $e)<option value="{{ $e->id }}" {{ $task->others->contains($e->id)?'selected':'' }}>{{ $e->displayName() }}</option>@endforeach
    </select>
    <button class="btn" style="margin-top:12px">Edit / Save</button>
</form>
@endsection
