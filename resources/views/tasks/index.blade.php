@extends('layouts.app')
@section('content')
<div class="page-head">
    <h1>Task Management</h1>
    <div class="row">
        <a class="btn light" href="{{ route('tasks.export.excel') }}"><i class="fa-solid fa-file-excel"></i> Export Excel</a>
        <a class="btn light" href="{{ route('tasks.export.pdf') }}" target="_blank"><i class="fa-solid fa-file-pdf"></i> Export PDF</a>
        <button class="btn" onclick="document.getElementById('newtask').classList.add('open')">+ New Task</button>
    </div>
</div>

<div class="card">
    <form class="row" method="get" style="margin-bottom:12px;gap:8px">
        <input name="q" value="{{ $q }}" placeholder="Search by title, description..." style="flex:1">
        <select name="department_id" onchange="this.form.submit()" style="width:180px">
            <option value="">All Departments</option>
            @foreach($departments as $dept)
                <option value="{{ $dept->id }}" {{ $departmentId==$dept->id?'selected':'' }}>{{ $dept->name }}</option>
            @endforeach
        </select>
        <button class="btn light">Filter</button>
    </form>

    <div class="tabs">
        <a class="{{ !$status?'active':'' }}" href="{{ route('tasks.index') }}">All ({{ $counts['all'] }})</a>
        <a class="{{ $status==='pending'?'active':'' }}" href="{{ route('tasks.index',['status'=>'pending']) }}">Pending ({{ $counts['pending'] }})</a>
        <a class="{{ $status==='in_progress'?'active':'' }}" href="{{ route('tasks.index',['status'=>'in_progress']) }}">In Progress ({{ $counts['in_progress'] }})</a>
        <a class="{{ $status==='completed'?'active':'' }}" href="{{ route('tasks.index',['status'=>'completed']) }}">Completed ({{ $counts['completed'] }})</a>
    </div>

    <!-- Small Compact Card Layout for Tasks -->
    <div style="display:grid;grid-template-columns:repeat(auto-fill, minmax(320px, 1fr));gap:12px;margin-top:14px">
        @forelse($items as $task)
            <div style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:12px 14px;box-shadow:0 1px 3px rgba(0,0,0,0.05)">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:6px">
                    <a href="{{ route('tasks.show', $task) }}" style="font-weight:700;font-size:15px;color:var(--text)">
                        {{ $task->title }}
                    </a>
                    <span class="badge {{ $task->priority==='high'?'high':($task->priority==='low'?'low':'med') }}" style="font-size:10px;padding:2px 8px">
                        {{ strtoupper($task->priority) }}
                    </span>
                </div>
                
                <p class="muted" style="font-size:12px;margin:4px 0 8px 0;line-clamp:2;display:-webkit-box;-webkit-box-orient:vertical;overflow:hidden">
                    {{ $task->description ?: 'No detailed description.' }}
                </p>

                <div style="font-size:11px;color:#6b7280;display:flex;flex-wrap:wrap;gap:10px;margin-bottom:8px">
                    <div><i class="fa-solid fa-sitemap"></i> {{ $task->department->name ?? 'General' }}</div>
                    <div><i class="fa-solid fa-user"></i> {{ $task->assignee?->displayName() ?? 'Unassigned' }}</div>
                    <div><i class="fa-solid fa-calendar-day"></i> {{ optional($task->due_date)->format('d M Y') ?: 'No Due' }}</div>
                </div>

                <div style="display:flex;justify-content:space-between;align-items:center;border-top:1px solid #f3f4f6;padding-top:8px">
                    <span class="badge {{ $task->status==='completed'?'ok':($task->status==='in_progress'?'warn':'no') }}" style="font-size:10px">
                        {{ str_replace('_',' ', strtoupper($task->status)) }}
                    </span>
                    <a href="{{ route('tasks.show', $task) }}" class="btn light" style="font-size:11px;padding:3px 8px">View Details</a>
                </div>
            </div>
        @empty
            <div style="grid-column:1/-1;text-align:center;padding:30px" class="muted">No tasks found.</div>
        @endforelse
    </div>

    <div style="margin-top:16px">{{ $items->links() }}</div>
</div>

<!-- Modal: New Task Creation with Department Dependent Filtering -->
<!-- Modal: New Task Creation matching Screenshot design -->
<div class="modal-bg" id="newtask">
    <form class="modal" method="post" action="{{ route('tasks.store') }}" style="max-width:620px;padding:24px;border-radius:16px">
        @csrf
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;border-bottom:1px solid #f3f4f6;padding-bottom:10px">
            <h2 style="margin:0;font-size:20px;font-weight:700">New Task</h2>
            <button type="button" class="btn light" onclick="document.getElementById('newtask').classList.remove('open')" style="font-size:16px;padding:4px 10px">&times;</button>
        </div>

        <div class="grid-2" style="margin-bottom:10px">
            <div>
                <label>Client</label>
                <input name="client" placeholder="Select or type client name...">
            </div>
            <div>
                <label>Task Status *</label>
                <select name="status">
                    <option value="pending" selected>Pending</option>
                    <option value="in_progress">In Progress</option>
                    <option value="completed">Completed</option>
                </select>
            </div>
        </div>
        
        <label>Task Title *</label>
        <input name="title" placeholder="What needs to be done?" required style="margin-bottom:10px">

        <label>Description</label>
        <textarea name="description" rows="2" placeholder="Task description..." style="margin-bottom:10px"></textarea>

        <div class="grid-2" style="margin-bottom:10px">
            <div>
                <label>Department *</label>
                <select name="department_id" id="taskDeptSelect" onchange="filterEmployeesByDepartment(this.value)">
                    <option value="">Select Department</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label>Priority Level *</label>
                <select name="priority">
                    <option value="low">Low Priority</option>
                    <option value="medium" selected>Medium - Important task</option>
                    <option value="high">High Priority</option>
                </select>
            </div>
        </div>

        <div class="grid-2" style="margin-bottom:10px">
            <div>
                <label>Due Date</label>
                <input type="date" name="due_date" value="{{ now()->toDateString() }}">
            </div>
            <div>
                <label>Due Time</label>
                <input type="time" name="due_time" value="12:00">
            </div>
        </div>

        <div class="grid-2" style="margin-bottom:10px">
            <div>
                <label>Assigned To *</label>
                <select name="assigned_to" id="taskEmpSelect">
                    <option value="">Select Employee</option>
                    @foreach($employees as $e)
                        <option value="{{ $e->id }}" data-dept="{{ $e->department_id }}">{{ $e->displayName() }} ({{ $e->department ?: 'N/A' }})</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label>Assign To Others</label>
                <select name="others[]" multiple style="height:42px">
                    @foreach($employees as $e)
                        <option value="{{ $e->id }}">{{ $e->displayName() }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <p class="muted" style="font-size:11px;margin-top:10px">Note: Email & Push notification will be sent automatically to the assigned team members.</p>

        <div class="row" style="margin-top:16px;justify-content:flex-end;gap:10px">
            <button type="button" class="btn light" onclick="document.getElementById('newtask').classList.remove('open')">Cancel</button>
            <button class="btn" style="background:#111;color:#fff">Create Task</button>
        </div>
    </form>
</div>
@endsection

@section('scripts')
<script>
const taskEmployees = @json($employees);
function filterEmployeesByDepartment(deptId) {
    const select = document.getElementById('taskEmpSelect');
    select.innerHTML = '<option value="">Select Employee</option>';
    const filtered = deptId 
        ? taskEmployees.filter(e => e.department_id == deptId || e.department == deptId) 
        : taskEmployees;
    filtered.forEach(e => {
        const opt = document.createElement('option');
        opt.value = e.id;
        opt.textContent = (e.first_name || e.name || '') + ' ' + (e.last_name || '') + ' (' + (e.department || 'N/A') + ')';
        select.appendChild(opt);
    });
}
</script>
@endsection
