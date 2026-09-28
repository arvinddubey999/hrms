@extends('layouts.app')
@section('content')
<div class="page-head">
    <h1>Monthly Roster</h1>
    <form method="get" class="row">
        <select name="m" style="width:140px">@for($i=1;$i<=12;$i++)<option value="{{ $i }}" {{ $month==$i?'selected':'' }}>{{ date('F', mktime(0,0,0,$i,1)) }}</option>@endfor</select>
        <input type="number" name="y" value="{{ $year }}" style="width:100px">
        <button class="btn">Go</button>
    </form>
</div>
<div class="card" style="overflow:auto">
<table class="table">
    <thead>
    <tr>
        <th>Employee</th>
        @for($d=1;$d<=$days;$d++)<th>{{ $d }}</th>@endfor
    </tr>
    </thead>
    <tbody>
    @foreach($employees as $user)
        @php $sum = $attendance->monthSummary($user,$year,$month); @endphp
        <tr>
            <td>{{ $user->displayName() }}</td>
            @foreach($sum['rows'] as $row)
                <td>{{ $row['status']==='week_off'?'WO':($row['status']==='present'||$row['status']==='late'?'P':strtoupper(substr($row['status'],0,1))) }}</td>
            @endforeach
        </tr>
    @endforeach
    </tbody>
</table>
</div>
@endsection
