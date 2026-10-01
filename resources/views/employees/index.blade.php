@extends('layouts.app')
@section('content')
@php
$hour = (int) now('Asia/Kolkata')->format('G');
$greet = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
$colors = ['#7c3aed','#2563eb','#059669','#db2777','#ea580c','#4f46e5'];
@endphp

<div class="page-head">
    <h1>Employees</h1>
    <div class="row">
        <a class="btn light" href="{{ route('tracking.realtime') }}"><i class="fa-solid fa-location-dot"></i> Live View</a>
        <button class="btn light" onclick="openBulkShiftModal()"><i class="fa-solid fa-clock"></i> Bulk Shift</button>
        <button class="btn light" onclick="openBulkMarkModal()"><i class="fa-solid fa-check-double"></i> Bulk Attendance</button>
        <a class="btn" href="{{ route('employees.create') }}">+ Add employee</a>
    </div>
</div>

<div class="card hello">
    <div class="row" style="justify-space-between">
        <div>
            <h2>{{ $greet }}</h2>
            <p>Here's the attendance status of employees at</p>
            <div class="co">{{ $setting->company_name }}</div>
        </div>
        <div class="row">
            <span class="badge ok"><i class="fa-solid fa-circle" style="font-size:8px"></i> Live</span>
            <form method="get" id="dateForm">
                <input type="date" name="date" value="{{ $date->toDateString() }}" onchange="document.getElementById('dateForm').submit()">
            </form>
        </div>
    </div>
</div>

<div class="card" style="margin-top:14px">
    <div class="stats">
        <div>
            <strong>Attendance<br>Statistics</strong>
            <div class="muted" style="margin-top:8px">{{ $date->isToday() ? 'Today' : $date->format('d M Y') }}</div>
        </div>
        <div>
            <div class="kpi">
                <div style="cursor:pointer" onclick="openStatModal('present')"><span class="bar" style="background:#16a34a"></span><small>PRESENT</small><b>{{ $stats['present'] }}</b></div>
                <div style="cursor:pointer" onclick="openStatModal('absent')"><span class="bar" style="background:#ef4444"></span><small>ABSENT</small><b>{{ $stats['absent'] }}</b></div>
                <div style="cursor:pointer" onclick="openStatModal('not_marked')"><span class="bar" style="background:#9ca3af"></span><small>NOT MARKED</small><b>{{ $stats['not_marked'] }}</b></div>
                <div style="cursor:pointer" onclick="openStatModal('late')"><span class="bar" style="background:#eab308"></span><small>LATE</small><b>{{ $stats['late'] }}</b></div>
                <div style="cursor:pointer" onclick="openStatModal('leave')"><span class="bar" style="background:#f97316"></span><small>LEAVE</small><b>{{ $stats['leave'] }}</b></div>
                <div style="cursor:pointer" onclick="openStatModal('early')"><span class="bar" style="background:#fb923c"></span><small>EARLY</small><b>{{ $stats['early'] }}</b></div>
                <!-- EMPLOYEE LATE ALERTS (Standard KPI size matching screenshot) -->
                <div style="cursor:pointer" onclick="openStatModal('late')"><span class="bar" style="background:#dc2626"></span><small style="color:#991b1b;font-weight:700">LATE ALERTS</small><b style="color:#dc2626">{{ $stats['late'] }}</b></div>
                <!-- PENDING TASK COUNT LINK -->
                <div style="cursor:pointer" onclick="location.href='{{ route('tasks.index', ['status'=>'pending']) }}'"><span class="bar" style="background:#ea580c"></span><small style="color:#ea580c;font-weight:700">PENDING TASK</small><b style="color:#ea580c">{{ $stats['pending_tasks'] ?? 0 }}</b></div>
            </div>
            <div class="kpi" style="margin-top:14px;padding-top:12px;border-top:1px dashed #e5e7eb">
                <div style="cursor:pointer" onclick="openStatModal('total')"><span class="bar" style="background:#9ca3af"></span><small>TOTAL HEADS</small><b>{{ $stats['total'] }}</b></div>
                <div style="cursor:pointer" onclick="openStatModal('admin')"><span class="bar" style="background:#9ca3af"></span><small>ADMIN</small><b>{{ $stats['admin'] }}</b></div>
                <div style="cursor:pointer" onclick="openStatModal('manager')"><span class="bar" style="background:#9ca3af"></span><small>MANAGER</small><b>{{ $stats['manager'] }}</b></div>
                <div style="cursor:pointer" onclick="openStatModal('employee')"><span class="bar" style="background:#9ca3af"></span><small>EMPLOYEE</small><b>{{ $stats['employee'] }}</b></div>
                <div style="cursor:pointer" onclick="openStatModal('archived')"><span class="bar" style="background:#9ca3af"></span><small>ARCHIVED</small><b>{{ $stats['archived'] }}</b></div>
            </div>
        </div>
    </div>
</div>

<!-- Filter Bar & Search/Sort controls -->
<div class="card" style="margin-top:14px;padding:12px 18px">
    <form method="get" class="row" style="gap:12px;justify-content:space-between">
        <input type="hidden" name="date" value="{{ $date->toDateString() }}">
        <div class="row" style="flex:1">
            <input type="text" name="q" value="{{ $q }}" placeholder="Search employees by name, serial no, phone, dept..." style="max-width:380px">
            
            @if($categories->count() > 0)
                <select name="category" onchange="this.form.submit()" style="width:160px">
                    <option value="">All Categories</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ $categoryId==$cat->id?'selected':'' }}>{{ $cat->name }}</option>
                    @endforeach
                </select>
            @endif

            @if($departments->count() > 0)
                <select name="department_id" onchange="this.form.submit()" style="width:160px">
                    <option value="">All Departments</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept->id }}" {{ $departmentId==$dept->id?'selected':'' }}>{{ $dept->name }}</option>
                    @endforeach
                </select>
            @endif

            @if($companies->count() > 0)
                <select name="company_id" onchange="this.form.submit()" style="width:160px">
                    <option value="">All Companies</option>
                    @foreach($companies as $comp)
                        <option value="{{ $comp->id }}" {{ $companyId==$comp->id?'selected':'' }}>{{ $comp->name }}</option>
                    @endforeach
                </select>
            @endif
        </div>

        <!-- Sleek Sort dropdown menu matching Screenshot 4 -->
        <div class="row" style="gap:8px">
            <a class="btn light" href="{{ route('attendances.index', ['date'=>$date->toDateString()]) }}" style="font-size:12px;padding:6px 12px;border-radius:8px">Clear all</a>
            <div style="position:relative">
                <button type="button" class="btn light" onclick="toggleSortMenu()" style="border-radius:10px;padding:8px 12px">
                    <i class="fa-solid fa-arrows-up-down"></i>
                </button>
                <div id="sortMenu" style="display:none;position:absolute;right:0;top:44px;background:#fff;border:1px solid #e5e7eb;box-shadow:0 12px 30px rgba(0,0,0,0.12);border-radius:14px;padding:14px;width:190px;z-index:40">
                    <div style="font-size:13px;font-weight:700;color:#111827;margin-bottom:8px">Sort by</div>
                    <label style="display:flex;align-items:center;gap:8px;margin:6px 0;font-size:13px;cursor:pointer;color:#374151">
                        <input type="radio" name="sort_by" value="name" {{ $sortBy==='name'?'checked':'' }} onchange="this.form.submit()" style="accent-color:#111"> Name
                    </label>
                    <label style="display:flex;align-items:center;gap:8px;margin:6px 0;font-size:13px;cursor:pointer;color:#374151">
                        <input type="radio" name="sort_by" value="designation" {{ $sortBy==='designation'?'checked':'' }} onchange="this.form.submit()" style="accent-color:#111"> Designation
                    </label>
                    <label style="display:flex;align-items:center;gap:8px;margin:6px 0;font-size:13px;cursor:pointer;color:#374151">
                        <input type="radio" name="sort_by" value="department" {{ $sortBy==='department'?'checked':'' }} onchange="this.form.submit()" style="accent-color:#111"> Department
                    </label>
                    <label style="display:flex;align-items:center;gap:8px;margin:6px 0;font-size:13px;cursor:pointer;color:#374151">
                        <input type="radio" name="sort_by" value="category" {{ $sortBy==='category'?'checked':'' }} onchange="this.form.submit()" style="accent-color:#111"> Category
                    </label>
                    
                    <div style="background:#f3f4f6;padding:4px;border-radius:10px;margin-top:10px;display:flex;flex-direction:column;gap:2px">
                        <label style="display:flex;align-items:center;gap:6px;font-size:12px;cursor:pointer;padding:6px 10px;border-radius:8px;{{ $sortDir==='asc'?'background:#fff;font-weight:700;box-shadow:0 1px 3px rgba(0,0,0,0.05);':'color:#6b7280;' }}">
                            <input type="radio" name="sort_dir" value="asc" {{ $sortDir==='asc'?'checked':'' }} onchange="this.form.submit()" style="display:none"> ↑ A-Z
                        </label>
                        <label style="display:flex;align-items:center;gap:6px;font-size:12px;cursor:pointer;padding:6px 10px;border-radius:8px;{{ $sortDir==='desc'?'background:#fff;font-weight:700;box-shadow:0 1px 3px rgba(0,0,0,0.05);':'color:#6b7280;' }}">
                            <input type="radio" name="sort_dir" value="desc" {{ $sortDir==='desc'?'checked':'' }} onchange="this.form.submit()" style="display:none"> ↓ Z-A
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Main Employee Table -->
<div class="card" style="margin-top:14px">
        <table class="table">
            <thead>
            <tr>
                <th style="width:30px"><input type="checkbox" onchange="toggleSelectAll(this)"></th>
                <th>Name <i class="fa-solid fa-sort"></i></th>
                <th>Code</th>
                <th>Designation</th>
                <th>Phone</th>
                <th>Department</th>
                <th>Category</th>
                <th>Attendance</th>
                <th>Total Working Hours</th>
                <th>Actions</th>
            </tr>
            </thead>
            <tbody>
            @forelse($rows as $i => $row)
                @php $u = $row['user']; $color = $colors[$i % count($colors)]; @endphp
                <tr>
                    <td><input type="checkbox" name="employee_ids[]" value="{{ $u->id }}" class="emp-checkbox"></td>
                    <td>
                        <a class="person" href="{{ route('employees.show', $u) }}">
                            @if($u->profile_photo)
                                <img src="{{ asset('storage/'.$u->profile_photo) }}" alt="Photo" style="width:34px;height:34px;object-fit:cover;border-radius:50%;">
                            @else
                                <span class="dot" style="background:{{ $color }}">{{ $u->initials() }}</span>
                            @endif
                            <div>
                                <div>{{ $u->displayName() }}</div>
                                <small class="muted">{{ $u->company->name ?? '' }}</small>
                            </div>
                        </a>
                    </td>
                    <td><code>{{ $u->employee_code ?: 'N/A' }}</code></td>
                    <td>{{ $u->designation ?: '-' }}</td>
                    <td>{{ $u->phone }}</td>
                    <td>{{ $u->department ?: ($u->department_id ? \App\Models\Department::find($u->department_id)?->name : '-') }}</td>
                    <td><span class="chip">{{ $u->category->name ?? 'Unassigned' }}</span></td>
                    <td>
                        @forelse($row['punches'] as $p)
                            <span class="badge {{ $p->type }}">{{ strtoupper($p->type) }} {{ $p->punched_at->format('g:i A') }}</span>
                        @empty
                            @if($row['status'] === 'leave')
                                <span class="badge warn">LEAVE</span>
                            @elseif($row['status'] === 'week_off')
                                <span class="badge" style="background:#e0f2fe;color:#0369a1">WEEK OFF</span>
                            @elseif($row['status'] === 'wop')
                                <span class="badge ok">WOP (Week Off Present)</span>
                            @else
                                <span class="muted">Not Marked</span>
                            @endif
                        @endforelse
                    </td>
                    <td>
                        @php
                            $ins = $row['punches']->where('type', 'in')->values();
                            $outs = $row['punches']->where('type', 'out')->values();
                            $min = 0;
                            for($k=0;$k<max($ins->count(),$outs->count());$k++){
                                if(isset($ins[$k], $outs[$k])) {
                                    $min += $ins[$k]->punched_at->diffInMinutes($outs[$k]->punched_at);
                                }
                            }
                        @endphp
                        {{ sprintf('%02d:%02d hrs', intdiv($min, 60), $min % 60) }}
                    </td>
                    <td>
                        @if($u->status === 'archived')
                            <form method="post" action="{{ route('employees.restore', $u) }}" style="display:inline">@csrf<button class="btn light" style="font-size:12px;padding:4px 8px">Restore</button></form>
                        @else
                            <a href="{{ route('employees.show', $u) }}" class="btn light" style="font-size:12px;padding:4px 8px">View</a>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="10" style="text-align:center;padding:24px" class="muted">No employees found matching criteria.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

<!-- POPUP MODAL for Clickable Attendance Statistics (Matching Screenshot 2) -->
<div id="statModal" class="modal-bg">
    <div class="modal" style="max-width:680px">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px">
            <h3 id="statModalTitle" style="margin:0">Present Statistics for Today</h3>
            <button class="btn light" onclick="closeStatModal()">&times;</button>
        </div>
        <input type="text" id="statSearch" placeholder="Search employee..." onkeyup="filterStatModal()" style="margin-bottom:14px">
        <div style="max-height:360px;overflow-y:auto">
            <table class="table">
                <thead>
                    <tr><th>Employee</th><th>Attendance</th></tr>
                </thead>
                <tbody id="statModalTable">
                    <!-- Populated dynamically via JavaScript -->
                </tbody>
            </table>
        </div>
        <div style="text-align:right;margin-top:16px">
            <button class="btn" onclick="closeStatModal()">Close</button>
        </div>
    </div>
</div>

<!-- Modal: Bulk Shift Assign -->
<div id="bulkShiftModal" class="modal-bg">
    <div class="modal">
        <h3>Bulk Shift Assignment</h3>
        <p class="muted">Select shift to assign to checked employees:</p>
        <form method="post" action="{{ route('employees.bulk-shift') }}" onsubmit="return validateBulkSubmit(this)">
            @csrf
            <div id="bulkShiftInputs"></div>
            <label>Select Shift</label>
            <select name="shift_id" required>
                @foreach($shifts as $s)
                    <option value="{{ $s->id }}">{{ $s->name }} ({{ substr($s->start_time,0,5) }} - {{ substr($s->end_time,0,5) }})</option>
                @endforeach
            </select>
            <div class="row" style="margin-top:16px;justify-content:flex-end">
                <button type="button" class="btn light" onclick="document.getElementById('bulkShiftModal').classList.remove('open')">Cancel</button>
                <button class="btn">Assign Shift</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Bulk Attendance Mark -->
<div id="bulkMarkModal" class="modal-bg">
    <div class="modal">
        <h3>Multiple Manually Mark Attendance</h3>
        <p class="muted">Mark attendance for checked employees with remarks:</p>
        <form method="post" action="{{ route('employees.bulk-mark') }}" onsubmit="return validateBulkSubmit(this)">
            @csrf
            <input type="hidden" name="date" value="{{ $date->toDateString() }}">
            <div id="bulkMarkInputs"></div>
            <div class="grid-2">
                <div>
                    <label>Punch Type</label>
                    <select name="type" required>
                        <option value="in">Punch IN</option>
                        <option value="out">Punch OUT</option>
                    </select>
                </div>
                <div>
                    <label>Time (optional)</label>
                    <input type="time" name="time">
                </div>
            </div>
            <label>Reason / Remarks (Mandatory/Optional)</label>
            <textarea name="remarks" placeholder="Enter reason for manual marking..."></textarea>
            <div class="row" style="margin-top:16px;justify-content:flex-end">
                <button type="button" class="btn light" onclick="document.getElementById('bulkMarkModal').classList.remove('open')">Cancel</button>
                <button class="btn">Mark Attendance</button>
            </div>
        </form>
    </div>
</div>

@endsection

@section('scripts')
<script>
function toggleSortMenu() {
    var menu = document.getElementById('sortMenu');
    menu.style.display = menu.style.display === 'none' ? 'block' : 'none';
}

function openBulkShiftModal() {
    updateBulkInputs();
    var checked = document.querySelectorAll('.emp-checkbox:checked');
    if (checked.length === 0) {
        alert('Please select at least one employee using the checkboxes before assigning bulk shift.');
        return;
    }
    document.getElementById('bulkShiftModal').classList.add('open');
}

function openBulkMarkModal() {
    updateBulkInputs();
    var checked = document.querySelectorAll('.emp-checkbox:checked');
    if (checked.length === 0) {
        alert('Please select at least one employee using the checkboxes before marking bulk attendance.');
        return;
    }
    document.getElementById('bulkMarkModal').classList.add('open');
}

function validateBulkSubmit(form) {
    updateBulkInputs();
    var inputs = form.querySelectorAll('input[name="employee_ids[]"]');
    if (inputs.length === 0) {
        alert('Please select at least one employee from the table using checkboxes.');
        return false;
    }
    return true;
}

function toggleSelectAll(master) {
    var checkboxes = document.querySelectorAll('.emp-checkbox');
    checkboxes.forEach(c => c.checked = master.checked);
    updateBulkInputs();
}

function updateBulkInputs() {
    var checked = document.querySelectorAll('.emp-checkbox:checked');
    var shiftContainer = document.getElementById('bulkShiftInputs');
    var markContainer = document.getElementById('bulkMarkInputs');
    if (shiftContainer) shiftContainer.innerHTML = '';
    if (markContainer) markContainer.innerHTML = '';
    checked.forEach(c => {
        if (shiftContainer) shiftContainer.innerHTML += `<input type="hidden" name="employee_ids[]" value="${c.value}">`;
        if (markContainer) markContainer.innerHTML += `<input type="hidden" name="employee_ids[]" value="${c.value}">`;
    });
}

document.querySelectorAll('.emp-checkbox').forEach(c => {
    c.addEventListener('change', updateBulkInputs);
});

function openStatModal(type) {
    var date = "{{ $date->toDateString() }}";
    fetch(`{{ route('attendances.stat-modal') }}?type=${type}&date=${date}`)
        .then(res => res.json())
        .then(data => {
            document.getElementById('statModalTitle').innerText = data.title;
            var tbody = document.getElementById('statModalTable');
            tbody.innerHTML = '';
            if (data.employees.length === 0) {
                tbody.innerHTML = '<tr><td colspan="2" class="muted" style="text-align:center;padding:16px">No employees found for this status.</td></tr>';
            } else {
                data.employees.forEach(emp => {
                    var showUrl = `{{ url('employees') }}/${emp.id}`;
                    
                    var badgeHtml = '';
                    if (emp.in_time || emp.out_time) {
                        if (emp.in_time) {
                            badgeHtml += `<span style="background:#bbf7d0;color:#166534;padding:4px 10px;border-radius:12px;font-weight:700;font-size:11px;margin-right:4px">IN ${emp.in_time}</span>`;
                        }
                        if (emp.out_time) {
                            badgeHtml += `<span style="background:#fef08a;color:#854d0e;padding:4px 10px;border-radius:12px;font-weight:700;font-size:11px">OUT ${emp.out_time}</span>`;
                        }
                    } else if (emp.status === 'not_marked') {
                        badgeHtml = `<span style="background:#f3f4f6;color:#6b7280;padding:4px 12px;border-radius:12px;font-weight:600;font-size:12px">Not Marked</span>`;
                    } else if (emp.status === 'absent') {
                        badgeHtml = `<span style="background:#fee2e2;color:#991b1b;padding:4px 12px;border-radius:12px;font-weight:600;font-size:12px">Absent</span>`;
                    } else if (emp.status === 'leave') {
                        badgeHtml = `<span style="background:#ffedd5;color:#9a3412;padding:4px 12px;border-radius:12px;font-weight:600;font-size:12px">Leave</span>`;
                    } else if (emp.status === 'archived') {
                        badgeHtml = `<span style="background:#f3f4f6;color:#9ca3af;padding:4px 12px;border-radius:12px;font-weight:600;font-size:12px">Archived</span>`;
                    } else {
                        badgeHtml = `<span style="background:#f3f4f6;color:#374151;padding:4px 12px;border-radius:12px;font-weight:600;font-size:12px">${emp.status.toUpperCase()}</span>`;
                    }

                    tbody.innerHTML += `
                        <tr style="border-bottom:1px solid #f3f4f6">
                            <td style="padding:12px 14px">
                                <a href="${showUrl}" style="font-weight:700;color:#111827;text-decoration:none">
                                    ${emp.user_name}
                                </a>
                            </td>
                            <td style="padding:12px 14px;text-align:right">
                                ${badgeHtml}
                            </td>
                        </tr>
                    `;
                });
            }
            document.getElementById('statModal').classList.add('open');
        });
}

function closeStatModal() {
    document.getElementById('statModal').classList.remove('open');
}

function filterStatModal() {
    var filter = document.getElementById('statSearch').value.toLowerCase();
    var rows = document.querySelectorAll('#statModalTable tr');
    rows.forEach(r => {
        var text = r.innerText.toLowerCase();
        r.style.display = text.includes(filter) ? '' : 'none';
    });
}
</script>
@endsection
