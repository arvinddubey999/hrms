@extends('layouts.app')
@section('content')
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
    <h1><i class="fa-solid fa-gears" style="color:var(--accent)"></i> Settings & Masters</h1>
</div>

@if(session('ok'))
    <div style="background:#ecfdf5;color:#065f46;padding:12px 16px;border-radius:8px;margin-bottom:16px;border:1px solid #a7f3d0">
        <i class="fa-solid fa-circle-check"></i> {{ session('ok') }}
    </div>
@endif

<div class="card" style="display:flex;align-items:center;gap:18px;margin-bottom:18px">
    @if($setting->company_logo)
        <img src="{{ asset('storage/'.$setting->company_logo) }}" alt="Logo" style="height:54px;object-fit:contain;border-radius:8px;border:1px solid #eee">
    @else
        <div style="width:54px;height:54px;background:var(--accent);color:#fff;display:grid;place-items:center;border-radius:8px;font-size:24px;font-weight:700">
            {{ strtoupper(substr($setting->company_name,0,1)) }}
        </div>
    @endif
    <div>
        <h2 style="margin:0">{{ $setting->company_name }}</h2>
        <div class="muted">{{ $setting->company_address ?: 'Address not set' }} | Geofence Radius: {{ $setting->geofence_radius_m }}m</div>
    </div>
</div>

<style>
.accordion-item {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    margin-bottom: 14px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    overflow: hidden;
}
.accordion-header {
    padding: 16px 20px;
    background: #fff;
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-weight: 700;
    font-size: 15px;
    color: #1e293b;
    user-select: none;
    transition: background 0.2s;
}
.accordion-header:hover {
    background: #f8fafc;
}
.accordion-body {
    padding: 20px;
    border-top: 1px solid #f1f5f9;
    display: none;
}
.accordion-item.active .accordion-body {
    display: block;
}
.accordion-item.active .accordion-header {
    background: #f8fafc;
}
.accordion-header i.chevron {
    transition: transform 0.3s;
}
.accordion-item.active .accordion-header i.chevron {
    transform: rotate(180deg);
}

/* Slide-over Drawer Styling matching Screenshot 3 */
.drawer-overlay {
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(0,0,0,0.5);
    z-index: 9999;
    display: none;
    justify-content: flex-end;
}
.drawer-content {
    background: #fff;
    width: 480px;
    max-width: 100%;
    height: 100%;
    overflow-y: auto;
    padding: 24px;
    box-shadow: -4px 0 20px rgba(0,0,0,0.15);
    display: flex;
    flex-direction: column;
}
.permission-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 12px;
    margin-top: 10px;
}
.permission-table th, .permission-table td {
    padding: 8px 10px;
    border-bottom: 1px solid #e2e8f0;
    text-align: center;
}
.permission-table th:first-child, .permission-table td:first-child {
    text-align: left;
    font-weight: 600;
}
</style>

<!-- 1. COMPANY DETAILS (ACCORDION) -->
<div class="accordion-item active">
    <div class="accordion-header" onclick="toggleAccordion(this)">
        <span><i class="fa-solid fa-building" style="color:var(--accent);margin-right:8px"></i> Company Details & Company Master</span>
        <i class="fa-solid fa-chevron-down chevron"></i>
    </div>
    <div class="accordion-body">
        <form method="post" action="{{ route('settings.companies.store') }}" enctype="multipart/form-data" style="margin-bottom:18px;background:#f8fafc;padding:16px;border-radius:12px;border:1px solid #e2e8f0">
            @csrf
            <strong style="display:block;margin-bottom:10px;color:var(--accent)">+ Add New Company</strong>
            
            <div class="grid-3" style="margin-bottom:10px">
                <div>
                    <label>Company Name *</label>
                    <input name="name" placeholder="e.g. Rakhecha Impex" required>
                </div>
                <div>
                    <label>Company Location / Address</label>
                    <input name="location" placeholder="e.g. Ring Road, Surat">
                </div>
                <div>
                    <label>Employee Code Prefix</label>
                    <input name="code_prefix" placeholder="e.g. RI (Generates RI00001)">
                </div>
            </div>

            <div class="grid-4" style="margin-bottom:10px">
                <div>
                    <label>Latitude (optional)</label>
                    <input name="latitude" placeholder="21.1702">
                </div>
                <div>
                    <label>Longitude (optional)</label>
                    <input name="longitude" placeholder="72.8311">
                </div>
                <div>
                    <label>Salary Calculation Rule</label>
                    <select name="salary_calculation_days">
                        <option value="30">Fixed 30 Days (30000 / 30 * Present Days)</option>
                        <option value="31">Fixed 31 Days (30000 / 31 * Present Days)</option>
                        <option value="actual">Actual Days in Month (28, 30 or 31)</option>
                    </select>
                </div>
                <div>
                    <label>Company Logo</label>
                    <input type="file" name="logo" accept="image/*">
                </div>
            </div>

            <div class="row" style="gap:16px;align-items:center;margin-top:12px;background:#fff;padding:10px;border-radius:8px;border:1px solid #cbd5e1">
                <label style="font-weight:600;margin:0;display:flex;align-items:center;gap:6px">
                    <input type="checkbox" name="pt_enabled" value="1" checked> Enable Professional Tax (PT) Calculation
                </label>
                <div style="display:flex;align-items:center;gap:6px">
                    <span style="font-size:12px">If Gross > ₹</span>
                    <input type="number" name="pt_threshold" value="12000" style="width:100px;padding:4px 8px">
                </div>
                <div style="display:flex;align-items:center;gap:6px">
                    <span style="font-size:12px">Deduct PT ₹</span>
                    <input type="number" name="pt_amount" value="200" style="width:90px;padding:4px 8px">
                </div>
            </div>

            <button class="btn" style="margin-top:14px"><i class="fa-solid fa-plus"></i> Add Company Master</button>
        </form>

        <div style="overflow-x:auto">
            <table class="table">
                <thead>
                    <tr>
                        <th>Logo</th>
                        <th>Company & Code Prefix</th>
                        <th>Location & Lat/Lng</th>
                        <th>Salary Calc Rule</th>
                        <th>Professional Tax</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($companies as $comp)
                        <tr>
                            <td style="width:50px">
                                @if($comp->logo)
                                    <img src="{{ asset('storage/'.$comp->logo) }}" style="width:38px;height:38px;object-fit:contain;border-radius:6px">
                                @else
                                    <div style="width:38px;height:38px;background:#e2e8f0;border-radius:6px;display:grid;place-items:center;font-weight:bold;color:#475569">
                                        {{ strtoupper(substr($comp->name, 0, 2)) }}
                                    </div>
                                @endif
                            </td>
                            <td>
                                <b>{{ $comp->name }}</b>
                                @if($comp->code_prefix)
                                    <br><span class="chip" style="background:#e0f2fe;color:#0369a1">Prefix: {{ $comp->code_prefix }}</span>
                                @endif
                            </td>
                            <td>
                                <div><i class="fa-solid fa-location-dot" style="color:#ef4444"></i> {{ $comp->location ?: 'No address specified' }}</div>
                                <small class="muted">Lat/Lng: {{ $comp->latitude ? round($comp->latitude,4).', '.round($comp->longitude,4) : 'Default' }}</small>
                            </td>
                            <td>
                                <span class="chip" style="background:#fef3c7;color:#92400e">
                                    {{ $comp->salary_calculation_days ?? '30' }} Days Month Basis
                                </span>
                            </td>
                            <td>
                                @if($comp->pt_enabled)
                                    <span class="chip" style="background:#dcfce7;color:#166534">
                                        Gross > ₹{{ number_format($comp->pt_threshold ?? 12000) }} => ₹{{ number_format($comp->pt_amount ?? 200) }}/mo PT
                                    </span>
                                @else
                                    <span class="chip" style="background:#f1f5f9;color:#64748b">Disabled</span>
                                @endif
                            </td>
                            <td>
                                <button type="button" class="btn light" style="font-size:11px;padding:4px 8px;margin-right:4px" onclick="openEditCompanyModal({{ json_encode($comp) }})">
                                    <i class="fa-solid fa-pen-to-square"></i> Edit
                                </button>
                                <form method="post" action="{{ route('settings.companies.destroy', $comp) }}" style="display:inline" onsubmit="return confirm('Delete {{ $comp->name }}?')">
                                    @csrf @method('delete')
                                    <button class="btn light" style="font-size:11px;padding:4px 8px;color:#dc2626"><i class="fa-solid fa-trash"></i> Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="muted" style="text-align:center">No companies added yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- 2. COMPANY SHIFTS (ACCORDION) -->
<div class="accordion-item">
    <div class="accordion-header" onclick="toggleAccordion(this)">
        <span><i class="fa-solid fa-clock" style="color:var(--accent);margin-right:8px"></i> Company Shifts</span>
        <i class="fa-solid fa-chevron-down chevron"></i>
    </div>
    <div class="accordion-body">
        <form class="row" method="post" action="{{ route('settings.shifts') }}" style="margin-bottom:16px;gap:8px">
            @csrf
            <input name="name" placeholder="Shift Name (e.g. Morning Shift)" required style="width:200px">
            <label style="margin:0 4px">Start:</label>
            <input type="time" name="start_time" value="09:30" required style="width:130px">
            <label style="margin:0 4px">End:</label>
            <input type="time" name="end_time" value="18:30" required style="width:130px">
            <button class="btn"><i class="fa-solid fa-plus"></i> Add Shift</button>
        </form>

        <table class="table">
            <thead>
                <tr><th>Shift Name</th><th>Start Time</th><th>End Time</th><th>Actions</th></tr>
            </thead>
            <tbody>
                @foreach($shifts as $s)
                    <tr>
                        <td colspan="4" style="padding:8px">
                            <form method="post" action="{{ route('settings.shifts.update', $s) }}" class="row" style="gap:10px;align-items:center;margin:0">
                                @csrf @method('put')
                                <input name="name" value="{{ $s->name }}" required style="width:220px;font-weight:600">
                                <label style="margin:0">Start:</label>
                                <input type="time" name="start_time" value="{{ substr($s->start_time,0,5) }}" required style="width:130px">
                                <label style="margin:0">End:</label>
                                <input type="time" name="end_time" value="{{ substr($s->end_time,0,5) }}" required style="width:130px">
                                <button class="btn light" style="font-size:11px;padding:5px 10px"><i class="fa-solid fa-floppy-disk"></i> Update Shift</button>
                                <a href="#" onclick="if(confirm('Delete shift?')){ event.preventDefault(); document.getElementById('del-shift-{{ $s->id }}').submit(); }" style="color:#dc2626;font-size:12px;margin-left:8px"><i class="fa-solid fa-trash"></i> Delete</a>
                            </form>
                            <form id="del-shift-{{ $s->id }}" method="post" action="{{ route('settings.shifts.destroy', $s) }}" style="display:none">
                                @csrf @method('delete')
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<!-- 3. ROLE & PERMISSION (ACCORDION - MATCHING SCREENSHOT REF) -->
<div class="accordion-item active">
    <div class="accordion-header" onclick="toggleAccordion(this)">
        <span><i class="fa-solid fa-user-shield" style="color:var(--accent);margin-right:8px"></i> Role & Permission</span>
        <i class="fa-solid fa-chevron-down chevron"></i>
    </div>
    <div class="accordion-body">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px">
            <div>
                <h4 style="margin:0;font-size:15px;color:#0f172a"><i class="fa-solid fa-users-gear" style="color:var(--accent)"></i> Role Management</h4>
                <p class="muted" style="margin:2px 0 0 0;font-size:12px">Configure roles (Admin, Manager, HR, Supervisor, Accountant, etc.) and assign default module-wise permissions.</p>
            </div>
            <button type="button" class="btn" onclick="openNewRoleDrawer()">
                <i class="fa-solid fa-plus"></i> Add New Role
            </button>
        </div>

        <div style="overflow-x:auto;margin-bottom:20px">
            <table class="table" style="font-size:13px">
                <thead>
                    <tr style="background:#f8fafc">
                        <th>ID</th>
                        <th>Role Name</th>
                        <th>Description</th>
                        <th>Permissions Configured</th>
                        <th style="text-align:right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($roles as $rl)
                        @php $permCount = is_array($rl->permissions) ? count($rl->permissions) : 0; @endphp
                        <tr>
                            <td><b>#{{ $rl->id }}</b></td>
                            <td>
                                <span class="chip" style="background:#e0f2fe;color:#0369a1;font-weight:700">{{ $rl->name }}</span>
                            </td>
                            <td class="muted">{{ $rl->description ?: 'No description' }}</td>
                            <td>
                                <span class="chip" style="background:#dcfce7;color:#166534">
                                    <i class="fa-solid fa-shield-halved"></i> {{ $permCount }} permissions assigned
                                </span>
                            </td>
                            <td style="text-align:right">
                                <button type="button" class="btn light" style="font-size:11px;padding:4px 8px;margin-right:4px" onclick="openEditRoleDrawer({{ json_encode($rl) }})">
                                    <i class="fa-solid fa-pen-to-square"></i> Edit Role & Permissions
                                </button>
                                @if(!in_array(strtolower($rl->name), ['admin', 'employee']))
                                    <form method="post" action="{{ route('settings.roles.destroy', $rl) }}" style="display:inline" onsubmit="return confirm('Delete role {{ $rl->name }}?')">
                                        @csrf @method('delete')
                                        <button class="btn light" style="font-size:11px;padding:4px 8px;color:#dc2626"><i class="fa-solid fa-trash"></i> Delete</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="muted" style="text-align:center">No system roles created yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <h4 style="margin:20px 0 10px 0;font-size:14px;color:#0f172a"><i class="fa-solid fa-list-check" style="color:var(--accent)"></i> Permission Management & Keys Overview</h4>
        <div style="overflow-x:auto">
            <table class="table" style="font-size:12px">
                <thead>
                    <tr style="background:#f1f5f9">
                        <th>Module</th>
                        <th>Permission Name</th>
                        <th>Permission Key</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $systemPermList = [
                            ['module' => 'Dashboard', 'name' => 'View Dashboard', 'key' => 'dashboard.view', 'desc' => 'View main analytics & summary dashboard'],
                            ['module' => 'Dashboard', 'name' => 'View Reports', 'key' => 'dashboard.reports', 'desc' => 'Generate and view attendance reports'],
                            ['module' => 'Dashboard', 'name' => 'Download Reports', 'key' => 'dashboard.download', 'desc' => 'Download Excel/PDF attendance reports'],
                            ['module' => 'Employee', 'name' => 'View Employees', 'key' => 'employee.view', 'desc' => 'Access employee directory & profiles'],
                            ['module' => 'Employee', 'name' => 'Add Employee', 'key' => 'employee.add', 'desc' => 'Create new employee profiles'],
                            ['module' => 'Employee', 'name' => 'Edit Employee', 'key' => 'employee.edit', 'desc' => 'Update staff details & assign roles'],
                            ['module' => 'Employee', 'name' => 'Delete Employee', 'key' => 'employee.delete', 'desc' => 'Archive or delete employee records'],
                            ['module' => 'Attendance', 'name' => 'View Attendance', 'key' => 'attendance.view', 'desc' => 'View daily attendance logs & punches'],
                            ['module' => 'Attendance', 'name' => 'Mark Attendance', 'key' => 'attendance.mark', 'desc' => 'Record single employee attendance punch'],
                            ['module' => 'Attendance', 'name' => 'Bulk Mark Attendance', 'key' => 'attendance.bulk', 'desc' => 'Mark bulk attendance for multiple staff'],
                            ['module' => 'Leave', 'name' => 'View Leave Requests', 'key' => 'leave.view', 'desc' => 'Access leave application records'],
                            ['module' => 'Leave', 'name' => 'Apply Leave', 'key' => 'leave.apply', 'desc' => 'Submit leave requests'],
                            ['module' => 'Leave', 'name' => 'Approve Leave', 'key' => 'leave.approve', 'desc' => 'Approve or reject employee leave requests'],
                            ['module' => 'Payroll', 'name' => 'View Salary & Payslips', 'key' => 'payroll.view', 'desc' => 'View salary calculations & payslips'],
                            ['module' => 'Payroll', 'name' => 'Process Payroll', 'key' => 'payroll.process', 'desc' => 'Process monthly salary calculations'],
                            ['module' => 'Tasks', 'name' => 'Manage Tasks', 'key' => 'tasks.manage', 'desc' => 'Create, assign & manage work tasks'],
                            ['module' => 'Settings', 'name' => 'Manage Roles & Permissions', 'key' => 'settings.roles', 'desc' => 'Configure roles & permissions in settings'],
                        ];
                    @endphp
                    @foreach($systemPermList as $sp)
                        <tr>
                            <td><b>{{ $sp['module'] }}</b></td>
                            <td>{{ $sp['name'] }}</td>
                            <td><code style="background:#f1f5f9;padding:2px 6px;border-radius:4px;color:#0284c7">{{ $sp['key'] }}</code></td>
                            <td class="muted">{{ $sp['desc'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- 4. CATEGORY MASTER (ACCORDION) -->
<div class="accordion-item">
    <div class="accordion-header" onclick="toggleAccordion(this)">
        <span><i class="fa-solid fa-layer-group" style="color:var(--accent);margin-right:8px"></i> Category Master</span>
        <i class="fa-solid fa-chevron-down chevron"></i>
    </div>
    <div class="accordion-body">
        <form class="row" method="post" action="{{ route('settings.categories') }}" style="margin-bottom:16px;gap:8px">
            @csrf
            <input name="name" placeholder="Category Name (e.g. Staff, Worker, Management)" required style="max-width:360px">
            <button class="btn"><i class="fa-solid fa-plus"></i> Add Category</button>
        </form>

        <table class="table">
            <thead>
                <tr><th>Category Name</th><th>Actions</th></tr>
            </thead>
            <tbody>
                @forelse($categories as $cat)
                    <tr>
                        <td><b>{{ $cat->name }}</b></td>
                        <td>
                            <button type="button" class="btn light" style="font-size:11px;padding:4px 8px;margin-right:4px" onclick="openEditCategoryModal({{ json_encode($cat) }})">
                                <i class="fa-solid fa-pen-to-square"></i> Edit
                            </button>
                            <form method="post" action="{{ route('settings.categories.destroy', $cat) }}" style="display:inline" onsubmit="return confirm('Delete {{ $cat->name }}?')">
                                @csrf @method('delete')
                                <button class="btn light" style="font-size:11px;padding:4px 8px;color:#dc2626"><i class="fa-solid fa-trash"></i> Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="2" class="muted" style="text-align:center">No categories configured yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- 5. DEPARTMENT MASTER (ACCORDION) -->
<div class="accordion-item">
    <div class="accordion-header" onclick="toggleAccordion(this)">
        <span><i class="fa-solid fa-sitemap" style="color:var(--accent);margin-right:8px"></i> Department Master</span>
        <i class="fa-solid fa-chevron-down chevron"></i>
    </div>
    <div class="accordion-body">
        <form class="row" method="post" action="{{ route('settings.departments.store') }}" style="margin-bottom:16px;gap:8px">
            @csrf
            <input name="name" placeholder="Department Name (e.g. Accounts, Sales, IT, Production)" required style="max-width:360px">
            <button class="btn"><i class="fa-solid fa-plus"></i> Add Department</button>
        </form>

        <table class="table">
            <thead>
                <tr><th>Department Name</th><th>Actions</th></tr>
            </thead>
            <tbody>
                @forelse($departments as $dept)
                    <tr>
                        <td><b>{{ $dept->name }}</b></td>
                        <td>
                            <button type="button" class="btn light" style="font-size:11px;padding:4px 8px;margin-right:4px" onclick="openEditDeptModal({{ json_encode($dept) }})">
                                <i class="fa-solid fa-pen-to-square"></i> Edit
                            </button>
                            <form method="post" action="{{ route('settings.departments.destroy', $dept) }}" style="display:inline" onsubmit="return confirm('Delete {{ $dept->name }}?')">
                                @csrf @method('delete')
                                <button class="btn light" style="font-size:11px;padding:4px 8px;color:#dc2626"><i class="fa-solid fa-trash"></i> Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="2" class="muted" style="text-align:center">No departments added yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- 6. HOLIDAY LISTING (ACCORDION) -->
<div class="accordion-item">
    <div class="accordion-header" onclick="toggleAccordion(this)">
        <span><i class="fa-solid fa-umbrella-beach" style="color:var(--accent);margin-right:8px"></i> Holiday Listing & Applicable Rules</span>
        <i class="fa-solid fa-chevron-down chevron"></i>
    </div>
    <div class="accordion-body">
        <form method="post" action="{{ route('settings.holidays.store') }}" style="margin-bottom:18px;background:#f8fafc;padding:16px;border-radius:12px;border:1px solid #e2e8f0">
            @csrf
            <strong style="display:block;margin-bottom:10px;color:var(--accent)">+ Add New Holiday / Leave</strong>
            <div class="grid-3" style="margin-bottom:12px">
                <div><label>Holiday Name *</label><input name="name" placeholder="e.g. Mahavir Jayanti / Diwali" required></div>
                <div><label>Date *</label><input type="date" name="date" required></div>
                <div><label>Description</label><input name="description" placeholder="Public or Festival Holiday"></div>
            </div>

            <div class="grid-2" style="margin-bottom:12px;background:#fff;padding:12px;border-radius:8px;border:1px solid #cbd5e1">
                <div>
                    <label style="font-weight:600;margin-bottom:6px">Select Applicable Companies (Blank = All):</label>
                    <div style="max-height:120px;overflow-y:auto">
                        @forelse($companies as $comp)
                            <label style="font-weight:normal;display:block;margin-bottom:4px">
                                <input type="checkbox" name="company_ids[]" value="{{ $comp->id }}" checked> {{ $comp->name }}
                            </label>
                        @empty
                            <span class="muted">No companies in master.</span>
                        @endforelse
                    </div>
                </div>
                <div>
                    <label style="font-weight:600;margin-bottom:6px">Select Applicable Departments (Blank = All):</label>
                    <div style="max-height:120px;overflow-y:auto">
                        @forelse($departments as $dept)
                            <label style="font-weight:normal;display:block;margin-bottom:4px">
                                <input type="checkbox" name="department_ids[]" value="{{ $dept->id }}" checked> {{ $dept->name }}
                            </label>
                        @empty
                            <span class="muted">No departments in master.</span>
                        @endforelse
                    </div>
                </div>
            </div>

            <button class="btn"><i class="fa-solid fa-floppy-disk"></i> Save Holiday</button>
        </form>

        <table class="table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Holiday Name</th>
                    <th>Applicable Companies</th>
                    <th>Applicable Departments</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($holidays as $hol)
                    <tr>
                        <td><b>{{ $hol->date->format('d M Y') }}</b></td>
                        <td>{{ $hol->name }}</td>
                        <td>
                            @if(empty($hol->company_ids))
                                <span class="chip">All Companies</span>
                            @else
                                @foreach($hol->company_ids as $cId)
                                    <span class="chip" style="background:#e0f2fe;color:#0369a1">{{ \App\Models\Company::find($cId)?->name ?? 'Company #'.$cId }}</span>
                                @endforeach
                            @endif
                        </td>
                        <td>
                            @if(empty($hol->department_ids))
                                <span class="chip">All Departments</span>
                            @else
                                @foreach($hol->department_ids as $dId)
                                    <span class="chip" style="background:#fef3c7;color:#92400e">{{ \App\Models\Department::find($dId)?->name ?? 'Dept #'.$dId }}</span>
                                @endforeach
                            @endif
                        </td>
                        <td>
                            <form method="post" action="{{ route('settings.holidays.destroy', $hol) }}" style="display:inline" onsubmit="return confirm('Delete holiday?')">
                                @csrf @method('delete')
                                <button class="btn light" style="font-size:11px;padding:3px 6px;color:#dc2626"><i class="fa-solid fa-trash"></i> Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="muted" style="text-align:center">No holidays configured yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- 7. SYSTEM SETTINGS (ACCORDION) -->
<div class="accordion-item">
    <div class="accordion-header" onclick="toggleAccordion(this)">
        <span><i class="fa-solid fa-sliders" style="color:var(--accent);margin-right:8px"></i> System Settings</span>
        <i class="fa-solid fa-chevron-down chevron"></i>
    </div>
    <div class="accordion-body">
        <form method="post" action="{{ route('settings.update') }}" enctype="multipart/form-data">
            @csrf
            <div class="grid-2">
                <div>
                    <label>Company Main Logo</label>
                    <input type="file" name="company_logo" accept="image/*">
                </div>
                <div>
                    <label>Company Main Name</label>
                    <input name="company_name" value="{{ $setting->company_name }}" required>
                </div>
            </div>

            <label style="margin-top:10px">Company Main Address</label>
            <input name="company_address" value="{{ $setting->company_address }}">

            <div class="grid-3" style="margin-top:10px">
                <div>
                    <label>DEFAULT LATITUDE *</label>
                    <input name="office_lat" value="{{ $setting->office_lat }}" required>
                </div>
                <div>
                    <label>DEFAULT LONGITUDE *</label>
                    <input name="office_lng" value="{{ $setting->office_lng }}" required>
                </div>
                <div>
                    <label>GEOFENCE RADIUS (Meters)</label>
                    <input name="geofence_radius_m" value="{{ $setting->geofence_radius_m }}" required>
                </div>
            </div>

            <div style="display:flex;gap:12px;margin-top:14px;align-items:center">
                <button class="btn"><i class="fa-solid fa-floppy-disk"></i> Save System Settings</button>
                <button type="button" class="btn light" onclick="openGeofenceModal()" style="border:1px solid #cbd5e1;background:#fff">
                    <i class="fa-solid fa-map-location-dot" style="color:var(--accent)"></i> Geo Fencing Locations
                </button>
            </div>
        </form>
    </div>
</div>

<!-- GEOFENCING LOCATIONS MODAL (MATCHING SCREENSHOTS 1 & 4) -->
<div id="geofenceModal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.6);z-index:9999;align-items:center;justify-content:center;padding:16px">
    <div style="background:#fff;width:1050px;max-width:98%;max-height:92vh;border-radius:16px;padding:24px;overflow-y:auto;box-shadow:0 25px 50px -12px rgba(0,0,0,0.25)">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;border-bottom:1px solid #e2e8f0;padding-bottom:12px">
            <div>
                <h3 style="margin:0;font-size:18px;font-weight:700"><i class="fa-solid fa-location-dot" style="color:#ef4444"></i> Manage Geo-fences</h3>
                <p class="muted" style="margin:2px 0 0 0;font-size:12px">Select a geofence to highlight it on the map. You can also add, edit or delete existing fences.</p>
            </div>
            <button type="button" onclick="closeGeofenceModal()" style="border:0;background:none;font-size:22px;cursor:pointer">&times;</button>
        </div>

        <!-- ADD GEOFENCE FORM (SCREENSHOT 4) -->
        <form method="post" action="{{ route('settings.geofences.store') }}" style="background:#f8fafc;padding:18px;border-radius:14px;border:1px solid #e2e8f0;margin-bottom:20px">
            @csrf
            <h4 style="margin:0 0 12px 0;font-size:14px;color:#1e293b"><i class="fa-solid fa-circle-plus" style="color:var(--accent)"></i> Configure New Geofence</h4>
            
            <div class="grid-3" style="gap:14px">
                <div>
                    <label>Select Company *</label>
                    <select name="company_id" required>
                        @foreach($companies as $comp)
                            <option value="{{ $comp->id }}">{{ $comp->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label>Geofence Name / Title *</label>
                    <input name="name" placeholder="e.g. Kohinoor Textile Market, Branch 1" required>
                </div>
                <div>
                    <label>Category Tag</label>
                    <input name="category" placeholder="e.g. Factory, Showroom, Office">
                </div>
            </div>

            <div class="grid-3" style="gap:14px;margin-top:10px">
                <div>
                    <label>Full Address / Location Search *</label>
                    <input name="address" placeholder="e.g. 3WJ6+Q68, Hojiwala Industrial Estate, Sachin, Surat" required>
                </div>
                <div>
                    <label>Latitude *</label>
                    <input name="latitude" placeholder="21.1882" required>
                </div>
                <div>
                    <label>Longitude *</label>
                    <input name="longitude" placeholder="72.8377" required>
                </div>
            </div>

            <div class="grid-2" style="gap:14px;margin-top:10px">
                <div>
                    <label>Geofence Radius (meters) *</label>
                    <input type="number" name="radius" value="100" min="10" required>
                </div>
                <div>
                    <label>Status</label>
                    <select name="status">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>

            <div style="margin-top:14px;display:flex;justify-content:flex-end">
                <button class="btn"><i class="fa-solid fa-check"></i> Confirm & Save Geofence Location</button>
            </div>
        </form>

        <!-- EXISTING GEOFENCES TABLE (SCREENSHOT 1) -->
        <div style="overflow-x:auto">
            <table class="table" style="font-size:13px">
                <thead>
                    <tr style="background:#f1f5f9">
                        <th>Address / Name</th>
                        <th>Latitude</th>
                        <th>Longitude</th>
                        <th>Radius (m)</th>
                        <th>Category</th>
                        <th>Company</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($geofences as $geo)
                        <tr>
                            <td>
                                <b>{{ $geo->name }}</b>
                                @if($geo->address)<div style="font-size:11px;color:#6b7280">{{ $geo->address }}</div>@endif
                            </td>
                            <td>{{ round($geo->latitude, 4) }}</td>
                            <td>{{ round($geo->longitude, 4) }}</td>
                            <td><span class="chip" style="background:#e0f2fe;color:#0369a1">{{ $geo->radius }} m</span></td>
                            <td>{{ $geo->category ?: '-' }}</td>
                            <td>{{ $geo->company->name ?? 'All' }}</td>
                            <td>
                                <span class="chip" style="background:{{ $geo->status==='active'?'#dcfce7':'#fee2e2' }};color:{{ $geo->status==='active'?'#166534':'#991b1b' }}">
                                    {{ ucfirst($geo->status) }}
                                </span>
                            </td>
                            <td>
                                <form method="post" action="{{ route('settings.geofences.destroy', $geo) }}" style="display:inline" onsubmit="return confirm('Delete geofence?')">
                                    @csrf @method('delete')
                                    <button class="btn light" style="font-size:11px;padding:4px 8px;color:#dc2626"><i class="fa-solid fa-trash"></i> Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="muted" style="text-align:center">No geo-fences configured yet. Add a new geofence above.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function openGeofenceModal() {
    document.getElementById('geofenceModal').style.display = 'flex';
}
function closeGeofenceModal() {
    document.getElementById('geofenceModal').style.display = 'none';
}
</script>


<!-- SLIDE-OVER DRAWER MODAL FOR EDIT ROLE & MODULE PERMISSIONS (SCREENSHOT REF) -->
<div id="roleDrawer" class="drawer-overlay">
    <div class="drawer-content">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;border-bottom:1px solid #e2e8f0;padding-bottom:12px">
            <h3 style="margin:0;font-size:18px;font-weight:700"><i class="fa-solid fa-user-shield" style="color:var(--accent)"></i> <span id="roleDrawerTitle">Edit Role & Permissions</span></h3>
            <button type="button" onclick="closeRoleDrawer()" style="border:0;background:none;font-size:20px;cursor:pointer">&times;</button>
        </div>

        <form id="roleForm" method="post" style="flex:1;display:flex;flex-direction:column">
            @csrf
            <input type="hidden" id="role_method" name="_method" value="post">
            
            <div style="margin-bottom:12px">
                <label style="font-weight:600;margin-bottom:6px">Role Name *</label>
                <input id="role_name" name="name" placeholder="e.g. Manager, HR, Accountant, Supervisor" required>
            </div>

            <div style="margin-bottom:16px">
                <label style="font-weight:600;margin-bottom:6px">Role Description</label>
                <input id="role_description" name="description" placeholder="Short summary of role responsibilities...">
            </div>

            <label style="font-weight:700;margin-bottom:8px;display:block;color:#1e293b"><i class="fa-solid fa-shield-halved" style="color:var(--accent)"></i> Select Module-Wise Access Permissions</label>
            <p class="muted" style="margin-top:0;font-size:11px">Check permissions that users assigned to this role will inherit automatically.</p>

            @php
                $permissionModules = [
                    'Dashboard' => [
                        'dashboard.view' => 'View Dashboard',
                        'dashboard.reports' => 'View Reports',
                        'dashboard.download' => 'Download Reports',
                    ],
                    'Employee Management' => [
                        'employee.view' => 'View Employees',
                        'employee.add' => 'Add Employee',
                        'employee.edit' => 'Edit Employee',
                        'employee.delete' => 'Delete Employee',
                    ],
                    'Attendance & Punches' => [
                        'attendance.view' => 'View Attendance Log',
                        'attendance.mark' => 'Mark Single Attendance',
                        'attendance.bulk' => 'Bulk Mark Attendance',
                        'attendance.edit' => 'Edit Punches & Shifts',
                        'roster.view' => 'Monthly Roster',
                    ],
                    'Leave Management' => [
                        'leave.view' => 'View Leave Requests',
                        'leave.apply' => 'Apply Leave',
                        'leave.approve' => 'Approve / Reject Leave',
                    ],
                    'Payroll & Salary' => [
                        'payroll.view' => 'View Salary & Payslips',
                        'payroll.process' => 'Process Payroll',
                        'payroll.advances' => 'Manage Advances & Loans',
                        'payroll.expenses' => 'Manage Expense Claims',
                    ],
                    'Tasks' => [
                        'tasks.view' => 'View Tasks',
                        'tasks.create' => 'Create & Assign Tasks',
                        'tasks.manage' => 'Manage & Reassign Tasks',
                    ],
                    'Live Tracking & Geo' => [
                        'tracking.view' => 'Live Location Tracking',
                        'geofence.view' => 'View Geofences',
                    ],
                    'Settings & System' => [
                        'settings.view' => 'View Settings',
                        'settings.roles' => 'Manage Roles & Permissions',
                        'settings.manage' => 'Manage System Settings',
                    ],
                ];
            @endphp

            <div style="flex:1;overflow-y:auto;padding-right:4px;margin-bottom:16px">
                @foreach($permissionModules as $modGroup => $permMap)
                    <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:12px;margin-bottom:12px">
                        <strong style="display:block;margin-bottom:8px;color:#1e293b;font-size:13px"><i class="fa-solid fa-folder" style="color:var(--accent)"></i> {{ $modGroup }}</strong>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
                            @foreach($permMap as $pkey => $plabel)
                                <label style="font-size:12px;font-weight:normal;display:flex;align-items:center;gap:6px;cursor:pointer">
                                    <input type="checkbox" name="permissions[]" value="{{ $pkey }}" class="role-perm-chk perm-key-{{ str_replace('.', '-', $pkey) }}">
                                    {{ $plabel }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>

            <div style="margin-top:auto;padding-top:16px;border-top:1px solid #e2e8f0;display:flex;justify-content:flex-end;gap:10px">
                <button type="button" class="btn light" onclick="closeRoleDrawer()">Cancel</button>
                <button class="btn"><i class="fa-solid fa-floppy-disk"></i> Save Role & Permissions</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal for Editing Category -->
<div id="editCategoryModal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.5);z-index:9999;align-items:center;justify-content:center">
    <div style="background:#fff;width:400px;max-width:95%;border-radius:12px;padding:24px">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
            <h3 style="margin:0"><i class="fa-solid fa-layer-group"></i> Edit Category Master</h3>
            <button type="button" onclick="closeEditCategoryModal()" style="border:0;background:none;font-size:18px;cursor:pointer">&times;</button>
        </div>
        <form id="editCategoryForm" method="post">
            @csrf @method('put')
            <label style="margin-bottom:6px;display:block">Category Name *</label>
            <input id="edit_cat_name" name="name" required style="margin-bottom:16px">
            <div style="display:flex;justify-content:flex-end;gap:10px">
                <button type="button" class="btn light" onclick="closeEditCategoryModal()">Cancel</button>
                <button class="btn"><i class="fa-solid fa-floppy-disk"></i> Save Category</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal for Editing Company -->
<div id="editCompanyModal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.5);z-index:9999;align-items:center;justify-content:center">
    <div style="background:#fff;width:600px;max-width:95%;border-radius:12px;padding:24px;box-shadow:0 20px 25px -5px rgba(0,0,0,0.1)">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
            <h3 style="margin:0"><i class="fa-solid fa-building"></i> Edit Company Master</h3>
            <button type="button" onclick="closeEditCompanyModal()" style="border:0;background:none;font-size:18px;cursor:pointer">&times;</button>
        </div>
        <form id="editCompanyForm" method="post" enctype="multipart/form-data">
            @csrf @method('put')
            <div class="grid-2" style="margin-bottom:10px">
                <div>
                    <label>Company Name *</label>
                    <input id="edit_comp_name" name="name" required>
                </div>
                <div>
                    <label>Employee Code Prefix</label>
                    <input id="edit_comp_prefix" name="code_prefix" placeholder="e.g. RI">
                </div>
            </div>
            <div class="grid-3" style="margin-bottom:10px">
                <div>
                    <label>Location / Address</label>
                    <input id="edit_comp_location" name="location">
                </div>
                <div>
                    <label>Latitude</label>
                    <input id="edit_comp_lat" name="latitude">
                </div>
                <div>
                    <label>Longitude</label>
                    <input id="edit_comp_lng" name="longitude">
                </div>
            </div>
            <div class="grid-2" style="margin-bottom:10px">
                <div>
                    <label>Salary Calculation Rule</label>
                    <select id="edit_comp_salary_days" name="salary_calculation_days">
                        <option value="30">Fixed 30 Days (30000 / 30 * Present)</option>
                        <option value="31">Fixed 31 Days (30000 / 31 * Present)</option>
                        <option value="actual">Actual Days in Month</option>
                    </select>
                </div>
                <div>
                    <label>Change Logo</label>
                    <input type="file" name="logo" accept="image/*">
                </div>
            </div>
            <div style="background:#f8fafc;padding:12px;border-radius:8px;border:1px solid #cbd5e1;margin-bottom:16px">
                <label style="font-weight:600;display:flex;align-items:center;gap:6px;margin-bottom:8px">
                    <input type="checkbox" id="edit_comp_pt_enabled" name="pt_enabled" value="1"> Enable Professional Tax (PT)
                </label>
                <div class="grid-2">
                    <div>
                        <label style="font-size:12px">Gross Threshold (₹)</label>
                        <input id="edit_comp_pt_threshold" type="number" name="pt_threshold">
                    </div>
                    <div>
                        <label style="font-size:12px">Deduction Amount (₹)</label>
                        <input id="edit_comp_pt_amount" type="number" name="pt_amount">
                    </div>
                </div>
            </div>
            <div style="display:flex;justify-content:flex-end;gap:10px">
                <button type="button" class="btn light" onclick="closeEditCompanyModal()">Cancel</button>
                <button class="btn"><i class="fa-solid fa-floppy-disk"></i> Update Company</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal for Editing Department -->
<div id="editDeptModal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.5);z-index:9999;align-items:center;justify-content:center">
    <div style="background:#fff;width:400px;max-width:95%;border-radius:12px;padding:24px">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
            <h3 style="margin:0"><i class="fa-solid fa-sitemap"></i> Edit Department Master</h3>
            <button type="button" onclick="closeEditDeptModal()" style="border:0;background:none;font-size:18px;cursor:pointer">&times;</button>
        </div>
        <form id="editDeptForm" method="post">
            @csrf @method('put')
            <label style="margin-bottom:6px;display:block">Department Name *</label>
            <input id="edit_dept_name" name="name" required style="margin-bottom:16px">
            <div style="display:flex;justify-content:flex-end;gap:10px">
                <button type="button" class="btn light" onclick="closeEditDeptModal()">Cancel</button>
                <button class="btn"><i class="fa-solid fa-floppy-disk"></i> Save Department</button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleAccordion(header) {
    const item = header.parentElement;
    item.classList.toggle('active');
}

function openNewRoleDrawer() {
    document.getElementById('roleDrawerTitle').innerText = 'Add New Role & Permissions';
    document.getElementById('roleForm').action = "{{ route('settings.roles.store') }}";
    document.getElementById('role_method').value = 'post';
    document.getElementById('role_name').value = '';
    document.getElementById('role_description').value = '';
    document.querySelectorAll('.role-perm-chk').forEach(c => c.checked = false);
    document.getElementById('roleDrawer').style.display = 'flex';
}

function openEditRoleDrawer(rl) {
    document.getElementById('roleDrawerTitle').innerText = 'Edit Role & Permissions: ' + rl.name;
    document.getElementById('roleForm').action = "/settings/roles/" + rl.id;
    document.getElementById('role_method').value = 'put';
    document.getElementById('role_name').value = rl.name || '';
    document.getElementById('role_description').value = rl.description || '';
    
    document.querySelectorAll('.role-perm-chk').forEach(c => c.checked = false);
    if (rl.permissions && Array.isArray(rl.permissions)) {
        rl.permissions.forEach(pkey => {
            const keyClass = 'perm-key-' + pkey.replace(/\./g, '-');
            const el = document.querySelector('.' + keyClass);
            if (el) el.checked = true;
        });
    }
    document.getElementById('roleDrawer').style.display = 'flex';
}

function closeRoleDrawer() {
    document.getElementById('roleDrawer').style.display = 'none';
}

function openNewDesignationDrawer() { openNewRoleDrawer(); }
function openEditDesignationDrawer(desig) { openEditRoleDrawer(desig); }
function closeDesignationDrawer() { closeRoleDrawer(); }

function openEditCategoryModal(cat) {
    document.getElementById('editCategoryForm').action = "/settings/categories/" + cat.id;
    document.getElementById('edit_cat_name').value = cat.name || '';
    document.getElementById('editCategoryModal').style.display = 'flex';
}
function closeEditCategoryModal() {
    document.getElementById('editCategoryModal').style.display = 'none';
}

function openEditCompanyModal(comp) {
    document.getElementById('editCompanyForm').action = "/settings/companies/" + comp.id;
    document.getElementById('edit_comp_name').value = comp.name || '';
    document.getElementById('edit_comp_prefix').value = comp.code_prefix || '';
    document.getElementById('edit_comp_location').value = comp.location || '';
    document.getElementById('edit_comp_lat').value = comp.latitude || '';
    document.getElementById('edit_comp_lng').value = comp.longitude || '';
    document.getElementById('edit_comp_salary_days').value = comp.salary_calculation_days || '30';
    document.getElementById('edit_comp_pt_enabled').checked = !!comp.pt_enabled;
    document.getElementById('edit_comp_pt_threshold').value = comp.pt_threshold || 12000;
    document.getElementById('edit_comp_pt_amount').value = comp.pt_amount || 200;
    document.getElementById('editCompanyModal').style.display = 'flex';
}
function closeEditCompanyModal() {
    document.getElementById('editCompanyModal').style.display = 'none';
}
function openEditDeptModal(dept) {
    document.getElementById('editDeptForm').action = "/settings/departments/" + dept.id;
    document.getElementById('edit_dept_name').value = dept.name || '';
    document.getElementById('editDeptModal').style.display = 'flex';
}
function closeEditDeptModal() {
    document.getElementById('editDeptModal').style.display = 'none';
}
</script>
@endsection
