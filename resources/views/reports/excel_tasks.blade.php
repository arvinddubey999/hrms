<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        table { border-collapse: collapse; width: 100%; font-family: Calibri, sans-serif; font-size: 11pt; }
        th, td { border: 1px solid #d4d4d4; padding: 6px 10px; text-align: left; }
        .company-header { font-size: 16pt; font-weight: bold; text-align: center; background: #ffffff; color: #111827; }
        .sub-header { font-size: 12pt; font-style: italic; text-align: center; background: #ffffff; color: #4b5563; }
        .col-header { background: #0284c7; color: #ffffff; font-weight: bold; text-transform: uppercase; font-size: 10pt; text-align: center; }
        .badge-pending { background: #fee2e2; color: #991b1b; font-weight: bold; text-align: center; }
        .badge-progress { background: #fef3c7; color: #92400e; font-weight: bold; text-align: center; }
        .badge-completed { background: #dcfce7; color: #166534; font-weight: bold; text-align: center; }
    </style>
</head>
<body>
    <table>
        <tr>
            <td colspan="11" class="company-header">{{ $companyName }}</td>
        </tr>
        <tr>
            <td colspan="11" class="sub-header">Task Management Report ({{ $from->format('d M Y') }} - {{ $to->format('d M Y') }})</td>
        </tr>
        <tr><td colspan="11"></td></tr>
        <tr class="col-header">
            <td>TASK ID</td>
            <td>TASK TITLE</td>
            <td>DESCRIPTION</td>
            <td>CLIENT</td>
            <td>DEPARTMENT</td>
            <td>ASSIGNED TO</td>
            <td>PRIORITY</td>
            <td>STATUS</td>
            <td>DUE DATE</td>
            <td>DUE TIME</td>
            <td>CREATED AT</td>
        </tr>
        @foreach($tasks as $t)
            <tr>
                <td style="text-align:center">#{{ $t->id }}</td>
                <td><b>{{ $t->title }}</b></td>
                <td>{{ $t->description ?: '-' }}</td>
                <td>{{ $t->client ?: '-' }}</td>
                <td>{{ $t->department->name ?? 'General' }}</td>
                <td>{{ $t->assignee?->displayName() ?? 'Unassigned' }}</td>
                <td style="text-align:center">{{ strtoupper($t->priority) }}</td>
                <td class="badge-{{ $t->status }}">{{ strtoupper(str_replace('_',' ',$t->status)) }}</td>
                <td style="text-align:center">{{ optional($t->due_date)->format('d-m-Y') ?: '-' }}</td>
                <td style="text-align:center">{{ $t->due_time ?: '12:00' }}</td>
                <td style="text-align:center">{{ $t->created_at ? $t->created_at->format('d-m-Y H:i') : '-' }}</td>
            </tr>
        @endforeach
    </table>
</body>
</html>
