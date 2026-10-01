@extends('layouts.app')
@section('content')
<div class="page-head" style="margin-bottom:16px">
    <h1><a href="{{ route('attendances.index') }}" style="color:inherit;text-decoration:none">←</a> Reports</h1>
</div>

<div class="card" style="padding:12px;border-radius:14px;background:#fff;margin-bottom:16px;border:1px solid #f3f4f6">
    <!-- 4 Main Navigation Tabs matching Screenshot 1 -->
    <div style="display:flex;gap:8px;border-bottom:1px solid #f3f4f6;padding-bottom:8px">
        <button type="button" class="tab-btn active" onclick="switchReportTab('attendance', this)" style="padding:8px 16px;border-radius:8px;font-weight:700;font-size:13px;border:0;background:#f3f4f6;color:#111;cursor:pointer">
            Attendance & Hours
        </button>
        <button type="button" class="tab-btn" onclick="switchReportTab('tracking', this)" style="padding:8px 16px;border-radius:8px;font-weight:700;font-size:13px;border:0;background:transparent;color:#6b7280;cursor:pointer">
            Field Tracking
        </button>
        <button type="button" class="tab-btn" onclick="switchReportTab('finance', this)" style="padding:8px 16px;border-radius:8px;font-weight:700;font-size:13px;border:0;background:transparent;color:#6b7280;cursor:pointer">
            Finance & Payroll
        </button>
        <button type="button" class="tab-btn" onclick="switchReportTab('master', this)" style="padding:8px 16px;border-radius:8px;font-weight:700;font-size:13px;border:0;background:transparent;color:#6b7280;cursor:pointer">
            HR & Master Data
        </button>
    </div>
</div>

<!-- TAB 1: ATTENDANCE & HOURS -->
<div id="tab-attendance" class="tab-pane">
    <div style="display:grid;grid-template-columns:repeat(auto-fill, minmax(310px, 1fr));gap:16px">
        <div class="report-card">
            <div class="report-icon"><i class="fa-regular fa-calendar-days"></i></div>
            <h3>Daywise Attendance Report</h3>
            <p>Generate day-by-day attendance reports with full in/out logs.</p>
            <button class="btn" onclick="openReportModal('daywise', 'Daywise Attendance Report', 'Generate Daywise Attendance Report')">
                <i class="fa-solid fa-sliders"></i> Generate
            </button>
        </div>

        <div class="report-card">
            <div class="report-icon"><i class="fa-solid fa-user-clock"></i></div>
            <h3>Daywise Attendance Report with multiple Punch in/out</h3>
            <p>View comprehensive punch details including multiple in/out timestamps.</p>
            <button class="btn" onclick="openReportModal('multi', 'Multiple Punch Attendance Report', 'Generate Multiple Punch Report')">
                <i class="fa-solid fa-sliders"></i> Generate
            </button>
        </div>

        <div class="report-card">
            <div class="report-icon"><i class="fa-regular fa-file-lines"></i></div>
            <h3>Overall Attendance Report</h3>
            <p>Get monthly summaries or custom range reports on employee attendance.</p>
            <button class="btn" onclick="openReportModal('overall', 'Overall Attendance Report', 'Generate Overall Report')">
                <i class="fa-solid fa-sliders"></i> Generate
            </button>
        </div>

        <div class="report-card">
            <div class="report-icon"><i class="fa-solid fa-file-contract"></i></div>
            <h3>Detailed Attendance Report</h3>
            <p>Download in-depth attendance trackers with locations and shift details.</p>
            <button class="btn" onclick="openReportModal('detailed', 'Detailed Attendance Tracker', 'Generate Detailed Report')">
                <i class="fa-solid fa-sliders"></i> Generate
            </button>
        </div>

        <div class="report-card">
            <div class="report-icon"><i class="fa-regular fa-clock"></i></div>
            <h3>Working Hours Report</h3>
            <p>Analyze total actual hours worked by employees across departments.</p>
            <button class="btn" onclick="openReportModal('hours', 'Working Hours Report', 'Generate Working Hours Report')">
                <i class="fa-solid fa-sliders"></i> Generate
            </button>
        </div>

        <div class="report-card">
            <div class="report-icon"><i class="fa-regular fa-clock"></i></div>
            <h3>Overtime Hours Report</h3>
            <p>Track extra hours put in by employees beyond shift timings.</p>
            <button class="btn" onclick="openReportModal('overtime', 'Overtime Hours Report', 'Generate Overtime Report')">
                <i class="fa-solid fa-sliders"></i> Generate
            </button>
        </div>

        <div class="report-card">
            <div class="report-icon"><i class="fa-solid fa-file-zipper"></i></div>
            <h3>Monthly Summary Sheet Download</h3>
            <p>Download individual monthly employee attendance sheets in a ZIP folder.</p>
            <button class="btn" onclick="openReportModal('monthly_zip', 'Monthly Summary Sheet Download', 'Generate Attendance Sheets')">
                <i class="fa-solid fa-sliders"></i> Generate
            </button>
        </div>
    </div>
</div>

<!-- TAB 2: FIELD TRACKING -->
<div id="tab-tracking" class="tab-pane" style="display:none">
    <div style="display:grid;grid-template-columns:repeat(auto-fill, minmax(310px, 1fr));gap:16px">
        <div class="report-card">
            <div class="report-icon"><i class="fa-solid fa-list-check"></i></div>
            <h3>Task Report</h3>
            <p>Download detailed task completion and progress reports.</p>
            <button class="btn" onclick="openReportModal('tasks', 'Generate Task Report', 'Generate Task Report')">
                <i class="fa-solid fa-sliders"></i> Generate
            </button>
        </div>

        <div class="report-card">
            <div class="report-icon"><i class="fa-solid fa-gauge"></i></div>
            <h3>Odometer Report</h3>
            <p>Track travel distances and odometer logs submitted by field staff.</p>
            <button class="btn" onclick="openReportModal('odometer', 'Odometer Travel Report', 'Generate Odometer Report')">
                <i class="fa-solid fa-sliders"></i> Generate
            </button>
        </div>

        <div class="report-card">
            <div class="report-icon"><i class="fa-solid fa-location-dot"></i></div>
            <h3>Visits Report</h3>
            <p>Export client visits, geotagged locations, and meeting summaries.</p>
            <button class="btn" onclick="openReportModal('visits', 'Client Visits Report', 'Generate Visits Report')">
                <i class="fa-solid fa-sliders"></i> Generate
            </button>
        </div>

        <div class="report-card">
            <div class="report-icon"><i class="fa-solid fa-route"></i></div>
            <h3>Live Tracking History</h3>
            <p>View historical GPS movement timeline logs for field employees.</p>
            <button class="btn" onclick="openReportModal('tracking', 'Live Tracking History Report', 'Generate Tracking Report')">
                <i class="fa-solid fa-sliders"></i> Generate
            </button>
        </div>
    </div>
</div>

<!-- TAB 3: FINANCE & PAYROLL -->
<div id="tab-finance" class="tab-pane" style="display:none">
    <div style="display:grid;grid-template-columns:repeat(auto-fill, minmax(310px, 1fr));gap:16px">
        <div class="report-card">
            <div class="report-icon"><i class="fa-solid fa-money-bill-wave"></i></div>
            <h3>Salary Sheet Report</h3>
            <p>Export monthly salary calculations, net payables, and deductions.</p>
            <button class="btn" onclick="openReportModal('salary', 'Salary Sheet Report', 'Generate Salary Sheet')">
                <i class="fa-solid fa-sliders"></i> Generate
            </button>
        </div>

        <div class="report-card">
            <div class="report-icon"><i class="fa-solid fa-file-invoice-dollar"></i></div>
            <h3>Payslip Bulk Download</h3>
            <p>Download generated payslips for all staff members in bulk.</p>
            <button class="btn" onclick="openReportModal('payslips', 'Payslip Bulk Download', 'Generate Payslips')">
                <i class="fa-solid fa-sliders"></i> Generate
            </button>
        </div>

        <div class="report-card">
            <div class="report-icon"><i class="fa-solid fa-hand-holding-dollar"></i></div>
            <h3>Advance & Incentive Report</h3>
            <p>Review total salary advances disbursed and earned performance incentives.</p>
            <button class="btn" onclick="openReportModal('advance_incentive', 'Advance & Incentive Report', 'Generate Advance Report')">
                <i class="fa-solid fa-sliders"></i> Generate
            </button>
        </div>

        <div class="report-card">
            <div class="report-icon"><i class="fa-solid fa-receipt"></i></div>
            <h3>Expense Claims Report</h3>
            <p>Export employee expense reimbursement claims with status and receipts.</p>
            <button class="btn" onclick="openReportModal('expenses', 'Expense Claims Report', 'Generate Expense Report')">
                <i class="fa-solid fa-sliders"></i> Generate
            </button>
        </div>
    </div>
</div>

<!-- TAB 4: HR & MASTER DATA -->
<div id="tab-master" class="tab-pane" style="display:none">
    <div style="display:grid;grid-template-columns:repeat(auto-fill, minmax(310px, 1fr));gap:16px">
        <div class="report-card">
            <div class="report-icon"><i class="fa-solid fa-id-card"></i></div>
            <h3>Employee Master Report</h3>
            <p>Download full employee database with code, department, salary, and status.</p>
            <a href="{{ route('reports.generate', ['type'=>'master_employees', 'format'=>'excel']) }}" class="btn" target="_blank">
                <i class="fa-solid fa-download"></i> Direct Download Excel
            </a>
        </div>

        <div class="report-card">
            <div class="report-icon"><i class="fa-solid fa-sitemap"></i></div>
            <h3>Department List Report</h3>
            <p>Export department structure and staff count details.</p>
            <a href="{{ route('reports.generate', ['type'=>'master_departments', 'format'=>'excel']) }}" class="btn" target="_blank">
                <i class="fa-solid fa-download"></i> Direct Download Excel
            </a>
        </div>

        <div class="report-card">
            <div class="report-icon"><i class="fa-solid fa-building"></i></div>
            <h3>Company List Report</h3>
            <p>Export registered companies and office location coordinates.</p>
            <a href="{{ route('reports.generate', ['type'=>'master_companies', 'format'=>'excel']) }}" class="btn" target="_blank">
                <i class="fa-solid fa-download"></i> Direct Download Excel
            </a>
        </div>

        <div class="report-card">
            <div class="report-icon"><i class="fa-solid fa-business-time"></i></div>
            <h3>Shift Master Report</h3>
            <p>Export work shift timings and grace period configurations.</p>
            <a href="{{ route('reports.generate', ['type'=>'master_shifts', 'format'=>'excel']) }}" class="btn" target="_blank">
                <i class="fa-solid fa-download"></i> Direct Download Excel
            </a>
        </div>
    </div>
</div>

<!-- GENERATE REPORTS POPUP MODAL matching Screenshot 2 & Screenshot 4 -->
<div id="reportModal" class="modal-bg">
    <div class="modal" style="max-width:820px;padding:24px;border-radius:18px;background:#fff">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;border-bottom:1px solid #f3f4f6;padding-bottom:10px">
            <h2 id="reportModalTitle" style="margin:0;font-size:18px;font-weight:700">Generate Reports</h2>
            <button type="button" class="btn light" onclick="closeReportModal()" style="font-size:16px;padding:4px 10px">&times;</button>
        </div>

        <form method="get" action="{{ route('reports.generate') }}" target="_blank">
            <input type="hidden" name="type" id="reportTypeInput" value="daywise">

            <div style="display:grid;grid-template-columns:260px 1fr 1fr;gap:16px;align-items:flex-start">
                
                <!-- LEFT BOX: Date Selection Options -->
                <div style="background:#fafafa;padding:14px;border-radius:12px;border:1px solid #e5e7eb">
                    <div style="display:flex;flex-direction:column;gap:10px;margin-bottom:14px">
                        <label style="display:flex;align-items:center;gap:8px;font-size:13px;font-weight:600;cursor:pointer">
                            <input type="radio" name="range_type" value="single" onchange="switchDateSelection(this.value)"> Single Date Selection
                        </label>
                        <label style="display:flex;align-items:center;gap:8px;font-size:13px;font-weight:600;cursor:pointer">
                            <input type="radio" name="range_type" value="month" checked onchange="switchDateSelection(this.value)"> Month Selection
                        </label>
                        <label style="display:flex;align-items:center;gap:8px;font-size:13px;font-weight:600;cursor:pointer">
                            <input type="radio" name="range_type" value="custom" onchange="switchDateSelection(this.value)"> Custom Date Range
                        </label>
                    </div>

                    <!-- Single Date Picker -->
                    <div id="singleDateBox" style="display:none">
                        <label style="font-size:12px;font-weight:600;color:#6b7280;margin-bottom:4px;display:block">Select Date</label>
                        <input type="date" name="date" value="{{ now()->toDateString() }}">
                    </div>

                    <!-- Month Selection Pickers -->
                    <div id="monthDateBox">
                        <label style="font-size:12px;font-weight:600;color:#6b7280;margin-bottom:4px;display:block">Select Month & Year</label>
                        <select name="month" style="margin-bottom:8px">
                            @for($m=1;$m<=12;$m++)
                                <option value="{{ $m }}" {{ now()->month==$m?'selected':'' }}>{{ date('F', mktime(0,0,0,$m,1)) }}</option>
                            @endfor
                        </select>
                        <select name="year">
                            @for($y=2024;$y<=2030;$y++)
                                <option value="{{ $y }}" {{ now()->year==$y?'selected':'' }}>{{ $y }}</option>
                            @endfor
                        </select>
                    </div>

                    <!-- Custom Range Pickers -->
                    <div id="customDateBox" style="display:none">
                        <label style="font-size:12px;font-weight:600;color:#6b7280;margin-bottom:4px;display:block">From Date</label>
                        <input type="date" name="from" value="{{ now()->startOfMonth()->toDateString() }}" style="margin-bottom:8px">
                        <label style="font-size:12px;font-weight:600;color:#6b7280;margin-bottom:4px;display:block">To Date</label>
                        <input type="date" name="to" value="{{ now()->toDateString() }}">
                    </div>
                </div>

                <!-- MIDDLE BOX: Select Category Checklist -->
                <div style="border:1px solid #e5e7eb;border-radius:12px;padding:12px;background:#fff">
                    <label style="font-size:13px;font-weight:700;color:#111827;margin-bottom:8px;display:block">Select Category :</label>
                    <input type="text" id="catSearchInput" placeholder="Search category..." onkeyup="filterCategoriesList()" style="font-size:12px;padding:6px 10px;margin-bottom:8px">
                    
                    <div style="max-height:180px;overflow-y:auto" id="catChecklist">
                        <label style="display:flex;align-items:center;gap:8px;font-size:12px;padding:4px 0;border-bottom:1px solid #f3f4f6;font-weight:600">
                            <input type="checkbox" id="selectAllCatCheckbox" onchange="toggleSelectAllCategories(this)" checked> Select All Categories
                        </label>
                        @foreach($categories as $cat)
                            <label class="cat-item" style="display:flex;align-items:center;gap:8px;font-size:12px;padding:4px 0;color:#374151">
                                <input type="checkbox" name="category_ids[]" value="{{ $cat->id }}" class="cat-cb" checked> {{ $cat->name }}
                            </label>
                        @endforeach
                    </div>
                </div>

                <!-- RIGHT BOX: Select Employee Checklist -->
                <div style="border:1px solid #e5e7eb;border-radius:12px;padding:12px;background:#fff">
                    <label style="font-size:13px;font-weight:700;color:#111827;margin-bottom:8px;display:block">Select Employee :</label>
                    <input type="text" id="empSearchInput" placeholder="Search employee..." onkeyup="filterEmployeesList()" style="font-size:12px;padding:6px 10px;margin-bottom:8px">
                    
                    <div style="max-height:180px;overflow-y:auto" id="empChecklist">
                        <label style="display:flex;align-items:center;gap:8px;font-size:12px;padding:4px 0;border-bottom:1px solid #f3f4f6;font-weight:600">
                            <input type="checkbox" id="selectAllEmpCheckbox" onchange="toggleSelectAllEmployees(this)" checked> Select All ({{ $employees->count() }}) Employees
                        </label>
                        @foreach($employees as $emp)
                            <label class="emp-item" style="display:flex;align-items:center;gap:8px;font-size:12px;padding:4px 0;color:#374151">
                                <input type="checkbox" name="employee_ids[]" value="{{ $emp->id }}" class="emp-cb" checked> {{ $emp->displayName() }}
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- BOTTOM BAR: Export Format & Submit Button -->
            <div style="margin-top:20px;padding-top:14px;border-top:1px solid #f3f4f6;display:flex;justify-space-between;align-items:center">
                <div style="display:flex;align-items:center;gap:16px;background:#fafafa;padding:8px 16px;border-radius:10px;border:1px dashed #d1d5db">
                    <span style="font-size:13px;font-weight:700;color:#374151">Export Format :</span>
                    <label style="display:inline-flex;align-items:center;gap:6px;font-size:13px;font-weight:600;cursor:pointer">
                        <input type="radio" name="format" value="pdf"> PDF
                    </label>
                    <label style="display:inline-flex;align-items:center;gap:6px;font-size:13px;font-weight:600;cursor:pointer">
                        <input type="radio" name="format" value="excel" checked> Excel
                    </label>
                </div>

                <button id="reportSubmitBtn" class="btn" style="background:#111;color:#fff;padding:10px 20px;border-radius:10px;font-weight:700;font-size:13px">
                    Generate Report
                </button>
            </div>
        </form>
    </div>
</div>

<style>
.report-card {
    background: #fff;
    border: 1px dashed #d1d5db;
    border-radius: 14px;
    padding: 20px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    transition: all 0.2s ease;
}
.report-card:hover {
    border-color: #0284c7;
    box-shadow: 0 4px 14px rgba(0,0,0,0.06);
}
.report-icon {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    background: #ffedd5;
    color: #ea580c;
    display: grid;
    place-items: center;
    font-size: 16px;
    margin-bottom: 12px;
}
.report-card h3 {
    margin: 0 0 6px 0;
    font-size: 15px;
    font-weight: 700;
    color: #111827;
}
.report-card p {
    margin: 0 0 16px 0;
    font-size: 12px;
    color: #6b7280;
    line-height: 1.5;
}
.report-card .btn {
    align-self: flex-end;
    background: #1f2937;
    color: #fff;
    border-radius: 8px;
    padding: 8px 16px;
    font-size: 12px;
    font-weight: 600;
}
</style>
@endsection

@section('scripts')
<script>
function switchReportTab(tabName, btn) {
    document.querySelectorAll('.tab-btn').forEach(b => {
        b.style.background = 'transparent';
        b.style.color = '#6b7280';
    });
    btn.style.background = '#f3f4f6';
    btn.style.color = '#111';

    document.querySelectorAll('.tab-pane').forEach(p => p.style.display = 'none');
    document.getElementById('tab-' + tabName).style.display = 'block';
}

function openReportModal(type, title, btnLabel) {
    document.getElementById('reportTypeInput').value = type;
    document.getElementById('reportModalTitle').innerText = title;
    document.getElementById('reportSubmitBtn').innerText = btnLabel || 'Generate Report';
    document.getElementById('reportModal').classList.add('open');
}

function closeReportModal() {
    document.getElementById('reportModal').classList.remove('open');
}

function switchDateSelection(val) {
    document.getElementById('singleDateBox').style.display = (val === 'single') ? 'block' : 'none';
    document.getElementById('monthDateBox').style.display = (val === 'month') ? 'block' : 'none';
    document.getElementById('customDateBox').style.display = (val === 'custom') ? 'block' : 'none';
}

function filterCategoriesList() {
    const q = document.getElementById('catSearchInput').value.toLowerCase();
    document.querySelectorAll('.cat-item').forEach(item => {
        const txt = item.innerText.toLowerCase();
        item.style.display = txt.includes(q) ? 'flex' : 'none';
    });
}

function filterEmployeesList() {
    const q = document.getElementById('empSearchInput').value.toLowerCase();
    document.querySelectorAll('.emp-item').forEach(item => {
        const txt = item.innerText.toLowerCase();
        item.style.display = txt.includes(q) ? 'flex' : 'none';
    });
}

function toggleSelectAllCategories(master) {
    document.querySelectorAll('.cat-cb').forEach(cb => cb.checked = master.checked);
}

function toggleSelectAllEmployees(master) {
    document.querySelectorAll('.emp-cb').forEach(cb => cb.checked = master.checked);
}
</script>
@endsection
