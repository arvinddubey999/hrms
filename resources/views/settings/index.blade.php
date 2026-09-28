@extends('layouts.app')
@section('content')
<h1>← Settings</h1>
<div class="card" style="text-align:center">
    <h2>{{ $setting->company_name }}</h2>
    <div class="brand"><a href="https://adcodenexus.com" target="_blank">ADCodeNexus</a></div>
</div>
<form class="card" style="margin-top:14px" method="post" action="{{ route('settings.update') }}" enctype="multipart/form-data">
    @csrf
    <h3>Personal Details</h3>
    <div class="grid-3">
        <div><label>First Name</label><input name="first_name" value="{{ auth()->user()->first_name }}"></div>
        <div><label>Last Name</label><input name="last_name" value="{{ auth()->user()->last_name }}"></div>
        <div><label>Phone Number</label><input name="phone" value="{{ auth()->user()->phone }}"></div>
        <div><label>Pay Type</label>
            <select name="pay_type"><option value="monthly">Monthly</option><option value="daily">Daily</option></select>
        </div>
        <div><label>Monthly Salary</label><input name="salary" value="{{ auth()->user()->salary }}"></div>
        <div><label>Date of Joining</label><input type="date" name="date_of_joining" value="{{ optional(auth()->user()->date_of_joining)->toDateString() }}"></div>
    </div>
    <label>Profile photo</label><input type="file" name="profile_photo">
    <label><input type="checkbox" name="multiple_attendance" value="1" {{ auth()->user()->multiple_attendance?'checked':'' }}> Multiple Attendance</label>
    <label><input type="checkbox" name="live_tracking" value="1" {{ auth()->user()->live_tracking?'checked':'' }}> Live Tracking</label>
    <label><input type="checkbox" name="mobile_attendance" value="1" {{ auth()->user()->mobile_attendance?'checked':'' }}> Mobile Attendance</label>
    <h3>Company Details</h3>
    <label>Company name</label><input name="company_name" value="{{ $setting->company_name }}">
    <label>Address</label><input name="company_address" value="{{ $setting->company_address }}">
    <div class="grid-3">
        <div><label>Office latitude</label><input name="office_lat" value="{{ $setting->office_lat }}"></div>
        <div><label>Office longitude</label><input name="office_lng" value="{{ $setting->office_lng }}"></div>
        <div><label>Geofence radius (meters)</label><input name="geofence_radius_m" value="{{ $setting->geofence_radius_m }}"></div>
    </div>
    <p class="muted">Set this to your company gate. Staff punch IN only inside this circle. If they go out, the app auto marks OUT.</p>
    <button class="btn">Save</button>
</form>
<div class="card" style="margin-top:14px">
    <h3>Company Shifts</h3>
    <form class="row" method="post" action="{{ route('settings.shifts') }}">
        @csrf
        <input name="name" placeholder="General Shift" style="width:200px">
        <input type="time" name="start_time" value="10:00">
        <input type="time" name="end_time" value="19:00">
        <button class="btn">Add New Shift</button>
    </form>
    <ul>
        @foreach($shifts as $s)
            <li>{{ $s->name }} ({{ substr($s->start_time,0,5) }} - {{ substr($s->end_time,0,5) }})</li>
        @endforeach
    </ul>
</div>
<div class="card" style="margin-top:14px">
    <h3>Categories</h3>
    <form class="row" method="post" action="{{ route('settings.categories') }}">
        @csrf
        <input name="name" placeholder="Support Team">
        <button class="btn">Add</button>
    </form>
    <p>{{ $categories->pluck('name')->join(', ') }}</p>
</div>
@endsection
