@extends('layouts.app')
@section('content')
<div class="page-head">
    <h1>Payslip List</h1>
    <div class="row">
        <form method="get" class="row">
            <select name="m" style="width:140px">@for($i=1;$i<=12;$i++)<option value="{{ $i }}" {{ $month==$i?'selected':'' }}>{{ date('F', mktime(0,0,0,$i,1)) }}</option>@endfor</select>
            <input type="number" name="y" value="{{ $year }}" style="width:100px">
            <button class="btn light">Go</button>
        </form>
        <a class="btn" href="{{ route('payroll.export', ['m'=>$month,'y'=>$year]) }}">Generate Payslip Report (Excel/CSV)</a>
    </div>
</div>
<div class="card">
    <table class="table">
        <thead><tr><th>Employee</th><th>Status</th><th>Designation</th><th>Pay Type</th><th>Present</th><th>Net</th><th></th></tr></thead>
        <tbody>
        @foreach($rows as $row)
            <tr>
                <td>{{ $row['user']->displayName() }}</td>
                <td>{{ $row['user']->status }}</td>
                <td>{{ $row['user']->designation }}</td>
                <td>{{ $row['user']->pay_type }}</td>
                <td>{{ $row['pay']['summary']['present'] }}</td>
                <td>₹{{ number_format($row['pay']['net'],2) }}</td>
                <td><a class="btn" href="{{ route('payroll.payslip', [$row['user'],'m'=>$month,'y'=>$year]) }}" target="_blank">Payslip</a></td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endsection
