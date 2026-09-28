<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>ADCodeNexus. Login</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
<div class="login-wrap">
    <form class="login-card" method="post" action="{{ route('login') }}">
        @csrf
        <div class="brand" style="color:#111;margin-bottom:8px"><a href="https://adcodenexus.com" target="_blank">ADCodeNexus.<span></span></a></div>
        <p class="muted">Admin panel for attendance, payroll and tasks</p>
        @if($errors->any())<div class="flash" style="background:#fee2e2;color:#991b1b">{{ $errors->first() }}</div>@endif
        <label>Phone or email</label>
        <input name="login" value="{{ old('login', '7375010611') }}" required>
        <label>Password</label>
        <input type="password" name="password" value="password" required>
        <button class="btn" style="width:100%;margin-top:16px">Sign in</button>
        <p class="muted" style="margin-top:14px">Demo admin: 7375010611 / password<br>Staff app login: 9000000001 / password (Arvind)</p>
    </form>
</div>
</body>
</html>
