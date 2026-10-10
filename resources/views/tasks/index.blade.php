@extends('layouts.app')
@section('content')
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;flex-wrap:wrap;gap:10px">
    <h1><i class="fa-solid fa-list-check" style="color:var(--accent)"></i> Task Management</h1>
    <div class="row" style="gap:8px">
        <!-- Calendar / List View Toggle -->
        <div style="background:#e2e8f0;padding:3px;border-radius:10px;display:flex">
            <button type="button" id="btnViewList" onclick="toggleTaskView('list')" class="btn" style="font-size:12px;padding:5px 12px;background:#fff;color:#111827;box-shadow:0 1px 3px rgba(0,0,0,0.1)">
                <i class="fa-solid fa-list"></i> List View
            </button>
            <button type="button" id="btnViewCalendar" onclick="toggleTaskView('calendar')" class="btn light" style="font-size:12px;padding:5px 12px;background:transparent;border:0;color:#64748b">
                <i class="fa-regular fa-calendar-days"></i> Calendar View
            </button>
        </div>

        <a class="btn light" href="{{ route('tasks.export.excel') }}"><i class="fa-solid fa-file-excel" style="color:#16a34a"></i> Excel</a>
        <a class="btn light" href="{{ route('tasks.export.pdf') }}" target="_blank"><i class="fa-solid fa-file-pdf" style="color:#dc2626"></i> PDF</a>
        <button class="btn" onclick="openNewTaskDrawer()"><i class="fa-solid fa-plus"></i> New Task</button>
    </div>
</div>

@if(session('ok'))
    <div style="background:#ecfdf5;color:#065f46;padding:10px 14px;border-radius:8px;margin-bottom:14px;border:1px solid #a7f3d0">
        <i class="fa-solid fa-circle-check"></i> {{ session('ok') }}
    </div>
@endif

<!-- COMPREHENSIVE FILTER BAR (Matching User, Company, Department, Overdue, Future Dated requirement) -->
<div class="card" style="padding:14px;margin-bottom:16px">
    <form method="get" class="row" style="gap:10px;flex-wrap:wrap;align-items:center">
        <input name="q" value="{{ $q }}" placeholder="Search by title, desc, client..." style="flex:1;min-width:200px">

        <select name="company_id" onchange="this.form.submit()" style="width:150px">
            <option value="">All Companies</option>
            @foreach($companies as $comp)
                <option value="{{ $comp->id }}" {{ $companyId==$comp->id?'selected':'' }}>{{ $comp->name }}</option>
            @endforeach
        </select>

        <select name="department_id" onchange="this.form.submit()" style="width:150px">
            <option value="">All Departments</option>
            @foreach($departments as $dept)
                <option value="{{ $dept->id }}" {{ $departmentId==$dept->id?'selected':'' }}>{{ $dept->name }}</option>
            @endforeach
        </select>

        <select name="user_id" onchange="this.form.submit()" style="width:160px">
            <option value="">All Assigned Users</option>
            @foreach($employees as $emp)
                <option value="{{ $emp->id }}" {{ $userId==$emp->id?'selected':'' }}>{{ $emp->displayName() }}</option>
            @endforeach
        </select>

        <select name="priority" onchange="this.form.submit()" style="width:130px">
            <option value="">All Priorities</option>
            <option value="high" {{ $priority==='high'?'selected':'' }}>High</option>
            <option value="medium" {{ $priority==='medium'?'selected':'' }}>Medium</option>
            <option value="low" {{ $priority==='low'?'selected':'' }}>Low</option>
        </select>

        <select name="date_filter" onchange="this.form.submit()" style="width:140px">
            <option value="">All Dates</option>
            <option value="overdue" {{ $dateFilter==='overdue'?'selected':'' }}>⚠️ Overdue</option>
            <option value="today" {{ $dateFilter==='today'?'selected':'' }}>📅 Due Today</option>
            <option value="future" {{ $dateFilter==='future'?'selected':'' }}>🔮 Future Dated</option>
        </select>

        <a class="btn light" href="{{ route('tasks.index') }}" style="font-size:12px">Clear Filters</a>
    </form>
</div>

<!-- STATUS BADGE TABS -->
<div class="tabs" style="margin-bottom:14px">
    <a class="{{ !$status?'active':'' }}" href="{{ route('tasks.index', request()->except('status')) }}">All ({{ $counts['all'] }})</a>
    <a class="{{ $status==='pending'?'active':'' }}" href="{{ route('tasks.index', array_merge(request()->query(), ['status'=>'pending'])) }}">Pending ({{ $counts['pending'] }})</a>
    <a class="{{ $status==='in_progress'?'active':'' }}" href="{{ route('tasks.index', array_merge(request()->query(), ['status'=>'in_progress'])) }}">In Progress ({{ $counts['in_progress'] }})</a>
    <a class="{{ $status==='completed'?'active':'' }}" href="{{ route('tasks.index', array_merge(request()->query(), ['status'=>'completed'])) }}">Completed ({{ $counts['completed'] }})</a>
    <a class="{{ $dateFilter==='overdue'?'active':'' }}" href="{{ route('tasks.index', array_merge(request()->query(), ['date_filter'=>'overdue'])) }}" style="color:#dc2626">Overdue ({{ $counts['overdue'] }})</a>
</div>

<!-- LIST VIEW CONTAINER -->
<div id="taskListView" class="card" style="padding:14px">
    <div style="overflow-x:auto">
        <table class="table" style="width:100%">
            <thead>
                <tr>
                    <th style="width:36px"><input type="checkbox" onchange="toggleTaskSelectAll(this)"></th>
                    <th>Task & Client</th>
                    <th>Description</th>
                    <th>Department</th>
                    <th>Due Date & Time</th>
                    <th>Priority</th>
                    <th>Status</th>
                    <th>Assigned User</th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $task)
                    <tr style="cursor:pointer" onclick="openTaskDetailsDrawer({{ json_encode($task->load(['assignee', 'assignees', 'others', 'department', 'replies.user', 'attachments', 'histories.user'])) }})" title="Click row to open details for {{ $task->title }}">
                        <td onclick="event.stopPropagation()"><input type="checkbox" class="task-checkbox" value="{{ $task->id }}"></td>
                        <td>
                            <strong style="color:#111827;font-size:14px">{{ $task->title }}</strong>
                            @if($task->replies && $task->replies->count() > 0)
                                <span class="chip" style="background:#e0e7ff;color:#3730a3;font-size:11px;margin-left:6px;font-weight:bold" onclick="event.stopPropagation(); openTaskDetailsDrawer({{ json_encode($task->load(['assignee', 'assignees', 'others', 'department', 'replies.user', 'attachments', 'histories.user'])) }}, 'reports')" title="View {{ $task->replies->count() }} replies">
                                    <i class="fa-solid fa-comments"></i> {{ $task->replies->count() }} {{ Str::plural('Reply', $task->replies->count()) }}
                                </span>
                            @endif
                            @if($task->client)
                                <div style="font-size:11px;color:#6b7280"><i class="fa-solid fa-user-tie"></i> Client: {{ $task->client }}</div>
                            @endif
                        </td>
                        <td style="max-width:260px">
                            <span class="muted" style="font-size:12px;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden">
                                {{ $task->description ?: '-' }}
                            </span>
                        </td>
                        <td>
                            <span class="chip" style="background:#f1f5f9;color:#475569"><i class="fa-solid fa-sitemap"></i> {{ $task->department->name ?? 'General' }}</span>
                        </td>
                        <td style="white-space:nowrap">
                            @if($task->due_date)
                                @php $isOverdue = $task->status !== 'completed' && $task->due_date->isPast() && !$task->due_date->isToday(); @endphp
                                <div style="{{ $isOverdue ? 'color:#dc2626;font-weight:bold' : '' }}">
                                    <i class="fa-regular fa-calendar"></i> {{ $task->due_date->format('Y-m-d') }}
                                </div>
                                @if($task->due_time)
                                    <small class="muted"><i class="fa-regular fa-clock"></i> {{ $task->due_time }}</small>
                                @endif
                            @else
                                <span class="muted">-</span>
                            @endif
                        </td>
                        <td>
                            @if($task->priority === 'high')
                                <span class="chip" style="background:#fee2e2;color:#991b1b;font-weight:700"><i class="fa-solid fa-circle-exclamation"></i> High</span>
                            @elseif($task->priority === 'medium')
                                <span class="chip" style="background:#fef3c7;color:#92400e;font-weight:600"><i class="fa-regular fa-clock"></i> Medium</span>
                            @else
                                <span class="chip" style="background:#f1f5f9;color:#475569">Low</span>
                            @endif
                        </td>
                        <td>
                            @if($task->status === 'completed')
                                <span class="chip" style="background:#dcfce7;color:#166534;font-weight:700">Completed</span>
                            @elseif($task->status === 'in_progress')
                                <span class="chip" style="background:#e0f2fe;color:#0369a1;font-weight:700">In Progress</span>
                            @else
                                <span class="chip" style="background:#fef9c3;color:#854d0e;font-weight:700">Pending</span>
                            @endif
                        </td>
                        <td>
                            <div style="display:flex;align-items:center;gap:6px">
                                @if($task->assignee)
                                    @if($task->assignee->profile_photo)
                                        <img src="{{ asset('storage/'.$task->assignee->profile_photo) }}" style="width:28px;height:28px;border-radius:50%;object-fit:cover">
                                    @else
                                        <div style="width:28px;height:28px;background:var(--accent);color:#fff;border-radius:50%;display:grid;place-items:center;font-size:11px;font-weight:700">
                                            {{ $task->assignee->initials() }}
                                        </div>
                                    @endif
                                    <span style="font-size:12px;font-weight:600">{{ $task->assignee->displayName() }}</span>
                                @else
                                    <span class="muted">Unassigned</span>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="muted" style="text-align:center;padding:24px">No tasks found matching current filters. Click "+ New Task" to create one.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div style="margin-top:14px">{{ $items->links() }}</div>
</div>

<!-- CALENDAR VIEW CONTAINER (Full Month Task Grid View) -->
<div id="taskCalendarView" class="card" style="display:none;padding:20px">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
        <h3 style="margin:0"><i class="fa-regular fa-calendar-days" style="color:var(--accent)"></i> Task Calendar — {{ now()->format('F Y') }}</h3>
        <span class="muted" style="font-size:12px">Click any task badge to view details</span>
    </div>

    <div style="display:grid;grid-template-columns:repeat(7, 1fr);gap:8px">
        @foreach(['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $dayName)
            <div style="font-weight:700;text-align:center;padding:8px;background:#f8fafc;border-radius:6px;font-size:13px;color:#475569">{{ $dayName }}</div>
        @endforeach

        @php
            $startOfMonth = now()->startOfMonth();
            $endOfMonth = now()->endOfMonth();
            $startDayOfWeek = $startOfMonth->dayOfWeek; // 0 = Sun
            $daysInMonth = now()->daysInMonth;
            $allTasksMap = $allTasksForCalendar->groupBy(fn($t) => optional($t->due_date)->format('Y-m-d'));
        @endphp

        <!-- Empty padding days for start of month -->
        @for($p=0; $p<$startDayOfWeek; $p++)
            <div style="background:#f8fafc;min-height:90px;border-radius:8px;opacity:0.4"></div>
        @endfor

        <!-- Month Days -->
        @for($d=1; $d<=$daysInMonth; $d++)
            @php
                $dateStr = now()->format('Y-m-') . sprintf('%02d', $d);
                $isToday = $dateStr === now()->toDateString();
                $dayTasks = $allTasksMap->get($dateStr, collect());
            @endphp
            <div style="background:{{ $isToday ? '#eff6ff' : '#fff' }};border:1px solid {{ $isToday ? '#3b82f6' : '#e2e8f0' }};min-height:100px;border-radius:8px;padding:6px">
                <div style="font-weight:700;font-size:12px;color:{{ $isToday ? '#1d4ed8' : '#334155' }};margin-bottom:4px">
                    {{ $d }} {{ $isToday ? '(Today)' : '' }}
                </div>
                
                <div style="display:flex;flex-direction:column;gap:4px">
                    @foreach($dayTasks as $cTask)
                        <div onclick="openTaskDetailsDrawer({{ json_encode($cTask->load(['assignee', 'assignees', 'others', 'department', 'replies.user', 'attachments', 'histories.user'])) }})" style="cursor:pointer;font-size:10px;padding:4px 6px;border-radius:4px;background:{{ $cTask->status==='completed' ? '#dcfce7' : ($cTask->priority==='high' ? '#fee2e2' : '#fef3c7') }};color:{{ $cTask->status==='completed' ? '#166534' : ($cTask->priority==='high' ? '#991b1b' : '#92400e') }};font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis" title="{{ $cTask->title }}">
                            {{ $cTask->title }}
                        </div>
                    @endforeach
                </div>
            </div>
        @endfor
    </div>
</div>

<!-- SLIDE-OVER RIGHT DRAWER FOR TASK CREATION / DETAILS -->
<div id="taskDrawerOverlay" onclick="closeTaskDrawer()" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.4);z-index:9990;transition:all 0.3s"></div>

<div id="taskDrawer" style="position:fixed;top:0;right:-540px;width:520px;max-width:92%;height:100vh;background:#fff;z-index:9995;box-shadow:-10px 0 25px rgba(0,0,0,0.15);transition:right 0.3s ease;display:flex;flex-direction:column;overflow-y:auto">
    
    <!-- Drawer Header -->
    <div style="padding:18px 24px;border-bottom:1px solid #e2e8f0;display:flex;justify-content:space-between;align-items:center;background:#fafafa">
        <h2 id="drawerHeaderTitle" style="margin:0;font-size:18px">New Task</h2>
        <button type="button" onclick="closeTaskDrawer()" style="border:0;background:none;font-size:22px;cursor:pointer;color:#64748b">&times;</button>
    </div>

    <!-- Drawer Content Body -->
    <div style="padding:24px;flex:1">
        
        <!-- NEW TASK FORM MODE -->
        <form id="newTaskForm" method="post" action="{{ route('tasks.store') }}" enctype="multipart/form-data">
            @csrf
            <div style="margin-bottom:12px">
                <label>Client</label>
                <input name="client" placeholder="Select or type client name...">
            </div>

            <div class="grid-2" style="margin-bottom:12px">
                <div>
                    <label>Department *</label>
                    <select name="department_id" id="newTaskDeptSelect" onchange="filterEmployeesByDept(this.value)">
                        <option value="">Select Department</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label>Assigned To Primary Employee *</label>
                    <select name="assigned_to" id="newTaskPrimaryEmpSelect" required>
                        <option value="">Select Employee</option>
                        @foreach($employees as $e)
                            <option value="{{ $e->id }}" data-dept="{{ $e->department_id }}">{{ $e->displayName() }} ({{ $e->department ?: 'N/A' }})</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid-2" style="margin-bottom:12px">
                <div>
                    <label>Task Status *</label>
                    <select name="status">
                        <option value="pending" selected>Pending</option>
                        <option value="in_progress">In Progress</option>
                        <option value="completed">Completed</option>
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

            <div style="margin-bottom:12px">
                <label>Repeat Task Option</label>
                <select name="repeat_type">
                    <option value="none">No Repeat (One-time)</option>
                    <option value="daily">Daily</option>
                    <option value="weekly">Weekly</option>
                    <option value="monthly">Monthly</option>
                    <option value="quarterly">Quarterly</option>
                    <option value="half_yearly">Half-Yearly (Semi-Annually)</option>
                    <option value="yearly">Yearly (Annually)</option>
                </select>
            </div>

            <label>Task Title *</label>
            <input name="title" placeholder="What needs to be done?" required style="margin-bottom:12px">

            <label>Description</label>
            <textarea name="description" rows="3" placeholder="Enter task details..." style="margin-bottom:12px"></textarea>

            <div class="grid-2" style="margin-bottom:12px">
                <div>
                    <label>Due Date</label>
                    <input type="date" name="due_date" value="{{ now()->toDateString() }}">
                </div>
                <div>
                    <label>Due Time</label>
                    <input type="time" name="due_time" value="12:00">
                </div>
            </div>

            <div style="margin-bottom:12px">
                <label>Attach File / Document</label>
                <input type="file" name="attachment_file">
            </div>

            <div style="margin-bottom:16px">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px">
                    <label style="margin:0">Assign To Others (Multiple Selection)</label>
                    <label style="font-size:12px;color:var(--accent);cursor:pointer;font-weight:bold">
                        <input type="checkbox" onchange="toggleSelectAllOthers(this)"> Select All
                    </label>
                </div>
                <select name="assignee_ids[]" id="newTaskOthersEmpSelect" multiple style="height:90px;width:100%;border-radius:8px;border:1px solid #cbd5e1">
                    @foreach($employees as $e)
                        <option value="{{ $e->id }}" data-dept="{{ $e->department_id }}">{{ $e->displayName() }} ({{ $e->department ?: 'Staff' }})</option>
                    @endforeach
                </select>
            </div>

            <button class="btn" style="width:100%;background:#111;color:#fff;padding:12px"><i class="fa-solid fa-paper-plane"></i> Create Task & Send Push Notification</button>
        </form>

        <!-- VIEW / EDIT TASK DETAILS MODE -->
        <div id="viewTaskDetails" style="display:none">
            
            <div style="display:flex;gap:12px;border-bottom:1px solid #e2e8f0;margin-bottom:16px;padding-bottom:8px">
                <button type="button" id="tabBtnDetails" onclick="switchDrawerTab('details')" style="border:0;background:none;font-weight:700;color:var(--accent);cursor:pointer">Details</button>
                <button type="button" id="tabBtnReports" onclick="switchDrawerTab('reports')" style="border:0;background:none;font-weight:600;color:#64748b;cursor:pointer">Reports & Replies (<span id="replyCount">0</span>)</button>
                <button type="button" id="tabBtnHistory" onclick="switchDrawerTab('history')" style="border:0;background:none;font-weight:600;color:#64748b;cursor:pointer">Task History (<span id="historyCount">0</span>)</button>
            </div>

            <!-- Tab 1: Details -->
            <div id="drawerTabDetails">
                <div style="background:#f8fafc;padding:14px;border-radius:10px;border:1px solid #e2e8f0;margin-bottom:14px">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
                        <span style="font-size:12px;font-weight:700;color:#64748b">TASK STATUS</span>
                        <span id="dtlStatusBadge" class="chip">Pending</span>
                    </div>
                    <h3 id="dtlTitle" style="margin:0 0 6px 0;color:#111827">Task Title</h3>
                    <p id="dtlDescription" class="muted" style="margin:0 0 10px 0;font-size:13px">Description...</p>
                    
                    <div class="grid-2" style="font-size:12px;color:#475569;margin-top:10px;border-top:1px dashed #cbd5e1;padding-top:10px">
                        <div><i class="fa-regular fa-calendar"></i> <b>Due Date:</b> <span id="dtlDueDate">-</span></div>
                        <div><i class="fa-regular fa-clock"></i> <b>Due Time:</b> <span id="dtlDueTime">-</span></div>
                        <div><i class="fa-solid fa-flag"></i> <b>Priority:</b> <span id="dtlPriority">-</span></div>
                        <div><i class="fa-solid fa-repeat"></i> <b>Repeat:</b> <span id="dtlRepeat">-</span></div>
                    </div>
                </div>

                <div style="background:#fff;padding:12px;border-radius:8px;border:1px solid #e2e8f0;margin-bottom:14px">
                    <strong style="display:block;margin-bottom:6px;font-size:13px">Assigned Team:</strong>
                    <div id="dtlAssigneesList" style="display:flex;align-items:center;gap:8px;flex-wrap:wrap"></div>
                </div>

                <!-- Action Buttons: Re-assign, Send Reminder & Delete -->
                <div style="display:flex;gap:8px;margin-bottom:14px">
                    <button type="button" class="btn light" style="flex:1" onclick="openReassignModal()"><i class="fa-solid fa-right-left"></i> Re-assign Task</button>
                    
                    <!-- Send Reminder Button -->
                    <form id="remindTaskForm" method="post" style="display:inline">
                        @csrf
                        <button class="btn light" style="color:#d97706" title="Send Push Notification reminder to assigned employees"><i class="fa-solid fa-bell"></i> Send Reminder</button>
                    </form>

                    <form id="deleteTaskForm" method="post" style="display:inline" onsubmit="return confirm('Delete this task?')">
                        @csrf @method('delete')
                        <button class="btn light" style="color:#dc2626"><i class="fa-solid fa-trash"></i> Delete</button>
                    </form>
                </div>
            </div>

            <!-- Tab 2: Replies & Attachments -->
            <div id="drawerTabReports" style="display:none">
                <strong style="display:block;margin-bottom:8px">Replies & Remarks Submission</strong>
                <div id="repliesContainer" style="max-height:240px;overflow-y:auto;margin-bottom:14px;display:flex;flex-direction:column;gap:8px"></div>

                <!-- Post Reply Form -->
                <form id="postReplyForm" method="post" enctype="multipart/form-data" style="background:#f8fafc;padding:12px;border-radius:8px;border:1px solid #e2e8f0">
                    @csrf
                    <textarea name="message" rows="2" placeholder="Write remark or progress update..." required style="margin-bottom:8px;width:100%"></textarea>
                    <div style="display:flex;justify-content:space-between;align-items:center">
                        <input type="file" name="reply_attachment" style="font-size:11px;width:200px">
                        <button class="btn light" style="font-size:12px"><i class="fa-solid fa-paper-plane"></i> Post Reply</button>
                    </div>
                </form>
            </div>

            <!-- Tab 3: Immutable Task History -->
            <div id="drawerTabHistory" style="display:none">
                <strong style="display:block;margin-bottom:8px;color:#dc2626"><i class="fa-solid fa-shield-halved"></i> Immutable Task Audit History (Cannot be edited or deleted)</strong>
                <div id="historyContainer" style="max-height:360px;overflow-y:auto;display:flex;flex-direction:column;gap:8px"></div>
            </div>

        </div>

    </div>
</div>

<!-- Modal: Re-Assign Task -->
<div id="reassignModal" class="modal-bg">
    <div class="modal" style="max-width:420px;z-index:10000">
        <h3 style="margin-top:0"><i class="fa-solid fa-right-left" style="color:var(--accent)"></i> Re-Assign Task</h3>
        <p class="muted" style="font-size:13px">If you feel this task belongs to someone else, re-assign it below. A push notification will be sent immediately to the new assignee.</p>
        <form id="reassignForm" method="post">
            @csrf
            <label style="margin-bottom:4px;display:block">Select New Assignee *</label>
            <select name="new_assigned_to" required style="margin-bottom:12px;width:100%">
                <option value="">Choose Employee</option>
                @foreach($employees as $e)
                    <option value="{{ $e->id }}">{{ $e->displayName() }} ({{ $e->department ?: 'Staff' }})</option>
                @endforeach
            </select>
            <label style="margin-bottom:4px;display:block">Reason / Remarks (Optional)</label>
            <textarea name="remarks" rows="2" placeholder="e.g. Belongs to Accounts Dept..." style="margin-bottom:14px;width:100%"></textarea>
            <div style="display:flex;justify-content:flex-end;gap:8px">
                <button type="button" class="btn light" onclick="closeReassignModal()">Cancel</button>
                <button class="btn">Re-Assign & Notify</button>
            </div>
        </form>
    </div>
</div>

<script>
let currentActiveTask = null;
const allEmployeesList = @json($employees);

function toggleTaskView(view) {
    if (view === 'list') {
        document.getElementById('taskListView').style.display = 'block';
        document.getElementById('taskCalendarView').style.display = 'none';
        document.getElementById('btnViewList').className = 'btn';
        document.getElementById('btnViewList').style.background = '#fff';
        document.getElementById('btnViewCalendar').className = 'btn light';
        document.getElementById('btnViewCalendar').style.background = 'transparent';
    } else {
        document.getElementById('taskListView').style.display = 'none';
        document.getElementById('taskCalendarView').style.display = 'block';
        document.getElementById('btnViewCalendar').className = 'btn';
        document.getElementById('btnViewCalendar').style.background = '#fff';
        document.getElementById('btnViewList').className = 'btn light';
        document.getElementById('btnViewList').style.background = 'transparent';
    }
}

function filterEmployeesByDept(deptId) {
    const primarySelect = document.getElementById('newTaskPrimaryEmpSelect');
    const othersSelect = document.getElementById('newTaskOthersEmpSelect');

    primarySelect.innerHTML = '<option value="">Select Employee</option>';
    othersSelect.innerHTML = '';

    const filtered = deptId 
        ? allEmployeesList.filter(e => e.department_id == deptId)
        : allEmployeesList;

    filtered.forEach(e => {
        const nameText = (e.first_name || e.name || '') + ' ' + (e.last_name || '') + ' (' + (e.department || 'Staff') + ')';
        primarySelect.add(new Option(nameText, e.id));
        othersSelect.add(new Option(nameText, e.id));
    });
}

function openNewTaskDrawer() {
    document.getElementById('drawerHeaderTitle').innerText = "New Task";
    document.getElementById('newTaskForm').style.display = 'block';
    document.getElementById('viewTaskDetails').style.display = 'none';
    document.getElementById('taskDrawerOverlay').style.display = 'block';
    document.getElementById('taskDrawer').style.right = '0';
}

function closeTaskDrawer() {
    document.getElementById('taskDrawerOverlay').style.display = 'none';
    document.getElementById('taskDrawer').style.right = '-540px';
}

function openTaskDetailsDrawer(task, initialTab = 'details') {
    currentActiveTask = task;
    document.getElementById('drawerHeaderTitle').innerText = "Task Details";
    document.getElementById('newTaskForm').style.display = 'none';
    document.getElementById('viewTaskDetails').style.display = 'block';

    document.getElementById('dtlTitle').innerText = task.title;
    document.getElementById('dtlDescription').innerText = task.description || 'No description provided.';
    document.getElementById('dtlDueDate').innerText = task.due_date ? task.due_date.substring(0, 10) : 'None';
    document.getElementById('dtlDueTime').innerText = task.due_time || '12:00';
    document.getElementById('dtlPriority').innerText = (task.priority || 'medium').toUpperCase();
    document.getElementById('dtlRepeat').innerText = (task.repeat_type || 'none').toUpperCase();

    // Status Badge
    const stBadge = document.getElementById('dtlStatusBadge');
    stBadge.innerText = (task.status || 'pending').replace('_', ' ').toUpperCase();

    // Assignees List
    const assigneesDiv = document.getElementById('dtlAssigneesList');
    assigneesDiv.innerHTML = '';
    if (task.assignee) {
        assigneesDiv.innerHTML += `<span class="chip" style="background:var(--accent);color:#fff">${task.assignee.first_name} ${task.assignee.last_name || ''}</span>`;
    }
    if (task.others && task.others.length > 0) {
        task.others.forEach(o => {
            assigneesDiv.innerHTML += `<span class="chip" style="background:#e0f2fe;color:#0369a1">${o.first_name} ${o.last_name || ''}</span>`;
        });
    }

    // Set forms
    document.getElementById('postReplyForm').action = "/tasks/" + task.id + "/reply";
    document.getElementById('remindTaskForm').action = "/tasks/" + task.id + "/remind";
    document.getElementById('deleteTaskForm').action = "/tasks/" + task.id;

    // Load Replies & Histories
    document.getElementById('replyCount').innerText = task.replies ? task.replies.length : 0;
    document.getElementById('historyCount').innerText = task.histories ? task.histories.length : 0;

    renderReplies(task.replies || []);
    renderHistories(task.histories || []);

    switchDrawerTab(initialTab);

    document.getElementById('taskDrawerOverlay').style.display = 'block';
    document.getElementById('taskDrawer').style.right = '0';
}

function renderReplies(replies) {
    const container = document.getElementById('repliesContainer');
    container.innerHTML = '';
    if (replies.length === 0) {
        container.innerHTML = '<div class="muted" style="font-size:12px">No replies yet.</div>';
        return;
    }
    replies.forEach(r => {
        let name = r.user ? (r.user.first_name + ' ' + (r.user.last_name || '')) : 'User';
        let fileHtml = r.attachment ? `<div style="margin-top:4px"><a href="/storage/${r.attachment}" target="_blank" style="color:var(--accent);font-size:11px"><i class="fa-solid fa-paperclip"></i> View Attachment</a></div>` : '';
        container.innerHTML += `
            <div style="background:#f8fafc;padding:10px;border-radius:8px;border:1px solid #e2e8f0;font-size:12px">
                <div style="display:flex;justify-content:space-between;font-weight:700;color:#111827">
                    <span>${name}</span>
                    <small class="muted">${r.created_at ? r.created_at.substring(0,10) : ''}</small>
                </div>
                <div style="margin-top:4px;color:#374151">${r.message}</div>
                ${fileHtml}
            </div>
        `;
    });
}

function renderHistories(histories) {
    const container = document.getElementById('historyContainer');
    container.innerHTML = '';
    if (histories.length === 0) {
        container.innerHTML = '<div class="muted" style="font-size:12px">No audit history recorded.</div>';
        return;
    }
    histories.forEach(h => {
        let uname = h.user ? h.user.first_name : 'System';
        container.innerHTML += `
            <div style="background:#fff;padding:8px 12px;border-radius:6px;border-left:3px solid var(--accent);box-shadow:0 1px 2px rgba(0,0,0,0.04);font-size:12px">
                <div style="font-weight:700;color:#111827">${h.action.toUpperCase()} by ${uname}</div>
                <div class="muted">${h.details || ''}</div>
                <small style="color:#94a3b8">${h.created_at ? h.created_at.substring(0,16).replace('T',' ') : ''}</small>
            </div>
        `;
    });
}

function switchDrawerTab(tab) {
    document.getElementById('drawerTabDetails').style.display = tab === 'details' ? 'block' : 'none';
    document.getElementById('drawerTabReports').style.display = tab === 'reports' ? 'block' : 'none';
    document.getElementById('drawerTabHistory').style.display = tab === 'history' ? 'block' : 'none';

    document.getElementById('tabBtnDetails').style.fontWeight = tab === 'details' ? '700' : '400';
    document.getElementById('tabBtnReports').style.fontWeight = tab === 'reports' ? '700' : '400';
    document.getElementById('tabBtnHistory').style.fontWeight = tab === 'history' ? '700' : '400';
}

function openReassignModal() {
    if (!currentActiveTask) return;
    closeTaskDrawer(); // CLOSE RIGHT DRAWER FIRST TO PREVENT MODAL OVERLAP
    document.getElementById('reassignForm').action = "/tasks/" + currentActiveTask.id + "/reassign";
    document.getElementById('reassignModal').classList.add('open');
}

function closeReassignModal() {
    document.getElementById('reassignModal').classList.remove('open');
}

function toggleSelectAllOthers(master) {
    const select = document.getElementById('newTaskOthersEmpSelect');
    for (let i = 0; i < select.options.length; i++) {
        select.options[i].selected = master.checked;
    }
}

function toggleTaskSelectAll(master) {
    document.querySelectorAll('.task-checkbox').forEach(cb => cb.checked = master.checked);
}
</script>
@endsection
