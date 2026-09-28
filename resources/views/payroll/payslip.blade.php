<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Payslip</title>
    <style>
        body{font-family:Arial,sans-serif;padding:32px;max-width:900px;margin:auto}
        h2{margin:0}
        .box{border:1px solid #ddd;border-radius:12px;padding:16px;margin:12px 0}
        .grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}
        table{width:100%;border-collapse:collapse}
        td{padding:6px 0}
        .net{border:1px solid #86efac;padding:14px;border-radius:10px}
    </style>
</head>
<body>
@php $c = \App\Models\Setting::current(); @endphp
<div style="display:flex;justify-content:space-between">
    <div>
        <div style="font-weight:800">ADCodeNexus</div>
        <h2>{{ $c->company_name }}</h2>
        <div>{{ $c->company_address }}</div>
    </div>
    <div>Payslip For the Month<br><b>{{ date('F', mktime(0,0,0,$pay['month'],1)) }} {{ $pay['year'] }}</b></div>
</div>
<div class="grid">
    <div class="box">
        <h3>EMPLOYEE SUMMARY</h3>
        <p>Employee Name : {{ $staff->displayName() }}<br>
        Designation : {{ $staff->designation }}<br>
        Employee Code : {{ $staff->employee_code }}<br>
        Date of joining : {{ optional($staff->date_of_joining)->format('F j, Y') }}<br>
        Pay Period : {{ date('F', mktime(0,0,0,$pay['month'],1)) }} {{ $pay['year'] }}<br>
        Pan No : {{ $staff->pan }}<br>
        PF No : {{ $staff->pf_number }}<br>
        UAN : {{ $staff->uan }}</p>
    </div>
    <div class="box">
        <h3>ATTENDANCE SUMMARY</h3>
        <p>Total Days : {{ $pay['summary']['days'] }}<br>
        Present Days : {{ $pay['summary']['present'] }}<br>
        Absent Days : {{ $pay['summary']['absent'] }}<br>
        Leave Days : {{ $pay['summary']['leave'] }}<br>
        Week Off Days : {{ $pay['summary']['weekOff'] }}<br>
        Payable Days : {{ $pay['payable_days'] }}</p>
    </div>
</div>
<div class="grid box">
    <div>
        <h3>EARNINGS</h3>
        <table>
            <tr><td>Regular Earnings</td><td>₹{{ number_format($pay['basic'],2) }}</td></tr>
            <tr><td>Incentives</td><td>₹{{ number_format($pay['incentive_total'],2) }}</td></tr>
            <tr><td><b>Gross Earnings</b></td><td><b>₹{{ number_format($pay['gross'],2) }}</b></td></tr>
        </table>
    </div>
    <div>
        <h3>DEDUCTIONS</h3>
        <table>
            @foreach($pay['advances'] as $a)
                <tr><td>Payments Made : [{{ $a->paid_on->toDateString() }}]</td><td>₹{{ number_format($a->amount,2) }}</td></tr>
            @endforeach
            <tr><td><b>Total Deductions</b></td><td><b>₹{{ number_format($pay['advance_total'],2) }}</b></td></tr>
        </table>
    </div>
</div>
<div class="net">
    <b>TOTAL NET PAYABLE</b>
    <span style="float:right;font-size:22px">₹{{ number_format($pay['net'],2) }}</span>
    <div>Gross Earnings - Total Deductions</div>
</div>
<p style="text-align:center"><b>Amount In Words:</b> {{ $words }}</p>
</body>
</html>
