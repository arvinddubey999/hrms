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

<!-- Main General Settings Form -->
<form class="card" method="post" action="{{ route('settings.update') }}" enctype="multipart/form-data" style="margin-bottom:20px">
    @csrf
    <h3><i class="fa-solid fa-sliders"></i> General & Admin Settings</h3>
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

    <hr style="margin:16px 0;border:0;border-top:1px solid #eee">
    <h3>Admin Personal Info</h3>
    <div class="grid-3">
        <div><label>First Name</label><input name="first_name" value="{{ auth()->user()->first_name }}"></div>
        <div><label>Last Name</label><input name="last_name" value="{{ auth()->user()->last_name }}"></div>
        <div><label>Phone Number</label><input name="phone" value="{{ auth()->user()->phone }}"></div>
    </div>
    <button class="btn" style="margin-top:14px"><i class="fa-solid fa-floppy-disk"></i> Save General Settings</button>
</form>

<!-- COMPANY MASTER SECTION (ADD / EDIT / DELETE) -->
<div class="card" style="margin-bottom:20px">
    <h3><i class="fa-solid fa-building"></i> Company Master (Add / Edit / Delete)</h3>
    <p class="muted">Manage multiple companies, location addresses, Lat/Lng coordinates, salary calculation rules (30 vs 31 days), employee code prefixes, and professional tax settings.</p>
    
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
                    <tr><td colspan="6" class="muted" style="text-align:center">No companies added yet in Master.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- DEPARTMENT MASTER SECTION (ADD / EDIT / DELETE) -->
<div class="card" style="margin-bottom:20px">
    <h3><i class="fa-solid fa-sitemap"></i> Department Master (Add / Edit / Delete)</h3>
    <p class="muted">Departments populate in Add Employee form & Holiday applicability rules.</p>
    
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

<!-- SHIFT MASTER SECTION (ADD / EDIT) -->
<div class="card" style="margin-bottom:20px">
    <h3><i class="fa-solid fa-clock"></i> Shift Master (Shift Name & Time Editable)</h3>
    <p class="muted">Define shift timing rules for late attendance and overtime calculations.</p>

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

<!-- HOLIDAY MASTER & APPLICABILITY SECTION -->
<div class="card" style="margin-bottom:20px">
    <h3><i class="fa-solid fa-umbrella-beach"></i> Holiday & Leave Management Master</h3>
    <p class="muted">Add holidays (e.g. 10th October Mahavir Jayanti) and select which Company AND which Department the leave is applicable to.</p>

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
                <label style="font-weight:600;margin-bottom:6px">Select Applicable Companies (Blank = All Companies):</label>
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
                <label style="font-weight:600;margin-bottom:6px">Select Applicable Departments (Blank = All Departments):</label>
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
