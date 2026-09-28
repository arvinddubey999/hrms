<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ADCodeNexus {{ $title ?? '' }}</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
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
@endphp
<div class="topbar">
    <div class="brand">ADCodeNexus<span></span> <small>v1.0</small></div>
    <input class="search" placeholder="Search" onkeydown="if(event.key==='Enter'){ location.href='{{ route('attendances.index') }}?q='+this.value }">
    <div class="who">
        <div>{{ auth()->user()->displayName() }}<div class="muted" style="color:#bbb">{{ auth()->user()->phone }}</div></div>
        
        <div class="avatar">
            @if(auth()->user()->profile_photo)
                <img src="{{ asset('storage/'.auth()->user()->profile_photo) }}" alt="Profile Photo" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">
            @else
                {{ auth()->user()->initials() }}
            @endif
        <!-- {{ auth()->user()->initials() }} -->
    </div>
    </div>
</div>
<div class="shell">
    <aside class="sidebar">
        <div class="company-pill">{{ \App\Models\Setting::current()->company_name }}</div>
        <nav class="nav">
            <a class="{{ $nav==='employees'?'active':'' }}" href="{{ route('attendances.index') }}">Employees</a>
            <details {{ $nav==='attendances'?'open':'' }}>
                <summary class="{{ $nav==='attendances'?'active':'' }}">Attendances</summary>
                <a href="{{ route('attendances.index') }}">Live board</a>
                <a href="{{ route('settings.index') }}">Category</a>
            </details>
            <a class="{{ $nav==='requests'?'active':'' }}" href="{{ route('requests.index') }}">Requests</a>
            <a class="{{ $nav==='payroll'?'active':'' }}" href="{{ route('payroll.index') }}">Payroll</a>
            <details {{ $nav==='tracking'?'open':'' }}>
                <summary>Live Tracking <span class="beta">Beta</span></summary>
                <a href="{{ route('tracking.realtime') }}">Realtime</a>
                <a class="{{ $nav==='tracking'?'active':'' }}" href="{{ route('tracking.timeline') }}">Timeline</a>
            </details>
            <details {{ $nav==='tasks'?'open':'' }}>
                <summary>Works <span class="beta">Beta</span></summary>
                <a class="{{ $nav==='tasks'?'active':'' }}" href="{{ route('tasks.index') }}">Tasks</a>
            </details>
            <a class="{{ $nav==='reports'?'active':'' }}" href="{{ route('reports.index') }}">Reports</a>
            <a class="{{ $nav==='roster'?'active':'' }}" href="{{ route('roster.index') }}">Monthly Roster</a>
        </nav>
        <a class="settings-link {{ $nav==='settings'?'active':'' }}" href="{{ route('settings.index') }}" style="color:#bbb;padding:10px 12px">Settings</a>
        <form method="post" action="{{ route('logout') }}">@csrf<button class="btn light" style="width:100%;margin-top:8px">Logout</button></form>
    </aside>
    <main class="main">
        @if(session('ok'))<div class="flash">{{ session('ok') }}</div>@endif
        @if($errors->any())<div class="flash" style="background:#fee2e2;color:#991b1b">{{ $errors->first() }}</div>@endif
        @yield('content')
    </main>
</div>
@yield('scripts')
</body>
</html>
