@extends('layouts.app')
@section('content')
<div class="page-head">
    <h1><a href="{{ route('tasks.index') }}" style="color:inherit;text-decoration:none">←</a> Task Details</h1>
    <div class="row">
        <a class="btn light" href="{{ route('tasks.index') }}"><i class="fa-solid fa-arrow-left"></i> Back to Tasks</a>
        <form method="post" action="{{ route('tasks.destroy', $task) }}" onsubmit="return confirm('Are you sure you want to delete this task?')">
            @csrf @method('delete')
            <button class="btn red"><i class="fa-solid fa-trash"></i> Delete Task</button>
        </form>
    </div>
</div>

<div class="grid-2" style="gap:20px;align-items:flex-start">
    <!-- LEFT: Task Summary Overview Card -->
    <div class="card" style="padding:24px;border-radius:16px;background:#fff;border:1px solid #f3f4f6">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:14px">
            <span class="badge {{ $task->status==='completed'?'ok':($task->status==='in_progress'?'warn':'no') }}" style="font-size:12px;padding:4px 10px">
                {{ str_replace('_',' ', strtoupper($task->status)) }}
            </span>
            <span class="badge {{ $task->priority==='high'?'high':($task->priority==='low'?'low':'med') }}" style="font-size:12px;padding:4px 10px">
                {{ strtoupper($task->priority) }} PRIORITY
            </span>
        </div>

        <h2 style="margin:0 0 10px 0;font-size:22px;font-weight:800;color:#111827">{{ $task->title }}</h2>
        <p style="color:#4b5563;font-size:14px;line-height:1.6;margin-bottom:20px;background:#f9fafb;padding:12px;border-radius:10px;border:1px solid #f3f4f6">
            {{ $task->description ?: 'No detailed description provided.' }}
        </p>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;font-size:13px;color:#374151">
            <div>
                <span style="color:#6b7280;display:block;font-size:11px;text-transform:uppercase;font-weight:700;margin-bottom:2px">Client</span>
                <b>{{ $task->client ?: 'N/A' }}</b>
            </div>
            <div>
                <span style="color:#6b7280;display:block;font-size:11px;text-transform:uppercase;font-weight:700;margin-bottom:2px">Department</span>
                <b>{{ $task->department->name ?? 'General' }}</b>
            </div>
            <div>
                <span style="color:#6b7280;display:block;font-size:11px;text-transform:uppercase;font-weight:700;margin-bottom:2px">Due Date & Time</span>
                <b>{{ optional($task->due_date)->format('d M Y') ?: 'No Date' }} @ {{ $task->due_time ?: '12:00' }}</b>
            </div>
            <div>
                <span style="color:#6b7280;display:block;font-size:11px;text-transform:uppercase;font-weight:700;margin-bottom:2px">Primary Assignee</span>
                <b>{{ $task->assignee?->displayName() ?? 'Unassigned' }}</b>
            </div>
        </div>

        @if($task->others->count() > 0)
            <div style="margin-top:16px;padding-top:14px;border-top:1px solid #f3f4f6">
                <span style="color:#6b7280;display:block;font-size:11px;text-transform:uppercase;font-weight:700;margin-bottom:6px">Assigned To Others</span>
                <div style="display:flex;flex-wrap:wrap;gap:6px">
                    @foreach($task->others as $o)
                        <span class="chip" style="font-weight:600">{{ $o->displayName() }}</span>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    <!-- RIGHT: Edit Task Form -->
    <div class="card" style="padding:24px;border-radius:16px;background:#fff;border:1px solid #f3f4f6">
        <h3 style="margin:0 0 16px 0;font-size:18px;font-weight:700;color:#111827">Edit Task Details</h3>

        <form method="post" action="{{ route('tasks.update', $task) }}">
            @csrf @method('put')

            <label style="font-weight:600;font-size:13px;margin-bottom:4px;display:block">Task Title *</label>
            <input name="title" value="{{ $task->title }}" required style="margin-bottom:12px">

            <label style="font-weight:600;font-size:13px;margin-bottom:4px;display:block">Description</label>
            <textarea name="description" rows="3" style="margin-bottom:12px">{{ $task->description }}</textarea>

            <div class="grid-2" style="gap:12px;margin-bottom:12px">
                <div>
                    <label style="font-weight:600;font-size:13px;margin-bottom:4px;display:block">Status *</label>
                    <select name="status">
                        @foreach(['pending','in_progress','completed'] as $s)
                            <option value="{{ $s }}" {{ $task->status===$s?'selected':'' }}>{{ ucfirst(str_replace('_',' ',$s)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="font-weight:600;font-size:13px;margin-bottom:4px;display:block">Priority Level *</label>
                    <select name="priority">
                        @foreach(['low','medium','high'] as $p)
                            <option value="{{ $p }}" {{ $task->priority===$p?'selected':'' }}>{{ ucfirst($p) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid-2" style="gap:12px;margin-bottom:12px">
                <div>
                    <label style="font-weight:600;font-size:13px;margin-bottom:4px;display:block">Department</label>
                    <select name="department_id" id="editTaskDept" onchange="filterEditEmployees(this.value)">
                        <option value="">Select Department</option>
                        @foreach($departments as $d)
                            <option value="{{ $d->id }}" {{ $task->department_id==$d->id?'selected':'' }}>{{ $d->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="font-weight:600;font-size:13px;margin-bottom:4px;display:block">Client</label>
                    <input name="client" value="{{ $task->client }}" placeholder="Client name">
                </div>
            </div>

            <div class="grid-2" style="gap:12px;margin-bottom:12px">
                <div>
                    <label style="font-weight:600;font-size:13px;margin-bottom:4px;display:block">Due Date</label>
                    <input type="date" name="due_date" value="{{ optional($task->due_date)->toDateString() }}">
                </div>
                <div>
                    <label style="font-weight:600;font-size:13px;margin-bottom:4px;display:block">Due Time</label>
                    <input type="time" name="due_time" value="{{ $task->due_time ?: '12:00' }}">
                </div>
            </div>

            <div class="grid-2" style="gap:12px;margin-bottom:16px">
                <div>
                    <label style="font-weight:600;font-size:13px;margin-bottom:4px;display:block">Assigned To *</label>
                    <select name="assigned_to" id="editTaskEmp">
                        <option value="">Select Employee</option>
                        @foreach($employees as $e)
                            <option value="{{ $e->id }}" data-dept="{{ $e->department_id }}" {{ $task->assigned_to==$e->id?'selected':'' }}>
                                {{ $e->displayName() }} ({{ $e->department ?: 'N/A' }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="font-weight:600;font-size:13px;margin-bottom:4px;display:block">Assign To Others</label>
                    <select name="others[]" multiple style="height:42px">
                        @foreach($employees as $e)
                            <option value="{{ $e->id }}" {{ $task->others->contains($e->id)?'selected':'' }}>{{ $e->displayName() }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <button class="btn" style="width:100%;background:#111;color:#fff;padding:12px">
                <i class="fa-solid fa-floppy-disk"></i> Update Task Details
            </button>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
const allEmployeesList = @json($employees);
function filterEditEmployees(deptId) {
    const select = document.getElementById('editTaskEmp');
    const currentVal = select.value;
    select.innerHTML = '<option value="">Select Employee</option>';
    const filtered = deptId 
        ? allEmployeesList.filter(e => e.department_id == deptId || e.department == deptId) 
        : allEmployeesList;
    filtered.forEach(e => {
        const opt = document.createElement('option');
        opt.value = e.id;
        opt.textContent = (e.first_name || e.name || '') + ' ' + (e.last_name || '') + ' (' + (e.department || 'N/A') + ')';
        if (e.id == currentVal) opt.selected = true;
        select.appendChild(opt);
    });
}
</script>
@endsection

