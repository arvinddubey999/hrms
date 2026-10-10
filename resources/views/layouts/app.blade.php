<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ \App\Models\Setting::current()->company_name }} - {{ $title ?? 'Dashboard' }}</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
@php
$route = request()->route()?->getName() ?? '';
$nav = str_starts_with($route, 'request') ? 'requests'
    : (str_starts_with($route, 'payroll') ? 'payroll'
    : (str_starts_with($route, 'task') ? 'tasks'
    : (str_starts_with($route, 'report') ? 'reports'
    : (str_starts_with($route, 'tracking') ? 'tracking'
    : (str_starts_with($route, 'roster') ? 'roster'
    : (str_starts_with($route, 'setting') ? 'settings'
    : (str_contains($route, 'employee') || str_starts_with($route, 'attendance') ? 'attendances' : '')))))));
$setting = \App\Models\Setting::current();
@endphp
<div class="topbar">
    <div class="brand" style="display:flex;align-items:center;gap:10px">
        @if($setting->company_logo)
            <img src="{{ asset('storage/'.$setting->company_logo) }}" alt="Logo" style="height:32px;object-fit:contain;border-radius:4px;">
        @else
            <i class="fa-solid fa-building" style="color:var(--accent)"></i>
        @endif
        <span>{{ $setting->company_name }}</span>
    </div>
    <input class="search" placeholder="Search employees by name, serial no, phone, dept..." onkeydown="if(event.key==='Enter'){ location.href='{{ route('attendances.index') }}?q='+this.value }">
    <div class="who">
        <div style="text-align:right">
            <div>{{ auth()->user()->displayName() }}</div>
            <div class="muted" style="color:#bbb;font-size:11px">{{ auth()->user()->phone }} ({{ strtoupper(auth()->user()->role) }})</div>
        </div>
        <div class="avatar">
            @if(auth()->user()->profile_photo)
                <img src="{{ asset('storage/'.auth()->user()->profile_photo) }}" alt="Profile Photo" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">
            @else
                {{ auth()->user()->initials() }}
            @endif
        </div>
    </div>
</div>
<div class="shell">
    <aside class="sidebar">
        <div class="company-pill" style="display:flex;align-items:center;gap:8px">
            @if($setting->company_logo)
                <img src="{{ asset('storage/'.$setting->company_logo) }}" style="width:24px;height:24px;object-fit:contain">
            @else
                <i class="fa-solid fa-building"></i>
            @endif
            <div style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $setting->company_name }}</div>
        </div>
        <nav class="nav">
            <a class="{{ $nav==='employees'?'active':'' }}" href="{{ route('attendances.index') }}"><i class="fa-solid fa-users" style="width:18px"></i> Employees</a>
            <details {{ $nav==='attendances'?'open':'' }}>
                <summary class="{{ $nav==='attendances'?'active':'' }}"><i class="fa-solid fa-calendar-check" style="width:18px"></i> Attendances</summary>
                <a href="{{ route('attendances.index') }}">Live board</a>
                <a href="{{ route('settings.index') }}">Category & Masters</a>
            </details>
            <a class="{{ $nav==='requests'?'active':'' }}" href="{{ route('requests.index') }}"><i class="fa-solid fa-envelope-open-text" style="width:18px"></i> Requests</a>
            <a class="{{ $nav==='payroll'?'active':'' }}" href="{{ route('payroll.index') }}"><i class="fa-solid fa-money-bill-wave" style="width:18px"></i> Payroll</a>
            <details {{ $nav==='tracking'?'open':'' }}>
                <summary><i class="fa-solid fa-location-dot" style="width:18px"></i> Live Tracking</summary>
                <a href="{{ route('tracking.realtime') }}">Realtime</a>
                <a class="{{ $nav==='tracking'?'active':'' }}" href="{{ route('tracking.timeline') }}">Timeline</a>
            </details>
            <details {{ $nav==='tasks'?'open':'' }}>
                <summary><i class="fa-solid fa-list-check" style="width:18px"></i> Works</summary>
                <a class="{{ $nav==='tasks'?'active':'' }}" href="{{ route('tasks.index') }}">Tasks</a>
            </details>
            <a class="{{ $nav==='reports'?'active':'' }}" href="{{ route('reports.index') }}"><i class="fa-solid fa-chart-pie" style="width:18px"></i> Reports</a>
            <a class="{{ $nav==='roster'?'active':'' }}" href="{{ route('roster.index') }}"><i class="fa-solid fa-calendar-days" style="width:18px"></i> Monthly Roster</a>
        </nav>
        <a class="settings-link {{ $nav==='settings'?'active':'' }}" href="{{ route('settings.index') }}" style="color:#bbb;padding:10px 12px"><i class="fa-solid fa-gear"></i> Settings & Masters</a>
        <form method="post" action="{{ route('logout') }}">@csrf<button class="btn light" style="width:100%;margin-top:8px"><i class="fa-solid fa-sign-out-alt"></i> Logout</button></form>
    </aside>
    <main class="main">
        @if(session('ok'))<div class="flash"><i class="fa-solid fa-circle-check"></i> {{ session('ok') }}</div>@endif
        @if($errors->any())<div class="flash" style="background:#fee2e2;color:#991b1b"><i class="fa-solid fa-triangle-exclamation"></i> {{ $errors->first() }}</div>@endif
        @yield('content')
    </main>
</div>
@yield('scripts')
</body>
</html>
