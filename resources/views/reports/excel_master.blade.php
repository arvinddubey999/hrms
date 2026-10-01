<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        table { border-collapse: collapse; width: 100%; font-family: Calibri, sans-serif; font-size: 11pt; }
        th, td { border: 1px solid #d4d4d4; padding: 6px 10px; text-align: left; }
        .company-header { font-size: 16pt; font-weight: bold; text-align: center; background: #ffffff; color: #111827; }
        .sub-header { font-size: 12pt; font-style: italic; text-align: center; background: #ffffff; color: #4b5563; }
        .col-header { background: #fef08a; font-weight: bold; text-transform: uppercase; font-size: 10pt; color: #111827; text-align: center; }
    </style>
</head>
<body>
    <table>
        <tr>
            <td colspan="10" class="company-header">{{ $companyName }}</td>
        </tr>
        <tr>
            <td colspan="10" class="sub-header">HR & Master Data Report (Generated: {{ date('d M Y h:i A') }})</td>
        </tr>
        <tr><td colspan="10"></td></tr>
        <tr class="col-header">
            <td>EMPLOYEE ID</td>
            <td>NAME</td>
            <td>DESIGNATION</td>
            <td>CATEGORY</td>
            <td>DEPARTMENT</td>
            <td>PHONE</td>
            <td>EMAIL</td>
            <td>DATE OF JOINING</td>
            <td>MONTHLY SALARY</td>
            <td>STATUS</td>
        </tr>
        @foreach($employees as $emp)
            <tr>
                <td style="text-align:center"><code>{{ $emp->employee_code ?: sprintf('%05d', $emp->id) }}</code></td>
                <td><b>{{ $emp->displayName() }}</b></td>
                <td>{{ $emp->designation ?: '-' }}</td>
                <td>{{ $emp->category->name ?? 'Unassigned' }}</td>
                <td>{{ $emp->department ?: '-' }}</td>
                <td>{{ $emp->phone }}</td>
                <td>{{ $emp->email ?: '-' }}</td>
                <td style="text-align:center">{{ optional($emp->date_of_joining)->format('d-m-Y') ?: '-' }}</td>
                <td style="text-align:right">₹{{ number_format($emp->salary, 2) }}</td>
                <td style="text-align:center"><code>{{ strtoupper($emp->status) }}</code></td>
            </tr>
        @endforeach
    </table>
</body>
</html>
