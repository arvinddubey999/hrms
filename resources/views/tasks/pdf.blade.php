<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Task Management Report</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; margin: 20px; }
        h1 { text-align: center; font-size: 18px; }
        .table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .table th, .table td { border: 1px solid #ccc; padding: 6px 8px; text-align: left; }
        .table th { background: #f3f4f6; }
        .badge { padding: 2px 6px; font-weight: bold; border-radius: 4px; font-size: 10px; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom:10px;text-align:right">
        <button onclick="window.print()">Print / Download PDF</button>
    </div>
    <h1>Task Management Report</h1>
    <p style="text-align:center">Generated on {{ date('d M Y h:i A') }}</p>

    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Task Title</th>
                <th>Department</th>
                <th>Assigned To</th>
                <th>Priority</th>
                <th>Status</th>
                <th>Due Date</th>
            </tr>
        </thead>
        <tbody>
            @foreach($tasks as $t)
                <tr>
                    <td>#{{ $t->id }}</td>
                    <td><b>{{ $t->title }}</b></td>
                    <td>{{ $t->department->name ?? '-' }}</td>
                    <td>{{ $t->assignee?->displayName() ?? 'Unassigned' }}</td>
                    <td>{{ strtoupper($t->priority) }}</td>
                    <td>{{ strtoupper($t->status) }}</td>
                    <td>{{ $t->due_date ? $t->due_date->format('Y-m-d') : '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
