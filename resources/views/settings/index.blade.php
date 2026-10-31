@extends('layouts.app')
@section('content')
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
    <h1><i class="fa-solid fa-gears" style="color:var(--accent)"></i> Settings & Masters</h1>
</div>

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
        <div class="muted">{{ $setting->company_address ?: 'Address not set' }}</div>
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
<div class="accordion-item">
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
                    <label style="font-weight:600;font-size:12px;display:block;margin-bottom:4px">Salary Calculation Rule</label>
                    <select name="salary_calculation_days" style="font-weight:600">
                        <option value="actual">Actual (Current Month Days - 28/29/30/31)</option>
                        <option value="30" selected>30 Days (Fixed 30 Days)</option>
                        <option value="26">26 Days (Fixed 26 Days)</option>
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
                                    {{ $comp->salary_calculation_days === 'actual' ? 'Actual Days' : ($comp->salary_calculation_days ?? '30').' Days' }} Basis
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
<div class="accordion-item">
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

<!-- 6. HOLIDAY LISTING (ACCORDION) MATCHING SCREENSHOT 1 -->
<div class="accordion-item">
    <div class="accordion-header" onclick="toggleAccordion(this)">
        <span><i class="fa-regular fa-calendar-check" style="color:var(--accent);margin-right:8px"></i> Holiday Listing</span>
        <i class="fa-solid fa-chevron-down chevron"></i>
    </div>
    <div class="accordion-body">
        <!-- 2-Column Holiday Cards Grid matching Screenshot 1 -->
        <div style="display:grid;grid-template-columns:repeat(auto-fill, minmax(360px, 1fr));gap:14px;max-height:420px;overflow-y:auto;padding-right:4px">
            @forelse($holidays as $hol)
                <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:12px 16px;display:flex;align-items:center;justify-content:space-between;gap:12px;transition:box-shadow 0.15s ease">
                    <div style="display:flex;align-items:center;gap:12px;min-width:0">
                        <div style="color:#475569;font-size:22px;flex-shrink:0">
                            <i class="fa-regular fa-calendar"></i>
                        </div>
                        <div style="min-width:0">
                            <div style="font-weight:700;font-size:14px;color:#1e293b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                                {{ $hol->name }}
                            </div>
                            <div style="font-size:12px;color:#64748b;margin-top:2px">
                                {{ $hol->date->format('d M Y') }} ({{ $hol->date->format('l') }})
                            </div>
                        </div>
                    </div>
                    <div style="display:flex;align-items:center;gap:8px;flex-shrink:0">
                        <button type="button" class="btn light" onclick="openEditHolidayModal({{ json_encode($hol) }})" style="padding:6px 10px;border-radius:8px;border:1px solid #e2e8f0;background:#fff;cursor:pointer" title="Edit Holiday">
                            <i class="fa-solid fa-pencil" style="font-size:13px;color:#475569"></i>
                        </button>
                        <form method="post" action="{{ route('settings.holidays.destroy', $hol) }}" style="display:inline;margin:0" onsubmit="return confirm('Delete this holiday?')">
                            @csrf @method('delete')
                            <button type="submit" class="btn light" style="padding:6px 10px;border-radius:8px;border:1px solid #fecdd3;background:#fff;cursor:pointer" title="Delete Holiday">
                                <i class="fa-solid fa-trash-can" style="font-size:13px;color:#991b1b"></i>
                            </button>
                        </form>
                    </div>
                </div>
            @empty
                <div style="grid-column:1/-1;text-align:center;padding:30px;color:#94a3b8">
                    No holidays configured yet. Click <b>Add New Holiday</b> below to create one.
                </div>
            @endforelse
        </div>

        <div style="margin-top:16px">
            <button type="button" class="btn" onclick="openAddHolidayModal()" style="background:#1e293b;color:#fff;border-radius:10px;padding:9px 20px;font-size:13px;font-weight:700;box-shadow:0 2px 5px rgba(0,0,0,0.15)">
                Add New Holiday
            </button>
        </div>
    </div>
</div>

<!-- Modal: Add / Edit Holiday matching Screenshots 2 & 3 -->
<div id="holidayModal" style="display:none;position:fixed;inset:0;background:rgba(15,23,42,0.5);backdrop-filter:blur(3px);z-index:9999;align-items:center;justify-content:center;padding:16px;box-sizing:border-box">
    <div style="background:#fff;width:520px;max-width:96vw;max-height:92vh;border-radius:16px;box-shadow:0 20px 40px rgba(0,0,0,0.2);display:flex;flex-direction:column;overflow:hidden">
        <!-- Modal Header -->
        <div style="padding:16px 20px;border-bottom:1px solid #e5e7eb;display:flex;justify-content:space-between;align-items:center;flex-shrink:0">
            <h3 id="holidayModalTitle" style="margin:0;font-size:16px;font-weight:700;color:#111827">Add Holiday</h3>
            <button type="button" onclick="closeHolidayModal()" style="border:0;background:none;font-size:20px;cursor:pointer;color:#64748b;line-height:1">&times;</button>
        </div>

        <!-- Modal Form Body -->
        <form id="holidayForm" method="post" action="{{ route('settings.holidays.store') }}" style="padding:18px 20px;overflow-y:auto;flex:1;display:flex;flex-direction:column;gap:14px;margin:0">
            @csrf
            <input type="hidden" name="_method" id="holiday_form_method" value="post">
            <input type="hidden" name="date" id="holiday_date" value="{{ now()->toDateString() }}">

            <!-- 1. Holiday Name -->
            <div>
                <label style="display:block;font-size:12.5px;font-weight:600;color:#374151;margin-bottom:6px">Holiday Name</label>
                <input name="name" id="holiday_name" placeholder="Enter holiday name" required style="width:100%;border:1px solid #d1d5db;border-radius:8px;padding:8px 12px;font-size:13px;outline:none;box-sizing:border-box">
            </div>

            <!-- 2. Holiday Date (Interactive Calendar matching Screenshots 2 & 3) -->
            <div>
                <label style="display:block;font-size:12.5px;font-weight:600;color:#374151;margin-bottom:6px">Holiday Date</label>
                <div style="border:1px solid #e5e7eb;border-radius:12px;padding:12px 14px;background:#fff">
                    <!-- Calendar Header (Month Year Nav) -->
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px">
                        <button type="button" onclick="navigateHolidayCal(-1)" style="border:0;background:none;font-size:16px;color:#64748b;cursor:pointer;padding:4px 8px">&larr;</button>
                        <strong id="holidayCalTitle" style="font-size:13.5px;color:#1e293b;font-weight:700">October 2026</strong>
                        <button type="button" onclick="navigateHolidayCal(1)" style="border:0;background:none;font-size:16px;color:#64748b;cursor:pointer;padding:4px 8px">&rarr;</button>
                    </div>
                    <!-- Weekdays -->
                    <div style="display:grid;grid-template-columns:repeat(7,1fr);text-align:center;font-size:11.5px;font-weight:600;color:#64748b;margin-bottom:6px">
                        <div>Su</div><div>Mo</div><div>Tu</div><div>We</div><div>Th</div><div>Fr</div><div>Sa</div>
                    </div>
                    <!-- Days Grid -->
                    <div id="holidayDaysGrid" style="display:grid;grid-template-columns:repeat(7,1fr);gap:4px;text-align:center">
                        <!-- Populated dynamically via JS -->
                    </div>
                </div>
            </div>

            <!-- 3. Select Categories matching Screenshots 2 & 3 -->
            <div>
                <label style="display:block;font-size:12.5px;font-weight:600;color:#374151;margin-bottom:6px">Select Categories</label>
                <div style="border:1px solid #e5e7eb;border-radius:10px;padding:10px 12px;max-height:130px;overflow-y:auto;display:flex;flex-direction:column;gap:6px">
                    @forelse($categories as $cat)
                        <label style="display:flex;align-items:center;gap:8px;font-size:12.5px;color:#334155;cursor:pointer;margin:0">
                            <input type="checkbox" name="category_ids[]" value="{{ $cat->id }}" class="hol-cat-chk" style="width:15px;height:15px;margin:0;accent-color:#1e293b">
                            <span>{{ $cat->name }}</span>
                        </label>
                    @empty
                        <span class="muted" style="font-size:11.5px">No categories configured.</span>
                    @endforelse
                </div>
            </div>

            <!-- 4. Additional Fields (Applicable Companies & Departments) Preserved as requested -->
            <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:12px;display:flex;flex-direction:column;gap:10px">
                <div style="font-size:12px;font-weight:700;color:#475569"><i class="fa-solid fa-sliders"></i> Applicable Rules (Optional)</div>
                <div class="grid-2" style="gap:10px">
                    <div>
                        <label style="font-size:11.5px;font-weight:600;color:#64748b;margin-bottom:4px;display:block">Applicable Companies (Blank = All)</label>
                        <div style="max-height:85px;overflow-y:auto;background:#fff;border:1px solid #cbd5e1;border-radius:6px;padding:6px 8px;display:flex;flex-direction:column;gap:4px">
                            @foreach($companies as $comp)
                                <label style="display:flex;align-items:center;gap:6px;font-size:11.5px;color:#334155;cursor:pointer;margin:0">
                                    <input type="checkbox" name="company_ids[]" value="{{ $comp->id }}" class="hol-comp-chk" style="width:13px;height:13px;margin:0">
                                    <span>{{ $comp->name }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                    <div>
                        <label style="font-size:11.5px;font-weight:600;color:#64748b;margin-bottom:4px;display:block">Applicable Departments (Blank = All)</label>
                        <div style="max-height:85px;overflow-y:auto;background:#fff;border:1px solid #cbd5e1;border-radius:6px;padding:6px 8px;display:flex;flex-direction:column;gap:4px">
                            @foreach($departments as $dept)
                                <label style="display:flex;align-items:center;gap:6px;font-size:11.5px;color:#334155;cursor:pointer;margin:0">
                                    <input type="checkbox" name="department_ids[]" value="{{ $dept->id }}" class="hol-dept-chk" style="width:13px;height:13px;margin:0">
                                    <span>{{ $dept->name }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div>
                    <label style="font-size:11.5px;font-weight:600;color:#64748b;margin-bottom:4px;display:block">Description</label>
                    <input name="description" id="holiday_description" placeholder="Public or Festival Holiday" style="width:100%;border:1px solid #cbd5e1;border-radius:6px;padding:6px 10px;font-size:12px;background:#fff;box-sizing:border-box">
                </div>
            </div>

            <!-- Footer Actions -->
            <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:6px;padding-top:10px;border-top:1px solid #e5e7eb">
                <button type="button" onclick="closeHolidayModal()" style="border:1px solid #d1d5db;background:#fff;color:#374151;border-radius:8px;padding:7px 18px;font-size:12.5px;font-weight:600;cursor:pointer">
                    Cancel
                </button>
                <button type="submit" id="holidaySubmitBtn" style="background:#1e293b;color:#fff;border:0;border-radius:8px;padding:7px 22px;font-size:12.5px;font-weight:700;cursor:pointer">
                    Save Holiday
                </button>
            </div>
        </form>
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

            <!-- Hidden Lat/Lng/Radius to preserve settings without cluttering UI -->
            <input type="hidden" name="office_lat" value="{{ $setting->office_lat }}">
            <input type="hidden" name="office_lng" value="{{ $setting->office_lng }}">
            <input type="hidden" name="geofence_radius_m" value="{{ $setting->geofence_radius_m }}">

            <div style="display:flex;gap:12px;margin-top:14px;align-items:center">
                <button class="btn"><i class="fa-solid fa-floppy-disk"></i> Save System Settings</button>
            </div>
        </form>
    </div>
</div>


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
                        'employee.import' => 'Excel Import Employees',
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
                    <label style="font-weight:600;font-size:12px;display:block;margin-bottom:4px">Salary Calculation Rule</label>
                    <select id="edit_comp_salary_days" name="salary_calculation_days" style="font-weight:600">
                        <option value="actual">Actual (Current Month Days - 28/29/30/31)</option>
                        <option value="30">30 Days (Fixed 30 Days)</option>
                        <option value="26">26 Days (Fixed 26 Days)</option>
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

/* Holiday Modal & Interactive Calendar matching Screenshots 2 & 3 */
let holidayCalYear = new Date().getFullYear();
let holidayCalMonth = new Date().getMonth(); // 0-indexed
let selectedHolidayDateStr = new Date().toISOString().substring(0, 10);
const monthNamesList = ['January','February','March','April','May','June','July','August','September','October','November','December'];

function renderHolidayCalendar() {
    const titleEl = document.getElementById('holidayCalTitle');
    const gridEl = document.getElementById('holidayDaysGrid');
    if (!titleEl || !gridEl) return;

    titleEl.textContent = monthNamesList[holidayCalMonth] + ' ' + holidayCalYear;
    gridEl.innerHTML = '';

    const firstDayIndex = new Date(holidayCalYear, holidayCalMonth, 1).getDay(); // 0 = Sunday
    const daysInMonth = new Date(holidayCalYear, holidayCalMonth + 1, 0).getDate();

    // Leading blanks
    for (let i = 0; i < firstDayIndex; i++) {
        const blank = document.createElement('div');
        gridEl.appendChild(blank);
    }

    // Days 1..N
    for (let day = 1; day <= daysInMonth; day++) {
        const mStr = String(holidayCalMonth + 1).padStart(2, '0');
        const dStr = String(day).padStart(2, '0');
        const thisDateStr = `${holidayCalYear}-${mStr}-${dStr}`;
        const isSelected = (thisDateStr === selectedHolidayDateStr);

        const dayBtn = document.createElement('div');
        dayBtn.style.padding = '6px 2px';
        dayBtn.style.fontSize = '12px';
        dayBtn.style.cursor = 'pointer';
        dayBtn.style.borderRadius = '6px';
        dayBtn.style.fontWeight = isSelected ? '800' : '500';
        dayBtn.style.color = isSelected ? '#ffffff' : '#1e293b';
        dayBtn.style.background = isSelected ? '#1f2937' : 'transparent';
        dayBtn.style.transition = 'all 0.15s ease';
        dayBtn.textContent = day;

        if (!isSelected) {
            dayBtn.onmouseover = function() { this.style.background = '#f1f5f9'; };
            dayBtn.onmouseout = function() { this.style.background = 'transparent'; };
        }

        dayBtn.onclick = function() {
            selectedHolidayDateStr = thisDateStr;
            document.getElementById('holiday_date').value = thisDateStr;
            renderHolidayCalendar();
        };

        gridEl.appendChild(dayBtn);
    }
}

function navigateHolidayCal(direction) {
    holidayCalMonth += direction;
    if (holidayCalMonth < 0) {
        holidayCalMonth = 11;
        holidayCalYear--;
    } else if (holidayCalMonth > 11) {
        holidayCalMonth = 0;
        holidayCalYear++;
    }
    renderHolidayCalendar();
}

function openAddHolidayModal() {
    document.getElementById('holidayModalTitle').textContent = 'Add Holiday';
    document.getElementById('holidaySubmitBtn').textContent = 'Save Holiday';
    document.getElementById('holidayForm').action = "{{ route('settings.holidays.store') }}";
    document.getElementById('holiday_form_method').value = 'post';

    document.getElementById('holiday_name').value = '';
    document.getElementById('holiday_description').value = '';

    const today = new Date();
    holidayCalYear = today.getFullYear();
    holidayCalMonth = today.getMonth();
    selectedHolidayDateStr = today.toISOString().substring(0, 10);
    document.getElementById('holiday_date').value = selectedHolidayDateStr;

    document.querySelectorAll('.hol-cat-chk').forEach(c => c.checked = false);
    document.querySelectorAll('.hol-comp-chk').forEach(c => c.checked = true);
    document.querySelectorAll('.hol-dept-chk').forEach(c => c.checked = true);

    renderHolidayCalendar();
    document.getElementById('holidayModal').style.display = 'flex';
}

function openEditHolidayModal(hol) {
    document.getElementById('holidayModalTitle').textContent = 'Edit Holiday';
    document.getElementById('holidaySubmitBtn').textContent = 'Update Holiday';
    document.getElementById('holidayForm').action = "/settings/holidays/" + hol.id;
    document.getElementById('holiday_form_method').value = 'put';

    document.getElementById('holiday_name').value = hol.name || '';
    document.getElementById('holiday_description').value = hol.description || '';

    // Date setup
    const dateStr = (hol.date || '').substring(0, 10);
    selectedHolidayDateStr = dateStr;
    document.getElementById('holiday_date').value = dateStr;

    if (dateStr) {
        const parts = dateStr.split('-');
        holidayCalYear = parseInt(parts[0], 10);
        holidayCalMonth = parseInt(parts[1], 10) - 1;
    }

    // Check categories
    const catIds = Array.isArray(hol.category_ids) ? hol.category_ids : [];
    document.querySelectorAll('.hol-cat-chk').forEach(c => {
        c.checked = catIds.includes(parseInt(c.value)) || catIds.includes(String(c.value));
    });

    // Check companies
    const compIds = Array.isArray(hol.company_ids) ? hol.company_ids : [];
    document.querySelectorAll('.hol-comp-chk').forEach(c => {
        c.checked = compIds.length === 0 || compIds.includes(parseInt(c.value)) || compIds.includes(String(c.value));
    });

    // Check departments
    const deptIds = Array.isArray(hol.department_ids) ? hol.department_ids : [];
    document.querySelectorAll('.hol-dept-chk').forEach(c => {
        c.checked = deptIds.length === 0 || deptIds.includes(parseInt(c.value)) || deptIds.includes(String(c.value));
    });

    renderHolidayCalendar();
    document.getElementById('holidayModal').style.display = 'flex';
}

function closeHolidayModal() {
    document.getElementById('holidayModal').style.display = 'none';
}
</script>
@endsection
