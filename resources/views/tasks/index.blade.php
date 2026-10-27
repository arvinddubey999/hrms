@extends('layouts.app')
@section('content')
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700;800&display=swap">
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

        <a class="btn light" href="{{ route('tasks.export.excel', request()->query()) }}"><i class="fa-solid fa-file-excel" style="color:#16a34a"></i> Excel</a>
        <a class="btn light" href="{{ route('tasks.export.pdf', request()->query()) }}" target="_blank"><i class="fa-solid fa-file-pdf" style="color:#dc2626"></i> PDF</a>
        <button class="btn" onclick="openNewTaskDrawer()"><i class="fa-solid fa-plus"></i> New Task</button>
    </div>
</div>

<!-- COMPREHENSIVE FILTER BAR (Matching User, Company, Department, Overdue, Future Dated requirement) -->
<div class="card" style="padding:14px;margin-bottom:16px">
    <form method="get" id="taskFilterForm" class="row" style="gap:10px;flex-wrap:wrap;align-items:center">
        <div style="position:relative;flex:1;min-width:240px">
            <input type="text" name="q" id="taskSearchInput" value="{{ $q }}" oninput="debounceTaskSearch(this)" placeholder="Search by title, desc, client..." style="width:100%;padding-right:85px" autocomplete="off">
            <button type="submit" class="btn" style="position:absolute;right:3px;top:50%;transform:translateY(-50%);padding:6px 12px;font-size:12px;display:flex;align-items:center;gap:4px;border-radius:6px" title="Search">
                <i class="fa-solid fa-magnifying-glass"></i> Search
            </button>
        </div>

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

        <select name="category_id" onchange="this.form.submit()" style="width:150px">
            <option value="">All Categories</option>
            @foreach($categories as $cat)
                <option value="{{ $cat->id }}" {{ $categoryId==$cat->id?'selected':'' }}>{{ $cat->name }}</option>
            @endforeach
        </select>

        <select name="from_user_id" onchange="this.form.submit()" style="width:160px" title="Filter by user who created & assigned the task">
            <option value="">From User (All)</option>
            @foreach($employees as $emp)
                <option value="{{ $emp->id }}" {{ ($fromUserId ?? null)==$emp->id?'selected':'' }}>From: {{ $emp->displayName() }}</option>
            @endforeach
        </select>

        <select name="user_id" onchange="this.form.submit()" style="width:160px" title="Filter by assigned user">
            <option value="">To User (All)</option>
            @foreach($employees as $emp)
                <option value="{{ $emp->id }}" {{ $userId==$emp->id?'selected':'' }}>To: {{ $emp->displayName() }}</option>
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

        @if($viewMode)
            <input type="hidden" name="view_mode" value="{{ $viewMode }}">
        @endif
        @if($sortBy)
            <input type="hidden" name="sort_by" value="{{ $sortBy }}">
        @endif
        @if($sortDir)
            <input type="hidden" name="sort_dir" value="{{ $sortDir }}">
        @endif

        <a class="btn light" href="{{ route('tasks.index') }}" style="font-size:12px">Clear Filters</a>
    </form>
</div>

<!-- STATUS BADGE TABS -->
<div class="tabs" style="margin-bottom:14px;flex-wrap:wrap">
    <a class="{{ $status==='all' && !$viewMode && $dateFilter!=='overdue' ? 'active' : '' }}" href="{{ route('tasks.index', array_merge(request()->query(), ['status'=>'all', 'date_filter'=>null, 'view_mode'=>null])) }}">All ({{ $counts['all'] }})</a>
    <a class="{{ $status==='pending' && !$viewMode && $dateFilter!=='overdue' ? 'active' : '' }}" href="{{ route('tasks.index', array_merge(request()->query(), ['status'=>'pending', 'date_filter'=>null, 'view_mode'=>null])) }}">Pending ({{ $counts['pending'] }})</a>
    <a class="{{ $status==='in_progress' && !$viewMode && $dateFilter!=='overdue' ? 'active' : '' }}" href="{{ route('tasks.index', array_merge(request()->query(), ['status'=>'in_progress', 'date_filter'=>null, 'view_mode'=>null])) }}">In Progress ({{ $counts['in_progress'] }})</a>
    <a class="{{ $status==='completed' && !$viewMode && $dateFilter!=='overdue' ? 'active' : '' }}" href="{{ route('tasks.index', array_merge(request()->query(), ['status'=>'completed', 'date_filter'=>null, 'view_mode'=>null])) }}">Completed ({{ $counts['completed'] }})</a>
    <a class="{{ $dateFilter==='overdue' && !$viewMode ? 'active' : '' }}" href="{{ route('tasks.index', array_merge(request()->query(), ['date_filter'=>'overdue', 'status'=>'all', 'view_mode'=>null])) }}" style="color:#dc2626">Overdue ({{ $counts['overdue'] }})</a>
    <a class="{{ $viewMode==='user_wise' ? 'active' : '' }}" href="{{ route('tasks.index', array_merge(request()->query(), ['view_mode'=>'user_wise', 'sort_by'=>'deadline', 'sort_dir'=>'asc'])) }}" style="color:#4f46e5;font-weight:700"><i class="fa-solid fa-users"></i> User Wise</a>
    <a class="{{ $viewMode==='category_wise' ? 'active' : '' }}" href="{{ route('tasks.index', array_merge(request()->query(), ['view_mode'=>'category_wise', 'sort_by'=>'deadline', 'sort_dir'=>'asc'])) }}" style="color:#0284c7;font-weight:700"><i class="fa-solid fa-tags"></i> Category Wise</a>
</div>

@if($viewMode === 'user_wise' && $userWiseGroups)
    <!-- USER WISE GROUPED DISPLAY -->
    <div style="background:#eef2ff;border:1.5px solid #c7d2fe;padding:12px 18px;border-radius:10px;margin-bottom:16px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px">
        <div>
            <strong style="color:#3730a3;font-size:14px;display:flex;align-items:center;gap:6px"><i class="fa-solid fa-users-viewfinder"></i> User-Wise Task Breakdown</strong>
            <div style="color:#4f46e5;font-size:12px;margin-top:2px">Viewing tasks grouped by assigned team member. Total {{ $userWiseGroups->count() }} user(s) currently have tasks.</div>
        </div>
        <a href="{{ route('tasks.index') }}" class="btn light" style="background:#fff;color:#dc2626;border:1px solid #fca5a5;font-weight:700;font-size:12px">
            <i class="fa-solid fa-xmark"></i> Clear Filter (Normal Mode)
        </a>
    </div>

    <div style="display:flex;flex-direction:column;gap:18px;margin-bottom:24px">
        @forelse($userWiseGroups as $assigneeGroupName => $grpTasks)
            <div class="card" style="padding:0;overflow:hidden;border:1px solid #cbd5e1;border-radius:12px">
                <div style="background:#f8fafc;padding:12px 18px;border-bottom:1px solid #e2e8f0;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px">
                    <div style="display:flex;align-items:center;gap:10px">
                        <div style="background:#3b82f6;color:#fff;width:34px;height:34px;border-radius:50%;display:grid;place-items:center;font-weight:700;font-size:13px">
                            {{ strtoupper(substr($assigneeGroupName, 0, 2)) }}
                        </div>
                        <div>
                            <strong style="color:#0f172a;font-size:14px">{{ $assigneeGroupName }}</strong>
                            <span style="font-size:11px;color:#64748b;margin-left:8px">Total: {{ $grpTasks->count() }} task(s)</span>
                        </div>
                    </div>
                    <div style="display:flex;gap:6px">
                        <span class="chip" style="background:#fee2e2;color:#991b1b;font-size:11px;font-weight:700">Pending: {{ $grpTasks->where('status','pending')->count() }}</span>
                        <span class="chip" style="background:#fef3c7;color:#92400e;font-size:11px;font-weight:700">In Progress: {{ $grpTasks->where('status','in_progress')->count() }}</span>
                        <span class="chip" style="background:#dcfce7;color:#166534;font-size:11px;font-weight:700">Done: {{ $grpTasks->where('status','completed')->count() }}</span>
                    </div>
                </div>
                <div style="overflow-x:auto">
                    <table style="width:100%;border-collapse:collapse;font-size:13px">
                        <thead>
                            <tr style="background:#f1f5f9;color:#475569">
                                <th style="padding:10px 14px;text-align:center;width:40px">#</th>
                                <th style="padding:10px 14px;text-align:left">Task Title</th>
                                <th style="padding:10px 14px;text-align:left">Assigned By</th>
                                <th style="padding:10px 14px;text-align:left">Priority</th>
                                <th style="padding:10px 14px;text-align:left">
                                    @php
                                        $nextSortDir = ($sortBy === 'deadline' && $sortDir === 'asc') ? 'desc' : 'asc';
                                    @endphp
                                    <a href="{{ route('tasks.index', array_merge(request()->query(), ['view_mode'=>'user_wise', 'sort_by' => 'deadline', 'sort_dir' => $nextSortDir])) }}" style="color:#475569;text-decoration:none;display:inline-flex;align-items:center;gap:4px" title="Click to sort by deadline">
                                        Deadline
                                        @if($sortBy === 'deadline')
                                            @if($sortDir === 'asc')
                                                <i class="fa-solid fa-arrow-up-short-wide" style="color:#4f46e5" title="Closest deadline first"></i>
                                            @else
                                                <i class="fa-solid fa-arrow-down-wide-short" style="color:#4f46e5" title="Furthest deadline first"></i>
                                            @endif
                                        @else
                                            <i class="fa-solid fa-sort" style="color:#94a3b8"></i>
                                        @endif
                                    </a>
                                </th>
                                <th style="padding:10px 14px;text-align:left">Status (Update)</th>
                                <th style="padding:10px 14px;text-align:left">Comments</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($grpTasks as $gTask)
                                <tr style="border-bottom:1px solid #f1f5f9;cursor:pointer" onclick="openTaskDetailsDrawer({{ json_encode($gTask->load(['creator', 'assignee', 'assignees', 'others', 'department', 'category', 'replies.user', 'attachments', 'histories.user'])) }})">
                                    <td style="padding:10px 14px;text-align:center;color:#64748b">{{ $loop->iteration }}</td>
                                    <td style="padding:10px 14px">
                                        <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap">
                                            <b style="color:#1e293b">{{ $gTask->title }}</b>
                                            @if($gTask->category)
                                                <span class="chip" style="background:{{ $gTask->category->color ? $gTask->category->color . '20' : '#e0e7ff' }};color:{{ $gTask->category->color ?: '#4338ca' }};font-size:10px;font-weight:700;padding:1px 6px">
                                                    {{ $gTask->category->name }}
                                                </span>
                                            @endif
                                        </div>
                                        @if($gTask->client)<small style="display:block;color:#64748b">Client: {{ $gTask->client }}</small>@endif
                                    </td>
                                    <td style="padding:10px 14px;color:#475569">{{ $gTask->creator ? $gTask->creator->displayName() : 'Admin' }}</td>
                                    <td style="padding:10px 14px">
                                        <span class="chip" style="font-size:10px;text-transform:uppercase;font-weight:700;{{ $gTask->priority==='high' ? 'background:#fee2e2;color:#991b1b' : ($gTask->priority==='medium' ? 'background:#fef3c7;color:#92400e' : 'background:#dcfce7;color:#166534') }}">{{ $gTask->priority }}</span>
                                    </td>
                                    <td style="padding:10px 14px;white-space:nowrap">
                                        @if($gTask->due_date)
                                            @php $isOverdue = $gTask->status !== 'completed' && $gTask->due_date->isPast() && !$gTask->due_date->isToday(); @endphp
                                            <div style="font-size:13px;color:{{ $isOverdue ? '#dc2626' : '#334155' }};font-weight:{{ $isOverdue ? '700' : '500' }}">
                                                {{ $gTask->due_date->format('d M Y') }}
                                            </div>
                                            @if(!empty($gTask->due_time))
                                                <small style="color:#64748b;font-size:11px;display:flex;align-items:center;gap:3px;margin-top:2px">
                                                    <i class="fa-regular fa-clock" style="font-size:10px"></i> {{ date('h:i A', strtotime($gTask->due_time)) }}
                                                </small>
                                            @endif
                                        @else
                                            <span style="color:#94a3b8;font-size:13px">-</span>
                                        @endif
                                    </td>
                                    <td style="padding:10px 14px;white-space:nowrap" onclick="event.stopPropagation()">
                                        <form method="post" action="{{ route('tasks.status', $gTask) }}" style="margin:0;display:inline-flex;align-items:center;gap:6px">
                                            @csrf
                                            <select name="status" onchange="this.form.submit()" style="font-size:11px;font-weight:700;padding:3px 6px;border-radius:6px;border:1px solid #cbd5e1;cursor:pointer">
                                                <option value="pending" {{ $gTask->status==='pending' ? 'selected' : '' }}>⏳ Not Started</option>
                                                <option value="in_progress" {{ $gTask->status==='in_progress' ? 'selected' : '' }}>⚡ In Process</option>
                                                <option value="completed" {{ $gTask->status==='completed' ? 'selected' : '' }}>✓ Closed / Done</option>
                                            </select>
                                        </form>
                                    </td>
                                    <td style="padding:10px 14px;color:#64748b;font-size:12px">
                                        {{ \Illuminate\Support\Str::limit($gTask->description ?: '-', 40) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @empty
            <div class="card" style="padding:30px;text-align:center;color:#64748b">
                No active tasks found for user-wise grouping.
            </div>
        @endforelse
    </div>
@endif

@if($viewMode === 'category_wise' && $categoryWiseGroups)
    <!-- CATEGORY WISE GROUPED DISPLAY (Requirement 1: Category Filter like User Wise tab) -->
    <div style="background:#f0f9ff;border:1.5px solid #bae6fd;padding:12px 18px;border-radius:10px;margin-bottom:16px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px">
        <div>
            <strong style="color:#0369a1;font-size:14px;display:flex;align-items:center;gap:6px"><i class="fa-solid fa-tags"></i> Category-Wise Task Breakdown</strong>
            <div style="color:#0284c7;font-size:12px;margin-top:2px">Viewing tasks grouped by Category. Total {{ $categoryWiseGroups->count() }} category group(s).</div>
        </div>
        <a href="{{ route('tasks.index') }}" class="btn light" style="background:#fff;color:#dc2626;border:1px solid #fca5a5;font-weight:700;font-size:12px">
            <i class="fa-solid fa-xmark"></i> Clear Filter (Normal Mode)
        </a>
    </div>

    <div style="display:flex;flex-direction:column;gap:18px;margin-bottom:24px">
        @forelse($categoryWiseGroups as $categoryGroupName => $grpTasks)
            @php
                $firstTaskCat = $grpTasks->first()?->category;
                $catColor = $firstTaskCat && $firstTaskCat->color ? $firstTaskCat->color : '#0284c7';
            @endphp
            <div class="card" style="padding:0;overflow:hidden;border:1px solid #cbd5e1;border-radius:12px">
                <div style="background:#f8fafc;padding:12px 18px;border-bottom:1px solid #e2e8f0;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px">
                    <div style="display:flex;align-items:center;gap:10px">
                        <div style="background:{{ $catColor }};color:#fff;width:34px;height:34px;border-radius:8px;display:grid;place-items:center;font-weight:700;font-size:14px">
                            <i class="fa-solid fa-tag"></i>
                        </div>
                        <div>
                            <strong style="color:#0f172a;font-size:14px">{{ $categoryGroupName }}</strong>
                            <span style="font-size:11px;color:#64748b;margin-left:8px">Total: {{ $grpTasks->count() }} task(s)</span>
                        </div>
                    </div>
                    <div style="display:flex;gap:6px">
                        <span class="chip" style="background:#fee2e2;color:#991b1b;font-size:11px;font-weight:700">Pending: {{ $grpTasks->where('status','pending')->count() }}</span>
                        <span class="chip" style="background:#fef3c7;color:#92400e;font-size:11px;font-weight:700">In Progress: {{ $grpTasks->where('status','in_progress')->count() }}</span>
                        <span class="chip" style="background:#dcfce7;color:#166534;font-size:11px;font-weight:700">Done: {{ $grpTasks->where('status','completed')->count() }}</span>
                    </div>
                </div>
                <div style="overflow-x:auto">
                    <table style="width:100%;border-collapse:collapse;font-size:13px">
                        <thead>
                            <tr style="background:#f1f5f9;color:#475569">
                                <th style="padding:10px 14px;text-align:center;width:40px">#</th>
                                <th style="padding:10px 14px;text-align:left">Task Title</th>
                                <th style="padding:10px 14px;text-align:left">Assigned To</th>
                                <th style="padding:10px 14px;text-align:left">Assigned By</th>
                                <th style="padding:10px 14px;text-align:left">Priority</th>
                                <th style="padding:10px 14px;text-align:left">
                                    @php
                                        $nextSortDir = ($sortBy === 'deadline' && $sortDir === 'asc') ? 'desc' : 'asc';
                                    @endphp
                                    <a href="{{ route('tasks.index', array_merge(request()->query(), ['view_mode'=>'category_wise', 'sort_by' => 'deadline', 'sort_dir' => $nextSortDir])) }}" style="color:#475569;text-decoration:none;display:inline-flex;align-items:center;gap:4px" title="Click to sort by deadline">
                                        Deadline
                                        @if($sortBy === 'deadline')
                                            @if($sortDir === 'asc')
                                                <i class="fa-solid fa-arrow-up-short-wide" style="color:#0284c7" title="Closest deadline first"></i>
                                            @else
                                                <i class="fa-solid fa-arrow-down-wide-short" style="color:#0284c7" title="Furthest deadline first"></i>
                                            @endif
                                        @else
                                            <i class="fa-solid fa-sort" style="color:#94a3b8"></i>
                                        @endif
                                    </a>
                                </th>
                                <th style="padding:10px 14px;text-align:left">Status (Update)</th>
                                <th style="padding:10px 14px;text-align:left">Comments</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($grpTasks as $gTask)
                                <tr style="border-bottom:1px solid #f1f5f9;cursor:pointer" onclick="openTaskDetailsDrawer({{ json_encode($gTask->load(['creator', 'assignee', 'assignees', 'others', 'department', 'category', 'replies.user', 'attachments', 'histories.user'])) }})">
                                    <td style="padding:10px 14px;text-align:center;color:#64748b">{{ $loop->iteration }}</td>
                                    <td style="padding:10px 14px">
                                        <b style="color:#1e293b">{{ $gTask->title }}</b>
                                        @if($gTask->client)<small style="display:block;color:#64748b">Client: {{ $gTask->client }}</small>@endif
                                    </td>
                                    <td style="padding:10px 14px">
                                        <div style="display:flex;align-items:center;gap:6px">
                                            <div style="background:#e0f2fe;color:#0369a1;width:26px;height:26px;border-radius:50%;display:grid;place-items:center;font-weight:700;font-size:11px">
                                                {{ strtoupper(substr($gTask->assignee ? $gTask->assignee->displayName() : 'U', 0, 1)) }}
                                            </div>
                                            <span style="font-weight:600;color:#1e293b">{{ $gTask->assignee ? $gTask->assignee->displayName() : 'Unassigned' }}</span>
                                        </div>
                                    </td>
                                    <td style="padding:10px 14px;color:#475569">{{ $gTask->creator ? $gTask->creator->displayName() : 'Admin' }}</td>
                                    <td style="padding:10px 14px">
                                        <span class="chip" style="font-size:10px;text-transform:uppercase;font-weight:700;{{ $gTask->priority==='high' ? 'background:#fee2e2;color:#991b1b' : ($gTask->priority==='medium' ? 'background:#fef3c7;color:#92400e' : 'background:#dcfce7;color:#166534') }}">{{ $gTask->priority }}</span>
                                    </td>
                                    <td style="padding:10px 14px;white-space:nowrap">
                                        @if($gTask->due_date)
                                            @php $isOverdue = $gTask->status !== 'completed' && $gTask->due_date->isPast() && !$gTask->due_date->isToday(); @endphp
                                            <div style="font-size:13px;color:{{ $isOverdue ? '#dc2626' : '#334155' }};font-weight:{{ $isOverdue ? '700' : '500' }}">
                                                {{ $gTask->due_date->format('d M Y') }}
                                            </div>
                                            @if(!empty($gTask->due_time))
                                                <small style="color:#64748b;font-size:11px;display:flex;align-items:center;gap:3px;margin-top:2px">
                                                    <i class="fa-regular fa-clock" style="font-size:10px"></i> {{ date('h:i A', strtotime($gTask->due_time)) }}
                                                </small>
                                            @endif
                                        @else
                                            <span style="color:#94a3b8;font-size:13px">-</span>
                                        @endif
                                    </td>
                                    <td style="padding:10px 14px;white-space:nowrap" onclick="event.stopPropagation()">
                                        <form method="post" action="{{ route('tasks.status', $gTask) }}" style="margin:0;display:inline-flex;align-items:center;gap:6px">
                                            @csrf
                                            <select name="status" onchange="this.form.submit()" style="font-size:11px;font-weight:700;padding:3px 6px;border-radius:6px;border:1px solid #cbd5e1;cursor:pointer">
                                                <option value="pending" {{ $gTask->status==='pending' ? 'selected' : '' }}>⏳ Not Started</option>
                                                <option value="in_progress" {{ $gTask->status==='in_progress' ? 'selected' : '' }}>⚡ In Process</option>
                                                <option value="completed" {{ $gTask->status==='completed' ? 'selected' : '' }}>✓ Closed / Done</option>
                                            </select>
                                        </form>
                                    </td>
                                    <td style="padding:10px 14px;color:#64748b;font-size:12px">
                                        {{ \Illuminate\Support\Str::limit($gTask->description ?: '-', 40) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @empty
            <div class="card" style="padding:30px;text-align:center;color:#64748b">
                No active tasks found for category-wise grouping.
            </div>
        @endforelse
    </div>
@endif

<!-- LIST VIEW CONTAINER (Matching Screenshot) -->
<div id="taskListView" style="background:#fff;border-radius:14px;box-shadow:0 4px 20px -2px rgba(0,0,0,0.06);border:1px solid #e2e8f0;overflow:hidden;{{ ($viewMode==='user_wise' || $viewMode==='category_wise') ? 'display:none;' : '' }}">
    <div style="overflow-x:auto">
        <table style="width:100%;border-collapse:collapse;font-family:Inter,Segoe UI,-apple-system,sans-serif">
            <thead>
                <tr style="background:#334155;color:#ffffff">
                    <th style="padding:15px 12px;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:0.8px;text-align:center;width:60px">#</th>
                    <th style="padding:15px 18px;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:0.8px;text-align:left">TASK NAME</th>
                    <th style="padding:15px 18px;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:0.8px;text-align:center">ASSIGNED TO</th>
                    <th style="padding:15px 16px;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:0.8px;text-align:left">PRIORITY</th>
                    <th style="padding:15px 16px;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:0.8px;text-align:left">
                        @php
                            $nextSortDir = ($sortBy === 'deadline' && $sortDir === 'asc') ? 'desc' : 'asc';
                        @endphp
                        <a href="{{ route('tasks.index', array_merge(request()->query(), ['sort_by' => 'deadline', 'sort_dir' => $nextSortDir])) }}" style="color:#ffffff;text-decoration:none;display:inline-flex;align-items:center;gap:6px" title="Click to sort by deadline">
                            DEADLINE
                            @if($sortBy === 'deadline')
                                @if($sortDir === 'asc')
                                    <i class="fa-solid fa-arrow-up-short-wide" style="color:#38bdf8" title="Ascending (Closest deadline first)"></i>
                                @else
                                    <i class="fa-solid fa-arrow-down-wide-short" style="color:#38bdf8" title="Descending (Furthest deadline first)"></i>
                                @endif
                            @else
                                <i class="fa-solid fa-sort" style="color:#94a3b8"></i>
                            @endif
                        </a>
                    </th>
                    <th style="padding:15px 16px;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:0.8px;text-align:left">STATUS</th>
                    <th style="padding:15px 18px;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:0.8px;text-align:left">COMMENTS</th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $task)
                    @php
                        $rowBg = $task->is_pinned ? '#fff1f2' : ($loop->even ? '#f8fafc' : '#ffffff');
                        
                        // Assignee styling matching screenshot
                        $assigneeName = $task->assignee ? $task->assignee->displayName() : 'Unassigned';
                        $colorPalettes = [
                            ['bg' => '#c084fc', 'text' => '#ffffff'], // Lavender / Purple (Olivia Adams)
                            ['bg' => '#818cf8', 'text' => '#ffffff'], // Indigo / Violet (Zoe Martinez)
                            ['bg' => '#38bdf8', 'text' => '#0c4a6e'], // Sky Blue (Michael Brown)
                            ['bg' => '#f472b6', 'text' => '#ffffff'], // Dusty Rose / Pink (Sarah Wilson)
                            ['bg' => '#fcd34d', 'text' => '#78350f'], // Warm Gold / Amber (Alex Johnson)
                            ['bg' => '#6ee7b7', 'text' => '#064e3b'], // Mint / Emerald (Mia Garcia)
                            ['bg' => '#fb923c', 'text' => '#ffffff'], // Coral / Orange
                            ['bg' => '#a78bfa', 'text' => '#ffffff'], // Purple-Indigo
                            ['bg' => '#93c5fd', 'text' => '#1e3a8a'], // Soft Blue
                        ];
                        $paletteIndex = $task->assigned_to ? ($task->assigned_to % count($colorPalettes)) : abs(crc32($assigneeName)) % count($colorPalettes);
                        $palette = $colorPalettes[$paletteIndex];

                        // Priority vertical bar and label matching screenshot
                        $priLower = strtolower($task->priority);
                        $priBarColor = match($priLower) {
                            'high' => '#ef4444',
                            'medium' => '#eab308',
                            default => '#22c55e',
                        };
                        $priText = match($priLower) {
                            'high' => 'High',
                            'medium' => 'Medium',
                            default => 'Low',
                        };

                        // Status vertical bar and label matching screenshot
                        $stLower = strtolower($task->status);
                        $statusBarColor = match($stLower) {
                            'completed' => '#22c55e',
                            'in_progress' => '#eab308',
                            default => '#ef4444',
                        };
                        $statusText = match($stLower) {
                            'completed' => 'Closed / Done',
                            'in_progress' => 'In Process',
                            default => 'Not Started',
                        };

                        // Extra Assignees Count
                        $allAssignees = $task->assignees->merge($task->others)->unique('id');
                        $extraCount = $allAssignees->filter(fn($u) => $u->id !== $task->assigned_to)->count();

                        // Latest comment / reply or description
                        $latestReply = $task->replies ? $task->replies->last() : null;
                        $commentText = $latestReply ? $latestReply->message : ($task->description ?: '-');
                    @endphp
                    <tr style="background:{{ $rowBg }};border-bottom:1px solid #f1f5f9;cursor:pointer;transition:background 0.15s ease" 
                        onmouseover="this.style.background='#f1f5f9'" 
                        onmouseout="this.style.background='{{ $rowBg }}'"
                        onclick="openTaskDetailsDrawer({{ json_encode($task->load(['creator', 'assignee', 'assignees', 'others', 'department', 'category', 'replies.user', 'attachments', 'histories.user'])) }})"
                        title="Click row to open details for {{ $task->title }}">

                        <!-- # (Index & Pin) -->
                        <td style="padding:15px 10px;text-align:center;font-size:13px;font-weight:600;color:#64748b;width:60px" onclick="event.stopPropagation()">
                            <div style="display:flex;align-items:center;justify-content:center;gap:6px">
                                <form method="post" action="{{ route('tasks.pin', $task) }}" style="margin:0;display:inline">
                                    @csrf
                                    <button type="submit" style="background:none;border:none;cursor:pointer;padding:2px 4px;font-size:13px;color:{{ $task->is_pinned ? '#e11d48' : '#cbd5e1' }};transition:color 0.2s ease" title="{{ $task->is_pinned ? 'Unpin Task' : 'Pin Task to Top' }}">
                                        <i class="fa-solid fa-thumbtack" style="{{ $task->is_pinned ? 'transform:rotate(-45deg);' : '' }}"></i>
                                    </button>
                                </form>
                                <span>{{ ($items->currentPage() - 1) * $items->perPage() + $loop->iteration }}</span>
                            </div>
                        </td>

                        <!-- TASK NAME & CATEGORY -->
                        <td style="padding:15px 18px">
                            <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap">
                                <span style="font-weight:600;color:#1e293b;font-size:13.5px;line-height:1.3">{{ $task->title }}</span>
                                @if($task->category)
                                    <span class="chip" style="background:{{ $task->category->color ? $task->category->color . '20' : '#e0e7ff' }};color:{{ $task->category->color ?: '#4338ca' }};font-size:10.5px;font-weight:700;padding:2px 7px;border-radius:12px;border:1px solid {{ $task->category->color ? $task->category->color . '40' : '#c7d2fe' }}">
                                        <i class="fa-solid fa-tag" style="font-size:9px"></i> {{ $task->category->name }}
                                    </span>
                                @endif
                            </div>
                            @if($task->client)
                                <div style="font-size:11px;color:#64748b;margin-top:2px">
                                    <i class="fa-solid fa-user-tie" style="font-size:10px"></i> Client: <b>{{ $task->client }}</b>
                                </div>
                            @endif
                            @if($task->department)
                                <div style="font-size:10.5px;color:#94a3b8;margin-top:2px">
                                    Dept: {{ $task->department->name }}
                                </div>
                            @endif
                        </td>

                        <!-- ASSIGNED TO -->
                        <td style="padding:15px 18px;text-align:center">
                            <div style="display:inline-flex;flex-direction:column;align-items:center">
                                <div style="display:flex;align-items:center;gap:5px">
                                    <div style="background:{{ $palette['bg'] }};color:{{ $palette['text'] }};border-radius:6px;padding:6px 14px;font-size:11.5px;font-weight:800;letter-spacing:0.5px;text-transform:uppercase;box-shadow:0 1px 2px rgba(0,0,0,0.06);white-space:nowrap">
                                        {{ $assigneeName }}
                                    </div>
                                    @if($extraCount > 0)
                                        <span style="background:#e0e7ff;color:#3730a3;font-size:11px;font-weight:800;padding:3px 7px;border-radius:12px;border:1px solid #c7d2fe;white-space:nowrap" title="{{ $extraCount }} more employee(s) assigned">
                                            +{{ $extraCount }}
                                        </span>
                                    @endif
                                </div>
                                @if($task->creator)
                                    <div style="font-size:10px;color:#64748b;margin-top:3px;font-weight:500">
                                        FROM: <b style="color:#475569">{{ $task->creator->displayName() }}</b>
                                    </div>
                                @endif
                            </div>
                        </td>

                        <!-- PRIORITY -->
                        <td style="padding:15px 16px;white-space:nowrap">
                            <div style="display:flex;align-items:center;gap:8px">
                                <span style="display:inline-block;width:4.5px;height:16px;background:{{ $priBarColor }};border-radius:2px"></span>
                                <span style="font-size:13px;font-weight:500;color:#334155">{{ $priText }}</span>
                            </div>
                        </td>

                        <!-- DEADLINE -->
                        <td style="padding:15px 16px;white-space:nowrap">
                            @if($task->due_date)
                                @php $isOverdue = $task->status !== 'completed' && $task->due_date->isPast() && !$task->due_date->isToday(); @endphp
                                <div style="font-size:13px;color:{{ $isOverdue ? '#dc2626' : '#334155' }};font-weight:{{ $isOverdue ? '700' : '500' }}">
                                    {{ $task->due_date->format('d M Y') }}
                                </div>
                                @if(!empty($task->due_time))
                                    <small style="color:#64748b;font-size:11px;display:flex;align-items:center;gap:3px;margin-top:2px">
                                        <i class="fa-regular fa-clock" style="font-size:10px"></i> {{ date('h:i A', strtotime($task->due_time)) }}
                                    </small>
                                @endif
                            @else
                                <span style="color:#94a3b8;font-size:13px">-</span>
                            @endif
                        </td>

                        <!-- STATUS (Quick Update / Close) -->
                        <td style="padding:10px 14px;white-space:nowrap" onclick="event.stopPropagation()">
                            <form method="post" action="{{ route('tasks.status', $task) }}" style="margin:0;display:inline-flex;align-items:center;gap:6px">
                                @csrf
                                <span style="display:inline-block;width:4.5px;height:24px;background:{{ $statusBarColor }};border-radius:2px"></span>
                                <select name="status" onchange="this.form.submit()" style="font-size:12px;font-weight:700;padding:4px 8px;border-radius:6px;border:1.5px solid {{ $statusBarColor }};background:#fff;color:#1e293b;cursor:pointer" title="Update Task Status / Close Task">
                                    <option value="pending" {{ $task->status==='pending' ? 'selected' : '' }} style="color:#ef4444;font-weight:700">⏳ Not Started</option>
                                    <option value="in_progress" {{ $task->status==='in_progress' ? 'selected' : '' }} style="color:#d97706;font-weight:700">⚡ In Process</option>
                                    <option value="completed" {{ $task->status==='completed' ? 'selected' : '' }} style="color:#16a34a;font-weight:700">✓ Closed / Done</option>
                                </select>
                            </form>
                        </td>

                        <!-- COMMENTS -->
                        <td style="padding:15px 18px">
                            <div style="font-size:12.5px;color:#475569;max-width:260px;line-height:1.4">
                                @if($commentText !== '-')
                                    <span title="{{ $commentText }}">{{ \Illuminate\Support\Str::limit($commentText, 50) }}</span>
                                    @if($task->replies && $task->replies->count() > 0)
                                        <span style="display:inline-block;margin-left:4px;background:#e0e7ff;color:#4338ca;font-size:10px;font-weight:700;padding:1px 6px;border-radius:8px">
                                            💬 {{ $task->replies->count() }}
                                        </span>
                                    @endif
                                @else
                                    <span style="color:#94a3b8">-</span>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align:center;padding:36px;color:#64748b;font-size:13.5px">
                            No tasks found matching current filters. Click "+ New Task" to create one.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div style="padding:14px 18px;background:#fafafa;border-top:1px solid #f1f5f9">
        {{ $items->links() }}
    </div>
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

            <div style="margin-bottom:12px">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px">
                    <label style="margin:0;font-size:13px;font-weight:600">Task Category</label>
                    <a href="javascript:void(0)" onclick="openNewCategoryModal()" style="font-size:12px;color:var(--accent);font-weight:700;text-decoration:none;display:inline-flex;align-items:center;gap:4px">
                        <i class="fa-solid fa-plus-circle"></i> Add New Category
                    </a>
                </div>
                <select name="category_id" id="taskCategorySelect" style="width:100%;background:#fff;border:1px solid #cbd5e1;padding:8px 12px;border-radius:8px;font-size:12.5px;color:#1e293b">
                    <option value="">Select Category (Optional)</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="grid-2" style="margin-bottom:12px">
                <div>
                    <label>Department (Optional)</label>
                    <select name="department_id" id="newTaskDeptSelect" onchange="filterEmployeesByDept(this.value)">
                        <option value="">Select Department (Optional)</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label>Assigned To Primary Employee *</label>
                    <select name="assigned_to" id="newTaskAssigneeSelect" required style="width:100%;background:#fff;border:1px solid #cbd5e1;padding:8px 12px;border-radius:8px;font-size:12.5px;color:#1e293b">
                        <option value="">Select Primary Employee</option>
                        @foreach($employees as $e)
                            <option value="{{ $e->id }}" data-dept-id="{{ $e->department_id }}">{{ strtoupper($e->displayName()) }} ({{ $e->department ?: 'No Dept' }})</option>
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
                <label>Attach Files (Upload Multiple)</label>
                <input type="file" name="attachment_files[]" multiple>
            </div>

            <div style="margin-bottom:16px">
                <label style="margin-bottom:4px;display:block">Assign To Others (Multiple Selection)</label>
                <div class="custom-emp-picker" id="picker_others" style="position:relative">
                    <button type="button" class="picker-btn" onclick="toggleEmpPicker('picker_others')" style="width:100%;text-align:left;background:#fff;border:1px solid #cbd5e1;padding:8px 12px;border-radius:8px;font-size:12.5px;display:flex;justify-content:space-between;align-items:center;cursor:pointer">
                        <span class="picker-label" id="picker_label_others" style="color:#64748b">Select Additional Assignees (Optional)</span>
                        <i class="fa-solid fa-chevron-down" style="font-size:11px;color:#94a3b8"></i>
                    </button>
                    <div id="hidden_inputs_others"></div>

                    <!-- Dropdown Box matching Screenshot 2 & 3 with FILTER BY DEPARTMENT -->
                    <div class="picker-dropdown" id="dropdown_picker_others" style="display:none;position:absolute;top:100%;left:0;right:0;background:#ffffff;border:1.5px solid #cbd5e1;border-radius:12px;box-shadow:0 12px 30px rgba(0,0,0,0.18);z-index:9999;margin-top:4px;padding:14px;box-sizing:border-box">
                        <div style="font-size:11px;font-weight:800;color:#64748b;letter-spacing:0.8px;margin-bottom:8px">FILTER BY DEPARTMENT</div>
                        <div style="display:grid;grid-template-columns:repeat(2, 1fr);gap:6px 12px;margin-bottom:12px;max-height:120px;overflow-y:auto;padding-right:4px">
                            @foreach($departments as $dept)
                                <label style="display:flex;align-items:center;gap:6px;font-size:12px;color:#334155;cursor:pointer">
                                    <input type="checkbox" class="dept-filter-chk" data-dept-id="{{ $dept->id }}" onchange="applyPickerFilters('picker_others')" style="accent-color:#2563eb;width:15px;height:15px;cursor:pointer">
                                    <span>{{ $dept->name }}</span>
                                </label>
                            @endforeach
                        </div>

                        <div style="border-top:1px solid #e2e8f0;padding-top:8px;margin-bottom:10px;display:flex;justify-content:space-between;align-items:center">
                            <a href="javascript:void(0)" onclick="selectAllPickerEmployees('picker_others', true)" style="color:#2563eb;font-size:12px;font-weight:700;text-decoration:none">Select All Filtered Employees</a>
                            <a href="javascript:void(0)" onclick="selectAllPickerEmployees('picker_others', false)" style="color:#64748b;font-size:11px;text-decoration:none">Clear</a>
                        </div>

                        <div style="font-size:11px;font-weight:800;color:#64748b;letter-spacing:0.8px;margin-bottom:6px">EMPLOYEES</div>
                        <div class="picker-emp-list" style="max-height:160px;overflow-y:auto;display:flex;flex-direction:column;gap:6px;padding-right:4px;margin-bottom:12px">
                            @foreach($employees as $e)
                                <label class="picker-emp-item" data-dept-id="{{ $e->department_id }}" data-emp-id="{{ $e->id }}" data-name="{{ strtolower($e->displayName()) }}" style="display:flex;align-items:center;gap:8px;font-size:12px;color:#1e293b;cursor:pointer;padding:3px 0">
                                    <input type="checkbox" name="assignee_ids[]" class="emp-select-chk" value="{{ $e->id }}" data-name="{{ $e->displayName() }}" onchange="onEmpPickerChange('picker_others', 'multiple', 'assignee_ids[]')" style="accent-color:#2563eb;width:15px;height:15px;cursor:pointer">
                                    <span>{{ strtoupper($e->displayName()) }}</span>
                                </label>
                            @endforeach
                        </div>

                        <div style="position:relative">
                            <input type="text" class="picker-search-input" placeholder="Search employees" oninput="onPickerSearch('picker_others', this.value)" style="width:100%;padding:7px 10px;font-size:12px;border-radius:6px;border:1.5px solid #3b82f6;outline:none" autocomplete="off">
                        </div>
                    </div>
                </div>
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
                        <form id="drawerStatusForm" method="post" style="margin:0;display:flex;align-items:center;gap:6px">
                            @csrf
                            <select id="dtlStatusSelect" name="status" onchange="this.form.submit()" style="font-size:12px;font-weight:700;padding:4px 10px;border-radius:6px;border:1.5px solid #cbd5e1;cursor:pointer;background:#fff" title="Change Status / Close Task">
                                <option value="pending">⏳ Not Started</option>
                                <option value="in_progress">⚡ In Process</option>
                                <option value="completed">✓ Closed / Done</option>
                            </select>
                        </form>
                    </div>
                    <h3 id="dtlTitle" style="margin:0 0 6px 0;color:#111827">Task Title</h3>
                    
                    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:8px;margin-bottom:8px">
                        <p id="dtlDescription" class="muted" style="margin:0;font-size:13px;flex:1;white-space:pre-wrap">Description...</p>
                        <button type="button" id="btnEditDescription" onclick="startEditDescription()" class="btn light" style="display:none;font-size:11px;padding:3px 8px;color:#4f46e5;border:1px solid #c7d2fe;white-space:nowrap;cursor:pointer" title="Edit Task Description (Creator or Admin)">
                            <i class="fa-solid fa-pen-to-square"></i> Edit
                        </button>
                    </div>

                    <div id="descEditContainer" style="display:none;background:#fff;border:1.5px solid #cbd5e1;border-radius:8px;padding:10px;margin-bottom:12px">
                        <label style="font-size:11.5px;font-weight:700;color:#334155;display:block;margin-bottom:4px"><i class="fa-solid fa-pen"></i> Edit Task Description</label>
                        <textarea id="dtlDescriptionInput" rows="3" style="width:100%;font-size:12.5px;padding:8px;border-radius:6px;border:1px solid #cbd5e1;box-sizing:border-box" placeholder="Enter updated task description..."></textarea>
                        <div style="display:flex;justify-content:flex-end;gap:6px;margin-top:8px">
                            <button type="button" class="btn light" style="font-size:11px;padding:4px 10px" onclick="cancelEditDescription()">Cancel</button>
                            <button type="button" id="btnSaveDesc" class="btn" style="font-size:11px;padding:4px 12px;background:#4f46e5;color:#fff" onclick="saveTaskDescription()"><i class="fa-solid fa-check"></i> Save Description</button>
                        </div>
                    </div>
                    
                    <div class="grid-2" style="font-size:12px;color:#475569;margin-top:10px;border-top:1px dashed #cbd5e1;padding-top:10px">
                        <div><i class="fa-solid fa-tags" style="color:#6366f1"></i> <b>Category:</b> <span id="dtlCategory">-</span></div>
                        <div><i class="fa-regular fa-calendar-plus" style="color:#0284c7"></i> <b>Created On:</b> <span id="dtlCreatedAt">-</span></div>
                        <div><i class="fa-regular fa-calendar"></i> <b>Due Date:</b> <span id="dtlDueDate">-</span></div>
                        <div><i class="fa-regular fa-clock"></i> <b>Due Time:</b> <span id="dtlDueTime">-</span></div>
                        <div><i class="fa-solid fa-flag"></i> <b>Priority:</b> <span id="dtlPriority">-</span></div>
                        <div><i class="fa-solid fa-repeat"></i> <b>Repeat:</b> <span id="dtlRepeat">-</span></div>
                    </div>
                </div>

                <!-- EDIT DUE DATE & STATUS (Deadline Extension / Resume Task) -->
                <div style="background:#f0f9ff;border:1.5px solid #bae6fd;padding:12px 14px;border-radius:10px;margin-bottom:14px">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px">
                        <strong style="font-size:12.5px;color:#0369a1;display:flex;align-items:center;gap:6px">
                            <i class="fa-solid fa-calendar-pen"></i> Edit Due Date & Update Status
                        </strong>
                        <span style="font-size:11px;color:#0284c7;font-weight:600">Extend / Resume</span>
                    </div>
                    <form id="drawerDeadlineForm" method="post" action="">
                        @csrf
                        <div class="grid-2" style="margin-bottom:8px;gap:8px">
                            <div>
                                <label style="font-size:11px;font-weight:700;color:#334155;display:block;margin-bottom:2px">New Due Date</label>
                                <input type="date" name="due_date" id="dtlEditDueDate" style="font-size:12px;padding:5px 8px;width:100%">
                            </div>
                            <div>
                                <label style="font-size:11px;font-weight:700;color:#334155;display:block;margin-bottom:2px">New Due Time</label>
                                <input type="time" name="due_time" id="dtlEditDueTime" style="font-size:12px;padding:5px 8px;width:100%">
                            </div>
                        </div>
                        <div style="margin-bottom:8px">
                            <label style="font-size:11px;font-weight:700;color:#334155;display:block;margin-bottom:2px">Update Status</label>
                            <select name="status" id="dtlEditStatus" style="font-size:12px;padding:5px 8px;width:100%;background:#fff">
                                <option value="pending">⏳ Pending (Not Started)</option>
                                <option value="in_progress">⚡ In Progress</option>
                                <option value="completed">✓ Completed</option>
                            </select>
                        </div>
                        <div style="margin-bottom:8px">
                            <label style="font-size:11px;font-weight:700;color:#334155;display:block;margin-bottom:2px">Reason / Remarks (Optional)</label>
                            <input type="text" name="remarks" id="dtlEditRemarks" placeholder="e.g. Work verification pending, deadline extended..." style="font-size:12px;padding:5px 8px;width:100%">
                        </div>
                        <button type="submit" class="btn" style="width:100%;font-size:12px;padding:6px;background:#0284c7;color:#fff;border-radius:6px">
                            <i class="fa-solid fa-floppy-disk"></i> Update Deadline & Status
                        </button>
                    </form>
                </div>

                <div style="background:#fff;padding:12px;border-radius:8px;border:1px solid #e2e8f0;margin-bottom:14px">
                    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px">
                        <div style="flex:1">
                            <strong style="display:block;margin-bottom:6px;font-size:13px;color:#1e293b"><i class="fa-solid fa-user-check" style="color:var(--accent)"></i> Assigned To:</strong>
                            <div id="dtlAssigneesList" style="display:flex;align-items:center;gap:8px;flex-wrap:wrap"></div>
                        </div>
                        <div style="flex:1;border-left:1px dashed #cbd5e1;padding-left:12px">
                            <strong style="display:block;margin-bottom:6px;font-size:13px;color:#1e293b"><i class="fa-solid fa-user-tag" style="color:#6366f1"></i> Assigned By (From):</strong>
                            <div id="dtlCreatorInfo" style="display:flex;align-items:center;gap:6px"></div>
                        </div>
                    </div>
                </div>

                <!-- Attached Files of the Task -->
                <div style="background:#fff;padding:12px;border-radius:8px;border:1px solid #e2e8f0;margin-bottom:14px">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
                        <strong style="font-size:13px;color:#1e293b"><i class="fa-solid fa-paperclip" style="color:var(--accent)"></i> Attached Files:</strong>
                    </div>
                    <div id="dtlAttachmentsList" style="display:flex;flex-direction:column;gap:6px">
                        <span class="muted" style="font-size:12px">No files attached to this task.</span>
                    </div>

                    <!-- Upload Multiple Attachments Form (Requirement 5) -->
                    <form id="drawerUploadAttachmentForm" method="post" enctype="multipart/form-data" onsubmit="submitDrawerAttachments(event)" style="margin-top:10px;padding-top:10px;border-top:1px dashed #e2e8f0">
                        @csrf
                        <label style="font-size:11.5px;font-weight:700;color:#334155;display:block;margin-bottom:4px"><i class="fa-solid fa-plus-circle"></i> Upload Attachments (Multiple Files Supported)</label>
                        <div style="display:flex;gap:6px;align-items:center">
                            <input type="file" id="drawerAttachmentFiles" name="attachment_files[]" multiple required style="font-size:11.5px;flex:1">
                            <button type="submit" id="btnUploadDrawerAtt" class="btn light" style="font-size:11.5px;padding:5px 12px;white-space:nowrap;color:#0284c7;border-color:#bae6fd"><i class="fa-solid fa-upload"></i> Upload</button>
                        </div>
                    </form>
                </div>

                <!-- Action Buttons: Re-assign, Re-Open, Send Reminder & Delete -->
                <div style="display:flex;gap:8px;margin-bottom:14px;flex-wrap:wrap">
                    <button type="button" class="btn light" style="flex:1" onclick="openReassignModal()"><i class="fa-solid fa-right-left"></i> Re-assign</button>
                    <button type="button" id="btnReopenTask" class="btn" style="flex:1;background:#0284c7;color:#fff;display:none" onclick="openReopenModal()"><i class="fa-solid fa-rotate-left"></i> Re-Open Task</button>
                    
                    <!-- Send Reminder Button -->
                    <form id="remindTaskForm" method="post" style="display:inline">
                        @csrf
                        <button class="btn light" style="color:#d97706" title="Send Push Notification reminder to assigned employees"><i class="fa-solid fa-bell"></i> Remind</button>
                    </form>

                    <form id="deleteTaskForm" method="post" style="display:inline" onsubmit="return confirm('Delete this task?')">
                        @csrf @method('delete')
                        <button class="btn light" style="color:#dc2626"><i class="fa-solid fa-trash"></i></button>
                    </form>
                </div>
            </div>

            <!-- Tab 2: Replies & Attachments -->
            <div id="drawerTabReports" style="display:none">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px">
                    <strong style="margin:0"><i class="fa-solid fa-comments" style="color:var(--accent)"></i> Replies & Remarks Submission</strong>
                    <a id="dtlExportRepliesPdf" href="#" target="_blank" class="btn light" style="font-size:11px;padding:4px 10px;color:#dc2626;border-color:#fca5a5;display:inline-flex;align-items:center;gap:4px">
                        <i class="fa-solid fa-file-pdf"></i> Export Replies PDF
                    </a>
                </div>
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
<div id="reassignModal" class="modal-bg" style="align-items:center;justify-content:center">
    <div class="modal" style="max-width:500px;width:92%;max-height:90vh;overflow-y:auto;z-index:10000;padding:22px;border-radius:14px;background:#fff;margin:auto;box-shadow:0 15px 35px rgba(0,0,0,0.25)">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
            <h3 style="margin:0"><i class="fa-solid fa-right-left" style="color:var(--accent)"></i> Re-Assign Task</h3>
            <button type="button" onclick="closeReassignModal()" style="border:0;background:none;font-size:22px;cursor:pointer;color:#64748b">&times;</button>
        </div>
        <p class="muted" style="font-size:12.5px;margin-bottom:12px">Select one or multiple employees to re-assign this task to. A push notification and email will be sent immediately to the new assignee(s).</p>
        <form id="reassignForm" method="post">
            @csrf
            <label style="margin-bottom:4px;display:block;font-size:13px;font-weight:600">Select New Assignee(s) *</label>
            <div class="custom-emp-picker" id="picker_reassign" style="position:relative;margin-bottom:12px">
                <button type="button" class="picker-btn" onclick="toggleEmpPicker('picker_reassign')" style="width:100%;text-align:left;background:#fff;border:1px solid #cbd5e1;padding:8px 12px;border-radius:8px;font-size:12.5px;display:flex;justify-content:space-between;align-items:center;cursor:pointer">
                    <span class="picker-label" id="picker_label_reassign" style="color:#64748b">Select New Assignee(s)</span>
                    <i class="fa-solid fa-chevron-down" style="font-size:11px;color:#94a3b8"></i>
                </button>
                <div id="hidden_inputs_reassign"></div>

                <!-- Dropdown Box matching FILTER BY DEPARTMENT (Opens downwards, responsive) -->
                <div class="picker-dropdown" id="dropdown_picker_reassign" style="display:none;position:relative;width:100%;background:#ffffff;border:1.5px solid #cbd5e1;border-radius:12px;box-shadow:0 8px 24px rgba(0,0,0,0.12);z-index:9999;margin-top:6px;padding:14px;box-sizing:border-box;max-height:280px;overflow-y:auto">
                    <div style="font-size:11px;font-weight:800;color:#64748b;letter-spacing:0.8px;margin-bottom:8px">FILTER BY DEPARTMENT</div>
                    <div style="display:grid;grid-template-columns:repeat(2, 1fr);gap:6px 12px;margin-bottom:12px;max-height:110px;overflow-y:auto;padding-right:4px">
                        @foreach($departments as $dept)
                            <label style="display:flex;align-items:center;gap:6px;font-size:12px;color:#334155;cursor:pointer">
                                <input type="checkbox" class="dept-filter-chk" data-dept-id="{{ $dept->id }}" onchange="applyPickerFilters('picker_reassign')" style="accent-color:#2563eb;width:15px;height:15px;cursor:pointer">
                                <span>{{ $dept->name }}</span>
                            </label>
                        @endforeach
                    </div>

                    <div style="border-top:1px solid #e2e8f0;padding-top:8px;margin-bottom:10px;display:flex;justify-content:space-between;align-items:center">
                        <a href="javascript:void(0)" onclick="selectAllPickerEmployees('picker_reassign', true)" style="color:#2563eb;font-size:12px;font-weight:700;text-decoration:none">Select All Filtered Employees</a>
                        <a href="javascript:void(0)" onclick="selectAllPickerEmployees('picker_reassign', false)" style="color:#64748b;font-size:11px;text-decoration:none">Clear</a>
                    </div>

                    <div style="font-size:11px;font-weight:800;color:#64748b;letter-spacing:0.8px;margin-bottom:6px">EMPLOYEES</div>
                    <div class="picker-emp-list" style="max-height:150px;overflow-y:auto;display:flex;flex-direction:column;gap:6px;padding-right:4px;margin-bottom:12px">
                        @foreach($employees as $e)
                            <label class="picker-emp-item" data-dept-id="{{ $e->department_id }}" data-emp-id="{{ $e->id }}" data-name="{{ strtolower($e->displayName()) }}" style="display:flex;align-items:center;gap:8px;font-size:12px;color:#1e293b;cursor:pointer;padding:3px 0">
                                <input type="checkbox" name="new_assigned_to[]" class="emp-select-chk" value="{{ $e->id }}" data-name="{{ $e->displayName() }}" onchange="onEmpPickerChange('picker_reassign', 'multiple', 'new_assigned_to[]')" style="accent-color:#2563eb;width:15px;height:15px;cursor:pointer">
                                <span>{{ strtoupper($e->displayName()) }}</span>
                            </label>
                        @endforeach
                    </div>

                    <div style="position:relative">
                        <input type="text" class="picker-search-input" placeholder="Search employees" oninput="onPickerSearch('picker_reassign', this.value)" style="width:100%;padding:7px 10px;font-size:12px;border-radius:6px;border:1.5px solid #3b82f6;outline:none" autocomplete="off">
                    </div>
                </div>
            </div>

            <label style="margin-bottom:4px;display:block;font-size:13px;font-weight:600">Reason / Remarks (Optional)</label>
            <textarea name="remarks" rows="2" placeholder="e.g. Belongs to Accounts Dept..." style="margin-bottom:14px;width:100%"></textarea>
            <div style="display:flex;justify-content:flex-end;gap:8px">
                <button type="button" class="btn light" onclick="closeReassignModal()">Cancel</button>
                <button type="submit" class="btn"><i class="fa-solid fa-check"></i> Re-Assign & Notify</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Re-Open Task -->
<div id="reopenModal" class="modal-bg">
    <div class="modal" style="max-width:440px;z-index:10000;padding:20px;border-radius:14px">
        <h3 style="margin-top:0;color:#0284c7"><i class="fa-solid fa-rotate-left"></i> Re-Open Task</h3>
        <p class="muted" style="font-size:12.5px;margin-bottom:12px">Set a new deadline for this task to resume work. Status will be changed back to In Progress.</p>
        <form id="reopenForm" method="post">
            @csrf
            <div class="grid-2" style="margin-bottom:12px">
                <div>
                    <label style="font-size:12px;font-weight:600">New Due Date *</label>
                    <input type="date" name="due_date" id="reopenDueDate" required value="{{ now()->addDay()->toDateString() }}">
                </div>
                <div>
                    <label style="font-size:12px;font-weight:600">New Due Time</label>
                    <input type="time" name="due_time" id="reopenDueTime" value="12:00">
                </div>
            </div>
            <label style="font-size:12px;font-weight:600">Re-Open Reason / Note (Optional)</label>
            <textarea name="remarks" rows="2" placeholder="e.g. Client requested revision..." style="margin-bottom:14px;width:100%"></textarea>
            <div style="display:flex;justify-content:flex-end;gap:8px">
                <button type="button" class="btn light" onclick="closeReopenModal()">Cancel</button>
                <button class="btn" style="background:#0284c7"><i class="fa-solid fa-rotate-left"></i> Re-Open Task</button>
            </div>
        </form>
    </div>
</div>

<script>
let currentActiveTask = null;
const allEmployeesList = @json($employees);

function formatDateTime(dtStr) {
    if (!dtStr) return '';
    try {
        const d = new Date(dtStr);
        if (isNaN(d.getTime())) return dtStr;
        return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) + ' ' + 
               d.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true });
    } catch(e) {
        return dtStr;
    }
}

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

function openNewTaskDrawer() {
    document.getElementById('drawerHeaderTitle').innerText = "New Task";
    document.getElementById('newTaskForm').style.display = 'block';
    document.getElementById('viewTaskDetails').style.display = 'none';

    // Reset department filter and show all company employees in primary assignee dropdown
    const deptSelect = document.getElementById('newTaskDeptSelect');
    if (deptSelect) deptSelect.value = '';
    filterEmployeesByDept('');

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

    // Created On & Category in Drawer
    const createdSpan = document.getElementById('dtlCreatedAt');
    if (createdSpan) {
        createdSpan.innerText = formatDateTime(task.created_at) || 'N/A';
    }
    const catSpan = document.getElementById('dtlCategory');
    if (catSpan) {
        if (task.category) {
            catSpan.innerHTML = `<span class="chip" style="background:${task.category.color ? task.category.color + '20' : '#e0e7ff'};color:${task.category.color || '#4338ca'};font-weight:700">${task.category.name}</span>`;
        } else {
            catSpan.innerText = 'General';
        }
    }

    // Set deadline & status edit form values
    if (document.getElementById('drawerDeadlineForm')) {
        document.getElementById('drawerDeadlineForm').action = "/tasks/" + task.id + "/update-deadline";
    }
    if (document.getElementById('dtlEditDueDate')) {
        document.getElementById('dtlEditDueDate').value = task.due_date ? task.due_date.substring(0, 10) : '';
    }
    if (document.getElementById('dtlEditDueTime')) {
        document.getElementById('dtlEditDueTime').value = task.due_time || '12:00';
    }
    if (document.getElementById('dtlEditStatus')) {
        document.getElementById('dtlEditStatus').value = task.status || 'pending';
    }
    if (document.getElementById('dtlEditRemarks')) {
        document.getElementById('dtlEditRemarks').value = '';
    }

    // Status Select in Drawer Header
    const stSelect = document.getElementById('dtlStatusSelect');
    if (stSelect) {
        stSelect.value = task.status || 'pending';
    }

    // Consolidated Assignees List
    const assigneesDiv = document.getElementById('dtlAssigneesList');
    assigneesDiv.innerHTML = '';
    const allAssigneesMap = new Map();

    if (task.assignee) {
        allAssigneesMap.set(Number(task.assignee.id), {
            id: task.assignee.id,
            name: `${task.assignee.first_name} ${task.assignee.last_name || ''}`.trim(),
            isPrimary: true
        });
    }
    if (Array.isArray(task.assignees)) {
        task.assignees.forEach(u => {
            if (!allAssigneesMap.has(Number(u.id))) {
                allAssigneesMap.set(Number(u.id), {
                    id: u.id,
                    name: `${u.first_name} ${u.last_name || ''}`.trim(),
                    isPrimary: false
                });
            }
        });
    }
    if (Array.isArray(task.others)) {
        task.others.forEach(u => {
            if (!allAssigneesMap.has(Number(u.id))) {
                allAssigneesMap.set(Number(u.id), {
                    id: u.id,
                    name: `${u.first_name} ${u.last_name || ''}`.trim(),
                    isPrimary: false
                });
            }
        });
    }

    if (allAssigneesMap.size === 0) {
        assigneesDiv.innerHTML = '<span class="chip" style="background:#fee2e2;color:#991b1b">Unassigned</span>';
    } else {
        allAssigneesMap.forEach(emp => {
            if (emp.isPrimary) {
                assigneesDiv.innerHTML += `<span class="chip" style="background:var(--accent);color:#fff;font-weight:700" title="Primary Assignee">${emp.name} <span style="font-size:10px;opacity:0.85">(Primary)</span></span>`;
            } else {
                assigneesDiv.innerHTML += `<span class="chip" style="background:#e0f2fe;color:#0369a1;font-weight:600" title="Additional Assignee">${emp.name}</span>`;
            }
        });
    }

    // Creator / Assigned By Info
    const creatorDiv = document.getElementById('dtlCreatorInfo');
    if (creatorDiv) {
        if (task.creator) {
            creatorDiv.innerHTML = `<span class="chip" style="background:#e0e7ff;color:#3730a3;font-weight:700"><i class="fa-solid fa-user-tie"></i> ${task.creator.first_name} ${task.creator.last_name || ''}</span>`;
        } else {
            creatorDiv.innerHTML = `<span class="chip" style="background:#f1f5f9;color:#475569"><i class="fa-solid fa-user-shield"></i> Admin</span>`;
        }
    }

    // Set forms
    if (document.getElementById('drawerStatusForm')) {
        document.getElementById('drawerStatusForm').action = "/tasks/" + task.id + "/status";
    }
    document.getElementById('postReplyForm').action = "/tasks/" + task.id + "/reply";
    document.getElementById('remindTaskForm').action = "/tasks/" + task.id + "/remind";
    document.getElementById('deleteTaskForm').action = "/tasks/" + task.id;

    // Delete permission: only creator or super admin can see delete button
    const currentUserId = {{ (int)auth()->id() }};
    const isSuperAdmin = {{ (auth()->user() && auth()->user()->isSuperAdmin()) ? 'true' : 'false' }};
    const isAdmin = {{ (auth()->user() && auth()->user()->isAdmin()) ? 'true' : 'false' }};
    const deleteBtnForm = document.getElementById('deleteTaskForm');
    if (deleteBtnForm) {
        if (isSuperAdmin || (task.created_by && task.created_by == currentUserId)) {
            deleteBtnForm.style.display = 'inline';
        } else {
            deleteBtnForm.style.display = 'none';
        }
    }

    // Edit description permission (Requirement 3): Creator or Admin
    const btnEditDesc = document.getElementById('btnEditDescription');
    if (btnEditDesc) {
        if (isSuperAdmin || isAdmin || (task.created_by && Number(task.created_by) === currentUserId)) {
            btnEditDesc.style.display = 'inline-block';
        } else {
            btnEditDesc.style.display = 'none';
        }
    }
    cancelEditDescription();

    // Drawer Multiple Attachment Form Action (Requirement 5)
    const drawerAttForm = document.getElementById('drawerUploadAttachmentForm');
    if (drawerAttForm) {
        drawerAttForm.action = "/tasks/" + task.id + "/attachment";
        const fileInput = document.getElementById('drawerAttachmentFiles');
        if (fileInput) fileInput.value = '';
    }

    // Render Attached Files of the Task
    const attContainer = document.getElementById('dtlAttachmentsList');
    if (attContainer) {
        attContainer.innerHTML = '';
        if (task.attachments && task.attachments.length > 0) {
            task.attachments.forEach(att => {
                const fName = att.file_name || att.file_path.split('/').pop();
                attContainer.innerHTML += `
                    <div style="display:flex;align-items:center;justify-content:space-between;background:#f8fafc;padding:6px 10px;border-radius:6px;border:1px solid #e2e8f0;margin-bottom:4px">
                        <a href="/storage/${att.file_path}" target="_blank" style="color:#0284c7;font-size:12px;font-weight:600;display:flex;align-items:center;gap:6px;text-decoration:none">
                            <i class="fa-solid fa-paperclip"></i> ${fName}
                        </a>
                        <form method="post" action="/tasks/attachments/${att.id}" onsubmit="return confirm('Remove this attachment?')" style="margin:0;display:inline">
                            <input type="hidden" name="_token" value="{{ csrf_token() }}">
                            <input type="hidden" name="_method" value="delete">
                            <button type="submit" style="background:none;border:none;color:#ef4444;cursor:pointer;font-size:12px;padding:2px 4px" title="Delete attachment"><i class="fa-solid fa-trash"></i></button>
                        </form>
                    </div>
                `;
            });
        } else if (task.attachment) {
            const fName = task.attachment.split('/').pop();
            attContainer.innerHTML = `
                <div style="background:#f8fafc;padding:6px 10px;border-radius:6px;border:1px solid #e2e8f0">
                    <a href="/storage/${task.attachment}" target="_blank" style="color:#0284c7;font-size:12px;font-weight:600;display:flex;align-items:center;gap:6px;text-decoration:none">
                        <i class="fa-solid fa-paperclip"></i> ${fName}
                    </a>
                </div>
            `;
        } else {
            attContainer.innerHTML = '<span class="muted" style="font-size:12px">No files attached to this task.</span>';
        }
    }

    // Control Re-Open Task button visibility
    const btnReopen = document.getElementById('btnReopenTask');
    if (btnReopen) {
        btnReopen.style.display = (task.status === 'completed') ? 'inline-flex' : 'none';
    }

    // Update Export Replies PDF button link
    const exportRepliesLink = document.getElementById('dtlExportRepliesPdf');
    if (exportRepliesLink) {
        exportRepliesLink.href = "/tasks/" + task.id + "/replies-pdf";
    }

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
        let timeStr = formatDateTime(r.created_at);
        container.innerHTML += `
            <div style="background:#f8fafc;padding:10px;border-radius:8px;border:1px solid #e2e8f0;font-size:12px">
                <div style="display:flex;justify-content:space-between;font-weight:700;color:#111827">
                    <span>${name}</span>
                    <small class="muted" style="color:#64748b;font-weight:600"><i class="fa-regular fa-clock" style="font-size:10px"></i> ${timeStr}</small>
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
        let timeStr = formatDateTime(h.created_at);
        container.innerHTML += `
            <div style="background:#fff;padding:8px 12px;border-radius:6px;border-left:3px solid var(--accent);box-shadow:0 1px 2px rgba(0,0,0,0.04);font-size:12px">
                <div style="font-weight:700;color:#111827">${h.action.toUpperCase()} by ${uname}</div>
                <div class="muted">${h.details || ''}</div>
                <small style="color:#64748b;font-weight:600;display:block;margin-top:4px"><i class="fa-regular fa-clock" style="font-size:10px"></i> ${timeStr}</small>
            </div>
        `;
    });
}

function switchDrawerTab(tab) {
    document.getElementById('drawerTabDetails').style.display = tab === 'details' ? 'block' : 'none';
    document.getElementById('drawerTabReports').style.display = tab === 'reports' ? 'block' : 'none';
    document.getElementById('drawerTabHistory').style.display = tab === 'history' ? 'block' : 'none';

    const btnDetails = document.getElementById('tabBtnDetails');
    const btnReports = document.getElementById('tabBtnReports');
    const btnHistory = document.getElementById('tabBtnHistory');

    if (btnDetails) {
        btnDetails.style.fontWeight = tab === 'details' ? '700' : '500';
        btnDetails.style.color = tab === 'details' ? 'var(--accent)' : '#64748b';
        btnDetails.style.borderBottom = tab === 'details' ? '2px solid var(--accent)' : 'none';
        btnDetails.style.paddingBottom = '4px';
    }
    if (btnReports) {
        btnReports.style.fontWeight = tab === 'reports' ? '700' : '500';
        btnReports.style.color = tab === 'reports' ? 'var(--accent)' : '#64748b';
        btnReports.style.borderBottom = tab === 'reports' ? '2px solid var(--accent)' : 'none';
        btnReports.style.paddingBottom = '4px';
    }
    if (btnHistory) {
        btnHistory.style.fontWeight = tab === 'history' ? '700' : '500';
        btnHistory.style.color = tab === 'history' ? 'var(--accent)' : '#64748b';
        btnHistory.style.borderBottom = tab === 'history' ? '2px solid var(--accent)' : 'none';
        btnHistory.style.paddingBottom = '4px';
    }
}

function openReassignModal() {
    if (!currentActiveTask) return;
    closeTaskDrawer();
    document.getElementById('reassignForm').action = "/tasks/" + currentActiveTask.id + "/reassign";
    
    // Reset picker state
    const dropdown = document.getElementById('dropdown_picker_reassign');
    if (dropdown) {
        dropdown.querySelectorAll('.dept-filter-chk').forEach(c => c.checked = false);
        dropdown.querySelectorAll('.emp-select-chk').forEach(c => c.checked = false);
        dropdown.style.display = 'none';
        applyPickerFilters('picker_reassign');
    }
    const labelSpan = document.getElementById('picker_label_reassign');
    if (labelSpan) {
        labelSpan.innerText = 'Select New Assignee(s)';
        labelSpan.style.color = '#64748b';
        labelSpan.style.fontWeight = 'normal';
    }

    document.getElementById('reassignModal').classList.add('open');
}

function closeReassignModal() {
    document.getElementById('reassignModal').classList.remove('open');
}

function openReopenModal() {
    if (!currentActiveTask) return;
    closeTaskDrawer();
    document.getElementById('reopenForm').action = "/tasks/" + currentActiveTask.id + "/reopen";
    if (currentActiveTask.due_date) {
        document.getElementById('reopenDueDate').value = currentActiveTask.due_date.substring(0, 10);
    }
    if (currentActiveTask.due_time) {
        document.getElementById('reopenDueTime').value = currentActiveTask.due_time;
    }
    document.getElementById('reopenModal').classList.add('open');
}

function closeReopenModal() {
    document.getElementById('reopenModal').classList.remove('open');
}

// Debounced Task Search as user types
let taskSearchTimer = null;
function debounceTaskSearch(input) {
    clearTimeout(taskSearchTimer);
    taskSearchTimer = setTimeout(() => {
        const form = document.getElementById('taskFilterForm');
        if (form) form.submit();
    }, 450);
}

// Dynamically filter primary employee dropdown based on selected Department (if chosen)
function filterEmployeesByDept(deptId) {
    const select = document.getElementById('newTaskAssigneeSelect');
    if (!select) return;
    const options = select.querySelectorAll('option');
    options.forEach((opt, idx) => {
        if (idx === 0) return; // keep default placeholder
        const optDept = opt.getAttribute('data-dept-id');
        if (!deptId || optDept === deptId) {
            opt.style.display = '';
        } else {
            opt.style.display = 'none';
        }
    });
    if (deptId && select.value) {
        const selectedOpt = select.querySelector(`option[value="${select.value}"]`);
        if (selectedOpt && selectedOpt.style.display === 'none') {
            select.value = '';
        }
    }
}

function toggleEmpPicker(pickerId) {
    const dropdown = document.getElementById('dropdown_' + pickerId);
    if (!dropdown) return;
    const isVisible = dropdown.style.display === 'block';
    
    // Close any other open dropdowns first
    document.querySelectorAll('.picker-dropdown').forEach(d => {
        if (d !== dropdown) d.style.display = 'none';
    });
    
    dropdown.style.display = isVisible ? 'none' : 'block';
}

function applyPickerFilters(pickerId) {
    const dropdown = document.getElementById('dropdown_' + pickerId);
    if (!dropdown) return;

    const checkedDeptBoxes = dropdown.querySelectorAll('.dept-filter-chk:checked');
    const checkedDeptIds = Array.from(checkedDeptBoxes).map(cb => cb.getAttribute('data-dept-id'));
    const searchInput = dropdown.querySelector('.picker-search-input');
    const searchVal = searchInput ? searchInput.value.toLowerCase().trim() : '';

    const empItems = dropdown.querySelectorAll('.picker-emp-item');
    empItems.forEach(item => {
        const itemDeptId = item.getAttribute('data-dept-id');
        const itemName = item.getAttribute('data-name');
        
        const matchesDept = checkedDeptIds.length === 0 || checkedDeptIds.includes(itemDeptId);
        const matchesSearch = !searchVal || itemName.includes(searchVal);

        item.style.display = (matchesDept && matchesSearch) ? 'flex' : 'none';
    });
}

function onPickerSearch(pickerId, val) {
    applyPickerFilters(pickerId);
}

function selectAllPickerEmployees(pickerId, selectAll) {
    const dropdown = document.getElementById('dropdown_' + pickerId);
    if (!dropdown) return;

    const visibleItems = dropdown.querySelectorAll('.picker-emp-item');
    visibleItems.forEach(item => {
        if (item.style.display !== 'none') {
            const chk = item.querySelector('.emp-select-chk');
            if (chk) chk.checked = selectAll;
        }
    });

    if (pickerId === 'picker_primary') {
        onEmpPickerChange('picker_primary', 'single');
    } else if (pickerId === 'picker_others') {
        onEmpPickerChange('picker_others', 'multiple', 'assignee_ids[]');
    } else if (pickerId === 'picker_reassign') {
        onEmpPickerChange('picker_reassign', 'multiple', 'new_assigned_to[]');
    }
}

function onEmpPickerChange(pickerId, mode, inputName = '') {
    const dropdown = document.getElementById('dropdown_' + pickerId);
    const labelSpan = document.getElementById('picker_label_' + pickerId);
    if (!dropdown || !labelSpan) return;

    if (mode === 'single') {
        const checked = dropdown.querySelectorAll('.emp-select-chk:checked');
        if (checked.length > 1) {
            checked.forEach((c, idx) => {
                if (idx < checked.length - 1) c.checked = false;
            });
        }
        const lastChecked = dropdown.querySelector('.emp-select-chk:checked');
        const hiddenInput = document.getElementById('input_primary');
        if (lastChecked) {
            labelSpan.innerText = lastChecked.getAttribute('data-name');
            labelSpan.style.color = '#1e293b';
            labelSpan.style.fontWeight = '700';
            if (hiddenInput) hiddenInput.value = lastChecked.value;
        } else {
            labelSpan.innerText = 'Select Primary Employee';
            labelSpan.style.color = '#64748b';
            labelSpan.style.fontWeight = 'normal';
            if (hiddenInput) hiddenInput.value = '';
        }
    } else {
        const checked = dropdown.querySelectorAll('.emp-select-chk:checked');
        const container = document.getElementById(pickerId === 'picker_others' ? 'hidden_inputs_others' : 'hidden_inputs_reassign');
        if (container) {
            container.innerHTML = '';
            checked.forEach(c => {
                const hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = inputName;
                hidden.value = c.value;
                container.appendChild(hidden);
            });
        }

        if (checked.length === 0) {
            labelSpan.innerText = pickerId === 'picker_others' ? 'Select Additional Assignees (Optional)' : 'Select New Assignee(s)';
            labelSpan.style.color = '#64748b';
            labelSpan.style.fontWeight = 'normal';
        } else if (checked.length === 1) {
            labelSpan.innerText = checked[0].getAttribute('data-name');
            labelSpan.style.color = '#1e293b';
            labelSpan.style.fontWeight = '700';
        } else {
            labelSpan.innerText = checked.length + ' employees selected';
            labelSpan.style.color = '#2563eb';
            labelSpan.style.fontWeight = '700';
        }
    }
}

// Close pickers when clicking outside
document.addEventListener('click', function(e) {
    if (!e.target.closest('.custom-emp-picker')) {
        document.querySelectorAll('.picker-dropdown').forEach(d => d.style.display = 'none');
    }
});

function openNewCategoryModal() {
    const m = document.getElementById('newCategoryModal');
    if (m) {
        m.classList.add('open');
        m.style.display = 'flex';
        document.getElementById('catModalName').value = '';
        document.getElementById('catModalDesc').value = '';
        setTimeout(() => document.getElementById('catModalName').focus(), 100);
    }
}

function closeNewCategoryModal() {
    const m = document.getElementById('newCategoryModal');
    if (m) {
        m.classList.remove('open');
        m.style.display = 'none';
    }
}

async function submitNewCategory(e) {
    e.preventDefault();
    const nameInput = document.getElementById('catModalName');
    const colorInput = document.getElementById('catModalColor');
    const descInput = document.getElementById('catModalDesc');
    const btn = document.getElementById('btnSaveCategory');

    const name = nameInput ? nameInput.value.trim() : '';
    const color = colorInput ? colorInput.value : '#4f46e5';
    const description = descInput ? descInput.value.trim() : '';

    if (!name) return;
    btn.disabled = true;
    btn.innerText = 'Saving...';

    try {
        const resp = await fetch('{{ route("tasks.categories.store") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            },
            body: JSON.stringify({ name, color, description })
        });

        const res = await resp.json();
        if (res.ok && res.category) {
            // Dynamically add to taskCategorySelect in New Task Drawer and select it
            const catSelect = document.getElementById('taskCategorySelect');
            if (catSelect) {
                let existingOpt = Array.from(catSelect.options).find(o => String(o.value) === String(res.category.id));
                if (!existingOpt) {
                    const opt = new Option(res.category.name, res.category.id, true, true);
                    catSelect.add(opt);
                }
                catSelect.value = res.category.id;
            }
            closeNewCategoryModal();
        } else {
            alert(res.message || 'Error creating category');
        }
    } catch(err) {
        console.error(err);
        alert('Failed to create category. Please check your network.');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-check"></i> Save Category';
    }
}

function startEditDescription() {
    if (!currentActiveTask) return;
    const input = document.getElementById('dtlDescriptionInput');
    if (input) input.value = currentActiveTask.description || '';
    const container = document.getElementById('descEditContainer');
    if (container) {
        container.style.display = 'block';
        input.focus();
    }
}

function cancelEditDescription() {
    const container = document.getElementById('descEditContainer');
    if (container) container.style.display = 'none';
}

async function saveTaskDescription() {
    if (!currentActiveTask) return;
    const btn = document.getElementById('btnSaveDesc');
    const input = document.getElementById('dtlDescriptionInput');
    const newDesc = input ? input.value.trim() : '';

    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saving...';

    try {
        const resp = await fetch(`/tasks/${currentActiveTask.id}/update-description`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ description: newDesc })
        });

        const res = await resp.json();
        if (res.ok) {
            currentActiveTask.description = res.description || '';
            const dtlDesc = document.getElementById('dtlDescription');
            if (dtlDesc) dtlDesc.innerText = currentActiveTask.description || 'No description provided.';
            cancelEditDescription();
        } else {
            alert(res.message || 'Failed to update description.');
        }
    } catch(err) {
        console.error(err);
        alert('Failed to save description. Please check your connection.');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-check"></i> Save Description';
    }
}

async function submitDrawerAttachments(e) {
    e.preventDefault();
    if (!currentActiveTask) return;
    const form = document.getElementById('drawerUploadAttachmentForm');
    const fileInput = document.getElementById('drawerAttachmentFiles');
    if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
        alert('Please select at least one file to upload.');
        return;
    }

    const btn = document.getElementById('btnUploadDrawerAtt');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Uploading...';

    const formData = new FormData(form);
    try {
        const resp = await fetch(`/tasks/${currentActiveTask.id}/attachment`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: formData
        });

        const res = await resp.json();
        if (res.ok) {
            fileInput.value = '';
            if (res.attachments) {
                currentActiveTask.attachments = res.attachments;
                const attContainer = document.getElementById('dtlAttachmentsList');
                if (attContainer) {
                    attContainer.innerHTML = '';
                    res.attachments.forEach(att => {
                        const fName = att.file_name || att.file_path.split('/').pop();
                        attContainer.innerHTML += `
                            <div style="display:flex;align-items:center;justify-content:space-between;background:#f8fafc;padding:6px 10px;border-radius:6px;border:1px solid #e2e8f0;margin-bottom:4px">
                                <a href="/storage/${att.file_path}" target="_blank" style="color:#0284c7;font-size:12px;font-weight:600;display:flex;align-items:center;gap:6px;text-decoration:none">
                                    <i class="fa-solid fa-paperclip"></i> ${fName}
                                </a>
                                <form method="post" action="/tasks/attachments/${att.id}" onsubmit="return confirm('Remove this attachment?')" style="margin:0;display:inline">
                                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                    <input type="hidden" name="_method" value="delete">
                                    <button type="submit" style="background:none;border:none;color:#ef4444;cursor:pointer;font-size:12px;padding:2px 4px" title="Delete attachment"><i class="fa-solid fa-trash"></i></button>
                                </form>
                            </div>
                        `;
                    });
                }
            }
        } else {
            alert(res.message || 'Error uploading attachments.');
        }
    } catch(err) {
        console.error(err);
        form.submit();
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-upload"></i> Upload';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    @if(isset($openTask) && $openTask)
        const initialTask = @json($openTask);
        const initialTab = @json(request('tab', 'details'));
        openTaskDetailsDrawer(initialTask, initialTab);

        // Requirement 2: Clean open_task & tab from URL so subsequent refreshes or actions don't keep opening the drawer
        try {
            const cleanUrl = new URL(window.location.href);
            cleanUrl.searchParams.delete('open_task');
            cleanUrl.searchParams.delete('tab');
            window.history.replaceState({}, document.title, cleanUrl.toString());
        } catch (e) {
            console.error('URL replace error:', e);
        }
    @endif
});
</script>

<!-- Modal: Add New Task Category (high z-index to appear on top of New Task Drawer) -->
<div id="newCategoryModal" class="modal-bg" onclick="if(event.target===this) closeNewCategoryModal()" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(15,23,42,0.6);backdrop-filter:blur(3px);z-index:100050!important;align-items:center;justify-content:center">
    <div class="modal" style="background:#fff;max-width:440px;width:92%;padding:22px;border-radius:14px;box-shadow:0 25px 60px rgba(0,0,0,0.35);margin:auto;position:relative;z-index:100051" onclick="event.stopPropagation()">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px">
            <h3 style="margin:0;font-size:16px"><i class="fa-solid fa-tags" style="color:var(--accent)"></i> Add New Task Category</h3>
            <button type="button" onclick="closeNewCategoryModal()" style="border:0;background:none;font-size:22px;cursor:pointer;color:#64748b">&times;</button>
        </div>
        <p class="muted" style="font-size:12px;margin-bottom:14px">Create custom task categories like Electricity Bill, Credit Card, Insurance Payment, Sales OS Followup, etc.</p>
        <form id="ajaxCategoryForm" onsubmit="submitNewCategory(event)">
            @csrf
            <label style="font-size:12.5px;font-weight:600;display:block;margin-bottom:4px">Category Name *</label>
            <input type="text" id="catModalName" name="name" placeholder="e.g. Electricity Bill, Sales OS Followup..." required style="margin-bottom:12px;width:100%">

            <label style="font-size:12.5px;font-weight:600;display:block;margin-bottom:4px">Category Color (Badge)</label>
            <input type="color" id="catModalColor" name="color" value="#4f46e5" style="width:100%;height:38px;padding:2px;border-radius:6px;border:1px solid #cbd5e1;cursor:pointer;margin-bottom:12px">

            <label style="font-size:12.5px;font-weight:600;display:block;margin-bottom:4px">Description (Optional)</label>
            <textarea id="catModalDesc" name="description" rows="2" placeholder="Brief note about this category..." style="margin-bottom:16px;width:100%"></textarea>

            <div style="display:flex;justify-content:flex-end;gap:8px">
                <button type="button" class="btn light" onclick="closeNewCategoryModal()">Cancel</button>
                <button type="submit" id="btnSaveCategory" class="btn"><i class="fa-solid fa-check"></i> Save Category</button>
            </div>
        </form>
    </div>
</div>
@endsection
