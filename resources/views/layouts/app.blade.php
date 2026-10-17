<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ \App\Models\Setting::current()->company_name }} - {{ $title ?? 'Dashboard' }}</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ file_exists(public_path('css/app.css')) ? filemtime(public_path('css/app.css')) : time() }}">
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
<div class="topbar" style="display:flex;align-items:center;justify-content:space-between;gap:18px">
    <div class="brand" style="display:flex;align-items:center;gap:10px;flex-shrink:0">
        @if($setting->company_logo)
            <img src="{{ asset('storage/'.$setting->company_logo) }}" alt="Logo" style="height:32px;object-fit:contain;border-radius:4px;">
        @else
            <i class="fa-solid fa-building" style="color:var(--accent)"></i>
        @endif
        <span>{{ $setting->company_name }}</span>
    </div>
    <div style="position:relative;display:flex;align-items:center;flex:1;max-width:440px;margin:0 auto">
        <input class="search" id="topbarSearchInput" placeholder="Search employees by name, serial no, phone, dept..." style="width:100%;padding-right:38px" onkeydown="if(event.key==='Enter'){ location.href='{{ route('attendances.index') }}?q='+encodeURIComponent(this.value) }">
        <button type="button" onclick="location.href='{{ route('attendances.index') }}?q='+encodeURIComponent(document.getElementById('topbarSearchInput').value)" style="position:absolute;right:6px;background:none;border:none;color:#9ca3af;cursor:pointer;padding:6px;display:flex;align-items:center;justify-content:center" title="Search">
            <i class="fa-solid fa-magnifying-glass"></i>
        </button>
    </div>
    <div class="who" style="position:relative;margin-left:auto;display:flex;align-items:center;gap:10px;flex-shrink:0">
        @if(auth()->check() && auth()->user())
            <div id="userMenuTrigger" onclick="toggleUserDropdown(event)" style="display:flex;align-items:center;gap:10px;background:#2a2a2a;padding:5px 8px 5px 14px;border-radius:12px;cursor:pointer;user-select:none;transition:background 0.2s" onmouseover="this.style.background='#333'" onmouseout="this.style.background='#2a2a2a'" title="Click to open menu">
                <div style="text-align:right">
                    <div style="font-weight:700;font-size:13px;color:#fff;line-height:1.2">{{ auth()->user()->displayName() }}</div>
                    <div style="color:#9ca3af;font-size:11px;line-height:1.2">{{ auth()->user()->phone ?: (auth()->user()->employee_code ?: 'N/A') }}</div>
                </div>
                <div class="avatar" style="background:#22c55e;color:#fff;font-weight:700;font-size:13px;border-radius:10px;width:34px;height:34px;display:grid;place-items:center;overflow:hidden">
                    @if(auth()->user()->profile_photo)
                        <img src="{{ asset('storage/'.auth()->user()->profile_photo) }}" alt="Profile Photo" style="width:100%;height:100%;object-fit:cover;border-radius:10px;">
                    @else
                        {{ auth()->user()->initials() }}
                    @endif
                </div>
            </div>

            <!-- Profile Dropdown Menu (Matches User Screenshot) -->
            <div id="userProfileDropdown" style="display:none;position:absolute;top:calc(100% + 8px);right:0;width:225px;background:#fff;border-radius:16px;box-shadow:0 12px 30px rgba(0,0,0,0.18), 0 4px 6px rgba(0,0,0,0.06);border:1px solid #e5e7eb;padding:8px 0;z-index:1000;color:#1f2937">
                <a href="{{ route('profile.settings') }}" style="display:flex;align-items:center;gap:12px;padding:10px 18px;color:#374151;font-size:13.5px;font-weight:500;text-decoration:none;transition:background 0.15s" onmouseover="this.style.background='#f3f4f6'" onmouseout="this.style.background='transparent'">
                    <i class="fa-regular fa-circle-user" style="font-size:17px;width:18px;color:#4b5563"></i> Profile Settings
                </a>

                @if(auth()->user()->hasPermission('settings.view') || auth()->user()->hasPermission('settings.roles'))
                    <a href="{{ route('settings.index') }}" style="display:flex;align-items:center;gap:12px;padding:10px 18px;color:#374151;font-size:13.5px;font-weight:500;text-decoration:none;transition:background 0.15s" onmouseover="this.style.background='#f3f4f6'" onmouseout="this.style.background='transparent'">
                        <i class="fa-solid fa-gear" style="font-size:16px;width:18px;color:#4b5563"></i> Workspace Settings
                    </a>
                @endif

                <!-- <a href="javascript:void(0)" onclick="alert('Tulsi HRMS mobile app APK is available on your dashboard');hideUserDropdown();" style="display:flex;align-items:center;gap:12px;padding:10px 18px;color:#374151;font-size:13.5px;font-weight:500;text-decoration:none;transition:background 0.15s" onmouseover="this.style.background='#f3f4f6'" onmouseout="this.style.background='transparent'">
                    <i class="fa-solid fa-mobile-screen" style="font-size:16px;width:18px;color:#4b5563"></i> Install Mobile App
                </a>

                <a href="javascript:void(0)" onclick="alert('For support, please contact your administrator');hideUserDropdown();" style="display:flex;align-items:center;gap:12px;padding:10px 18px;color:#374151;font-size:13.5px;font-weight:500;text-decoration:none;transition:background 0.15s" onmouseover="this.style.background='#f3f4f6'" onmouseout="this.style.background='transparent'">
                    <i class="fa-regular fa-circle-question" style="font-size:16px;width:18px;color:#4b5563"></i> Help Center
                </a> -->

                <div style="height:1px;background:#f3f4f6;margin:6px 0"></div>

                <form method="post" action="{{ route('logout') }}" style="margin:0">
                    @csrf
                    <button type="submit" style="width:100%;text-align:left;display:flex;align-items:center;gap:12px;padding:10px 18px;color:#ef4444;font-size:13.5px;font-weight:500;background:none;border:none;cursor:pointer;font-family:inherit;transition:background 0.15s" onmouseover="this.style.background='#fef2f2'" onmouseout="this.style.background='transparent'">
                        <i class="fa-solid fa-arrow-right-from-bracket" style="font-size:15px;width:18px;color:#ef4444"></i> Logout
                    </button>
                </form>
            </div>
        @endif
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
            @php $u = auth()->user(); @endphp
            @if(!$u || $u->hasPermission('employee.view') || $u->hasPermission('attendance.view'))
                <a class="{{ $nav==='employees'?'active':'' }}" href="{{ route('attendances.index') }}"><i class="fa-solid fa-users" style="width:18px"></i> Employees</a>
            @endif

            @if(!$u || $u->hasPermission('attendance.view') || $u->hasPermission('attendance.mark'))
                <details {{ $nav==='attendances'?'open':'' }}>
                    <summary class="{{ $nav==='attendances'?'active':'' }}"><i class="fa-solid fa-calendar-check" style="width:18px"></i> Attendances</summary>
                    <a href="{{ route('attendances.index') }}">Live board</a>
                </details>
            @endif

            @if(!$u || $u->hasPermission('leave.view') || $u->hasPermission('leave.apply') || $u->hasPermission('leave.approve'))
                <a class="{{ $nav==='requests'?'active':'' }}" href="{{ route('requests.index') }}"><i class="fa-solid fa-envelope-open-text" style="width:18px"></i> Requests</a>
            @endif

            @if(!$u || $u->hasPermission('payroll.view') || $u->hasPermission('payroll.process'))
                <a class="{{ $nav==='payroll'?'active':'' }}" href="{{ route('payroll.index') }}"><i class="fa-solid fa-money-bill-wave" style="width:18px"></i> Payroll</a>
            @endif

            @if(!$u || $u->hasPermission('tracking.view') || $u->hasPermission('geofence.tracking') || $u->hasPermission('geofence.view'))
                <details {{ in_array($nav ?? '', ['tracking', 'realtime', 'geofences']) || request()->is('tracking*') || request()->is('attendance/geofence-list*') ? 'open' : '' }}>
                    <summary><i class="fa-solid fa-location-dot" style="width:18px"></i> Live Tracking</summary>
                    <a class="{{ request()->routeIs('tracking.realtime') ? 'active' : '' }}" href="{{ route('tracking.realtime') }}">Realtime</a>
                    <a class="{{ request()->routeIs('tracking.timeline') ? 'active' : '' }}" href="{{ route('tracking.timeline') }}">Timeline</a>
                    <a class="{{ request()->routeIs('tracking.geofences') || request()->is('attendance/geofence-list*') ? 'active' : '' }}" href="{{ route('tracking.geofences') }}">Geo-fences</a>
                </details>
            @endif


            @if(!$u || $u->hasPermission('tasks.view') || $u->hasPermission('tasks.create') || $u->hasPermission('tasks.manage'))
                <a class="{{ $nav==='tasks'?'active':'' }}" href="{{ route('tasks.index') }}"><i class="fa-solid fa-list-check" style="width:18px"></i> Task Management</a>
            @endif

            @if(!$u || $u->hasPermission('dashboard.reports'))
                <a class="{{ $nav==='reports'?'active':'' }}" href="{{ route('reports.index') }}"><i class="fa-solid fa-chart-pie" style="width:18px"></i> Reports</a>
            @endif

            @if(!$u || $u->hasPermission('roster.view'))
                <a class="{{ $nav==='roster'?'active':'' }}" href="{{ route('roster.index') }}"><i class="fa-solid fa-calendar-days" style="width:18px"></i> Monthly Roster</a>
            @endif

            @if(!$u || $u->hasPermission('settings.view') || $u->hasPermission('settings.roles'))
                <a class="{{ $nav==='settings'?'active':'' }}" href="{{ route('settings.index') }}"><i class="fa-solid fa-gear" style="width:18px"></i> Settings & Masters</a>
            @endif
        </nav>
        <form method="post" action="{{ route('logout') }}">@csrf<button class="btn light" style="width:100%;margin-top:8px"><i class="fa-solid fa-sign-out-alt"></i> Logout</button></form>
    </aside>
    <main class="main">
        @if(session('ok'))<div class="flash"><i class="fa-solid fa-circle-check"></i> {{ session('ok') }}</div>@endif
        @if(session('error'))<div class="flash" style="background:#fee2e2;color:#991b1b"><i class="fa-solid fa-triangle-exclamation"></i> {{ session('error') }}</div>@endif
        @if(isset($errors) && $errors->any())<div class="flash" style="background:#fee2e2;color:#991b1b"><i class="fa-solid fa-triangle-exclamation"></i> {{ $errors->first() }}</div>@endif
        @yield('content')
    </main>
</div>

<!-- Modal: User Self Change Password -->
<div id="changePasswordModal" class="modal-bg" style="display:none;align-items:center;justify-content:center">
    <div class="modal" style="background:#fff;border-radius:14px;padding:22px;width:380px;max-width:92%">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px">
            <h3 style="margin:0;font-size:16px"><i class="fa-solid fa-key" style="color:var(--accent)"></i> Change Password</h3>
            <button type="button" onclick="closeChangePasswordModal()" class="btn light" style="padding:2px 8px;font-size:14px">&times;</button>
        </div>
        <form method="post" action="{{ route('change-password') }}">
            @csrf
            @if(!auth()->user()?->isAdmin() && auth()->user()?->password)
                <div style="margin-bottom:10px">
                    <label style="display:block;margin-bottom:4px;font-size:12px;font-weight:600">Current Password *</label>
                    <input type="password" name="current_password" required placeholder="Current password">
                </div>
            @endif
            <div style="margin-bottom:10px">
                <label style="display:block;margin-bottom:4px;font-size:12px;font-weight:600">New Password *</label>
                <input type="password" name="new_password" required minlength="6" placeholder="Min 6 characters">
            </div>
            <div style="margin-bottom:16px">
                <label style="display:block;margin-bottom:4px;font-size:12px;font-weight:600">Confirm New Password *</label>
                <input type="password" name="new_password_confirmation" required minlength="6" placeholder="Re-type new password">
            </div>
            <div style="display:flex;justify-content:flex-end;gap:8px">
                <button type="button" class="btn light" onclick="closeChangePasswordModal()">Cancel</button>
                <button type="submit" class="btn"><i class="fa-solid fa-check"></i> Save Password</button>
            </div>
        </form>
    </div>
</div>

<script>
function openChangePasswordModal() {
    var m = document.getElementById('changePasswordModal');
    if (m) m.style.display = 'flex';
}
function closeChangePasswordModal() {
    var m = document.getElementById('changePasswordModal');
    if (m) m.style.display = 'none';
}
function toggleUserDropdown(e) {
    if (e) e.stopPropagation();
    var dd = document.getElementById('userProfileDropdown');
    if (dd) {
        dd.style.display = (dd.style.display === 'none' || dd.style.display === '') ? 'block' : 'none';
    }
}
function hideUserDropdown() {
    var dd = document.getElementById('userProfileDropdown');
    if (dd) dd.style.display = 'none';
}
document.addEventListener('click', function(e) {
    var dd = document.getElementById('userProfileDropdown');
    var trigger = document.getElementById('userMenuTrigger');
    if (dd && dd.style.display === 'block') {
        if (trigger && (trigger.contains(e.target) || dd.contains(e.target))) {
            return;
        }
        dd.style.display = 'none';
    }
});
</script>
@yield('scripts')
</body>
</html>
