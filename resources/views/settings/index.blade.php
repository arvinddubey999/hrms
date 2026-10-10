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

<!-- 3. DESIGNATIONS & PERMISSIONS (ACCORDION - MATCHING SCREENSHOT 3) -->
<div class="accordion-item">
    <div class="accordion-header" onclick="toggleAccordion(this)">
        <span><i class="fa-solid fa-user-gear" style="color:var(--accent);margin-right:8px"></i> Designations & Permissions</span>
        <i class="fa-solid fa-chevron-down chevron"></i>
    </div>
    <div class="accordion-body">
        <p class="muted">Module-wise Access Control & Designation Rights.</p>
        <button type="button" class="btn" onclick="openNewDesignationDrawer()" style="margin-bottom:14px">
            <i class="fa-solid fa-plus"></i> Add New Designation
        </button>

        <div style="display:grid;grid-template-columns:repeat(auto-fill, minmax(280px, 1fr));gap:14px">
            @forelse($designations as $desig)
                @php $permCount = count($desig->permissions ?? []); @endphp
                <div style="background:#f8fafc;border:1px solid #cbd5e1;border-radius:10px;padding:14px;display:flex;justify-content:space-between;align-items:center">
                    <div>
                        <div style="font-weight:700;font-size:14px;color:#0f172a">{{ $desig->name }}</div>
                        <div style="font-size:11px;color:#64748b"><i class="fa-solid fa-shield-halved"></i> {{ $permCount }} permissions configured</div>
                    </div>
                    <button type="button" class="btn light" style="font-size:12px;padding:4px 8px" onclick="openEditDesignationDrawer({{ json_encode($desig) }})">
                        <i class="fa-solid fa-pen-to-square"></i> Edit
                    </button>
                </div>
            @empty
                <div class="muted">No custom designations configured yet. Click button above to create one.</div>
            @endforelse
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


<!-- SLIDE-OVER DRAWER MODAL FOR EDIT DESIGNATION & MODULE PERMISSIONS (SCREENSHOT 3) -->
<div id="designationDrawer" class="drawer-overlay">
    <div class="drawer-content">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;border-bottom:1px solid #e2e8f0;padding-bottom:12px">
            <h3 style="margin:0;font-size:18px;font-weight:700"><i class="fa-solid fa-user-shield" style="color:var(--accent)"></i> <span id="drawerTitle">Edit Designation</span></h3>
            <button type="button" onclick="closeDesignationDrawer()" style="border:0;background:none;font-size:20px;cursor:pointer">&times;</button>
        </div>

        <form id="designationForm" method="post" style="flex:1;display:flex;flex-direction:column">
            @csrf
            <input type="hidden" id="designation_method" name="_method" value="post">
            
            <label style="font-weight:600;margin-bottom:6px">Designation Name *</label>
            <input id="desig_name" name="name" placeholder="e.g. FLOOR INCHARGE, ACCOUNTS HEAD" required style="margin-bottom:16px">

            <label style="font-weight:700;margin-bottom:8px;display:block;color:#1e293b">Update Module-Wise Permissions</label>
            <p class="muted" style="margin-top:0;font-size:11px">Select specific Add, View, Edit, and Delete rights for each system module.</p>

            <table class="permission-table">
                <thead>
                    <tr style="background:#f8fafc">
                        <th>Module Name</th>
                        <th><i class="fa-solid fa-plus-circle" style="color:#10b981"></i> Add</th>
                        <th><i class="fa-solid fa-eye" style="color:#3b82f6"></i> View</th>
                        <th><i class="fa-solid fa-pen" style="color:#f59e0b"></i> Edit</th>
                        <th><i class="fa-solid fa-trash" style="color:#ef4444"></i> Delete</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $modules = [
                            'ATTENDANCE' => 'Attendance & Punches',
                            'SALARY' => 'Salary & Payslips',
                            'INCENTIVE' => 'Incentive Management',
                            'EXPENSE' => 'Expense Claims',
                            'LOAN' => 'Loan & Salary Advance',
                            'EMPLOYEE' => 'Employee Profiles',
                            'SHIFT' => 'Shifts & Timings',
                            'PAYROLL CONFIG' => 'Payroll & Statutory Config',
                            'GEOFENCING' => 'Geofencing & Locations',
                        ];
                    @endphp
                    @foreach($modules as $modKey => $modLabel)
                        <tr>
                            <td>{{ $modKey }}</td>
                            <td><input type="checkbox" name="permissions[{{ $modKey }}][add]" value="1" class="perm-chk perm-add-{{ $modKey }}"></td>
                            <td><input type="checkbox" name="permissions[{{ $modKey }}][view]" value="1" class="perm-chk perm-view-{{ $modKey }}"></td>
                            <td><input type="checkbox" name="permissions[{{ $modKey }}][edit]" value="1" class="perm-chk perm-edit-{{ $modKey }}"></td>
                            <td><input type="checkbox" name="permissions[{{ $modKey }}][delete]" value="1" class="perm-chk perm-delete-{{ $modKey }}"></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div style="margin-top:auto;padding-top:20px;display:flex;justify-content:flex-end;gap:10px">
                <button type="button" class="btn light" onclick="closeDesignationDrawer()">Cancel</button>
                <button class="btn"><i class="fa-solid fa-floppy-disk"></i> Save Changes</button>
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

function openNewDesignationDrawer() {
    document.getElementById('drawerTitle').innerText = 'Add New Designation';
    document.getElementById('designationForm').action = "{{ route('settings.designations.store') }}";
    document.getElementById('designation_method').value = 'post';
    document.getElementById('desig_name').value = '';
    document.querySelectorAll('.perm-chk').forEach(c => c.checked = false);
    document.getElementById('designationDrawer').style.display = 'flex';
}

function openEditDesignationDrawer(desig) {
    document.getElementById('drawerTitle').innerText = 'Edit Designation: ' + desig.name;
    document.getElementById('designationForm').action = "/settings/designations/" + desig.id;
    document.getElementById('designation_method').value = 'put';
    document.getElementById('desig_name').value = desig.name || '';
    
    document.querySelectorAll('.perm-chk').forEach(c => c.checked = false);
    if (desig.permissions) {
        Object.keys(desig.permissions).forEach(mod => {
            const actions = desig.permissions[mod];
            if (actions) {
                Object.keys(actions).forEach(act => {
                    const el = document.querySelector(`.perm-${act}-${mod}`);
                    if (el) el.checked = true;
                });
            }
        });
    }
    document.getElementById('designationDrawer').style.display = 'flex';
}

function closeDesignationDrawer() {
    document.getElementById('designationDrawer').style.display = 'none';
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
