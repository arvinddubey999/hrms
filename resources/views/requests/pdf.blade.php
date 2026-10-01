<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Leave Request Form - {{ $req->user?->displayName() }}</title>
    <style>
        body { font-family: sans-serif; font-size: 13px; margin: 30px; line-height: 1.6; }
        .header { text-align: center; border-bottom: 2px solid #111; padding-bottom: 10px; margin-bottom: 20px; }
        .box { border: 1px solid #ccc; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .status { padding: 4px 10px; font-weight: bold; border-radius: 4px; display: inline-block; text-transform: uppercase; }
        .approved { background: #dcfce7; color: #166534; }
        .pending { background: #fef3c7; color: #92400e; }
        .rejected { background: #fee2e2; color: #991b1b; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom:15px;text-align:right">
        <button onclick="window.print()">Print / Download PDF</button>
    </div>

    <div class="header">
        <h2>{{ \App\Models\Setting::current()->company_name }}</h2>
        <h3>LEAVE REQUEST APPLICATION FORM</h3>
        <p>Ref #: LR-{{ sprintf('%05d', $req->id) }} | Date: {{ $req->created_at->format('d M Y') }}</p>
    </div>

    <div class="box">
        <h4>Employee Information</h4>
        <div class="grid">
            <div>Employee Name: <b>{{ $req->user?->displayName() }}</b></div>
            <div>Employee Code: <b>{{ $req->user?->employee_code }}</b></div>
            <div>Department: <b>{{ $req->user?->department ?: '-' }}</b></div>
            <div>Designation: <b>{{ $req->user?->designation ?: '-' }}</b></div>
            <div>Phone: <b>{{ $req->user?->phone }}</b></div>
            <div>Company: <b>{{ $req->user?->company?->name ?? \App\Models\Setting::current()->company_name }}</b></div>
        </div>
    </div>

    <div class="box">
        <h4>Leave Application Details</h4>
        <div class="grid">
            <div>Leave Type: <b>{{ $req->leave_type }}</b></div>
            <div>Status: <span class="status {{ $req->status }}">{{ $req->status }}</span></div>
            <div>From Date: <b>{{ $req->from_date->format('d F Y') }}</b></div>
            <div>To Date: <b>{{ $req->to_date->format('d F Y') }}</b></div>
        </div>
        <div style="margin-top:12px">
            <div>Reason / Remarks:</div>
            <p style="background:#f9fafb;padding:10px;border-radius:6px;border:1px solid #eee">
                {{ $req->reason ?: 'No reason specified.' }}
            </p>
        </div>
    </div>

    <div style="margin-top:40px;display:flex;justify-content:space-between">
        <div style="text-align:center">
            <br><br>
            _______________________<br>
            Employee Signature
        </div>
        <div style="text-align:center">
            <br><br>
            _______________________<br>
            Authorized Approver Signature
        </div>
    </div>
</body>
</html>
