@extends('layouts.app')
@section('content')
<h1>← Settings & Masters</h1>

<div class="card" style="display:flex;align-items:center;gap:18px;margin-bottom:14px">
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

<!-- Main Settings Form -->
<form class="card" method="post" action="{{ route('settings.update') }}" enctype="multipart/form-data">
    @csrf
    <h3>Upload Company Logo & Main Settings</h3>
    <div class="grid-2">
        <div>
            <label>Upload Dynamic Company Logo</label>
            <input type="file" name="company_logo" accept="image/*">
        </div>
        <div>
            <label>Company Main Name</label>
            <input name="company_name" value="{{ $setting->company_name }}" required>
        </div>
    </div>

    <label>Company Address</label>
    <input name="company_address" value="{{ $setting->company_address }}">

    <div class="grid-3" style="margin-top:10px">
        <div>
            <label>COMPANY WISE LATITUDE *</label>
            <input name="office_lat" value="{{ $setting->office_lat }}" required>
        </div>
        <div>
            <label>COMPANY WISE LONGITUDE *</label>
            <input name="office_lng" value="{{ $setting->office_lng }}" required>
        </div>
        <div>
            <label>Geofence Radius (meters)</label>
            <input name="geofence_radius_m" value="{{ $setting->geofence_radius_m }}" required>
        </div>
    </div>

    <hr style="margin:16px 0;border:0;border-top:1px solid #eee">
    <h3>Admin Personal Details</h3>
    <div class="grid-3">
        <div><label>First Name</label><input name="first_name" value="{{ auth()->user()->first_name }}"></div>
        <div><label>Last Name</label><input name="last_name" value="{{ auth()->user()->last_name }}"></div>
        <div><label>Phone Number</label><input name="phone" value="{{ auth()->user()->phone }}"></div>
    </div>
    <button class="btn" style="margin-top:12px">Save General Settings</button>
</form>

<!-- COMPANY MASTER SECTION -->
<div class="card" style="margin-top:16px">
    <h3><i class="fa-solid fa-building"></i> Company Master (Add / Edit / Delete)</h3>
    <p class="muted">Add companies here so they populate in Add Employee dropdown and Holiday Applicability selection.</p>
    
    <form class="row" method="post" action="{{ route('settings.companies.store') }}" enctype="multipart/form-data" style="margin-bottom:14px;gap:8px">
        @csrf
        <input name="name" placeholder="Company Name (e.g. Tulsi Fabrics)" required style="flex:1">
        <input type="text" name="latitude" placeholder="Latitude (optional)" style="width:140px">
        <input type="text" name="longitude" placeholder="Longitude (optional)" style="width:140px">
        <input type="file" name="logo" accept="image/*" style="width:200px">
        <button class="btn">+ Add Company</button>
    </form>

    <table class="table">
        <thead>
            <tr><th>Logo</th><th>Company Name</th><th>Lat/Lng</th><th>Actions</th></tr>
        </thead>
        <tbody>
            @forelse($companies as $comp)
                <tr>
                    <td style="width:50px">
                        @if($comp->logo)
                            <img src="{{ asset('storage/'.$comp->logo) }}" style="width:36px;height:36px;object-fit:contain;border-radius:4px">
                        @else
                            <i class="fa-solid fa-building" style="color:var(--muted)"></i>
                        @endif
                    </td>
                    <td><b>{{ $comp->name }}</b></td>
                    <td>{{ $comp->latitude ? round($comp->latitude,4).', '.round($comp->longitude,4) : 'Default' }}</td>
                    <td>
                        <form method="post" action="{{ route('settings.companies.destroy', $comp) }}" style="display:inline" onsubmit="return confirm('Delete company?')">
                            @csrf @method('delete')
                            <button class="btn light" style="font-size:11px;padding:3px 6px">Delete</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="muted" style="text-align:center">No companies added yet in Master.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<!-- DEPARTMENT MASTER SECTION -->
<div class="card" style="margin-top:16px">
    <h3><i class="fa-solid fa-sitemap"></i> Department Master (Add / Edit / Delete)</h3>
    <p class="muted">Add departments here to show in Employee form dropdown instead of text box.</p>
    
    <form class="row" method="post" action="{{ route('settings.departments.store') }}" style="margin-bottom:14px">
        @csrf
        <input name="name" placeholder="Department Name (e.g. Accounts, Sales, IT)" required style="max-width:320px">
        <button class="btn">+ Add Department</button>
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
                        <form method="post" action="{{ route('settings.departments.destroy', $dept) }}" style="display:inline" onsubmit="return confirm('Delete department?')">
                            @csrf @method('delete')
                            <button class="btn light" style="font-size:11px;padding:3px 6px">Delete</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="2" class="muted" style="text-align:center">No departments added yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<!-- HOLIDAY MASTER & SELECTION SECTION -->
<div class="card" style="margin-top:16px">
    <h3><i class="fa-solid fa-umbrella-beach"></i> Holiday Master & Add New Holidays Options</h3>
    <p class="muted">Rule: Holiday credit is granted only if employee is Present on either 1st day of holiday or next day after holiday.</p>

    <form method="post" action="{{ route('settings.holidays.store') }}" style="margin-bottom:16px;background:#f9fafb;padding:14px;border-radius:12px;border:1px solid #eee">
        @csrf
        <strong style="display:block;margin-bottom:8px">+ Add New Holiday</strong>
        <div class="grid-3">
            <div><label>Holiday Name *</label><input name="name" placeholder="Diwali / Independence Day" required></div>
            <div><label>Date *</label><input type="date" name="date" required></div>
            <div><label>Description</label><input name="description" placeholder="Public holiday"></div>
        </div>

        <label style="margin-top:10px">HOLIDAYS KONSI KONSI COMPANY KE LIE APPLICABLE HOGI USKA SELECTION:</label>
        <div class="row" style="gap:16px;flex-wrap:wrap;margin:6px 0 12px 0">
            @forelse($companies as $comp)
                <label style="font-weight:normal"><input type="checkbox" name="company_ids[]" value="{{ $comp->id }}" checked> {{ $comp->name }}</label>
            @empty
                <span class="muted">Add companies in Company Master first to select applicability.</span>
            @endforelse
        </div>

        <button class="btn">+ Save Holiday</button>
    </form>

    <table class="table">
        <thead>
            <tr><th>Date</th><th>Holiday Name</th><th>Applicable Companies</th><th>Description</th><th>Actions</th></tr>
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
                                <span class="chip">{{ \App\Models\Company::find($cId)?->name ?? 'Company #'.$cId }}</span>
                            @endforeach
                        @endif
                    </td>
                    <td>{{ $hol->description ?: '-' }}</td>
                    <td>
                        <form method="post" action="{{ route('settings.holidays.destroy', $hol) }}" style="display:inline" onsubmit="return confirm('Delete holiday?')">
                            @csrf @method('delete')
                            <button class="btn light" style="font-size:11px;padding:3px 6px">Delete</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="muted" style="text-align:center">No holidays configured yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<!-- SHIFT MASTER SECTION -->
<div class="card" style="margin-top:16px">
    <h3><i class="fa-solid fa-clock"></i> Shift Master (Shift Time Editable)</h3>
    <form class="row" method="post" action="{{ route('settings.shifts') }}" style="margin-bottom:14px">
        @csrf
        <input name="name" placeholder="Shift Name (e.g. Morning Shift)" required style="width:200px">
        <label style="margin:0 4px">Start:</label>
        <input type="time" name="start_time" value="09:30" required style="width:130px">
        <label style="margin:0 4px">End:</label>
        <input type="time" name="end_time" value="18:30" required style="width:130px">
        <button class="btn">+ Add Shift</button>
    </form>

    <table class="table">
        <thead>
            <tr><th>Shift Name</th><th>Start Time</th><th>End Time</th><th>Actions</th></tr>
        </thead>
        <tbody>
            @foreach($shifts as $s)
                <tr>
                    <td><b>{{ $s->name }}</b></td>
                    <td>
                        <form method="post" action="{{ route('settings.shifts.update', $s) }}" class="row" style="gap:4px">
                            @csrf @method('put')
                            <input type="hidden" name="name" value="{{ $s->name }}">
                            <input type="time" name="start_time" value="{{ substr($s->start_time,0,5) }}" style="width:110px;padding:4px">
                    </td>
                    <td>
                            <input type="time" name="end_time" value="{{ substr($s->end_time,0,5) }}" style="width:110px;padding:4px">
                    </td>
                    <td>
                            <button class="btn light" style="font-size:11px;padding:3px 6px">Update Time</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
