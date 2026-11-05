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
        <!-- <button type="button" class="tab-btn" onclick="switchReportTab('tracking', this)" style="padding:8px 16px;border-radius:8px;font-weight:700;font-size:13px;border:0;background:transparent;color:#6b7280;cursor:pointer">
            Field Tracking
        </button>
        <button type="button" class="tab-btn" onclick="switchReportTab('finance', this)" style="padding:8px 16px;border-radius:8px;font-weight:700;font-size:13px;border:0;background:transparent;color:#6b7280;cursor:pointer">
            Finance & Payroll
        </button>
        <button type="button" class="tab-btn" onclick="switchReportTab('master', this)" style="padding:8px 16px;border-radius:8px;font-weight:700;font-size:13px;border:0;background:transparent;color:#6b7280;cursor:pointer">
            HR & Master Data
        </button> -->
    </div>
</div>

<!-- TAB 1: ATTENDANCE & HOURS -->
<div id="tab-attendance" class="tab-pane">
    <div style="display:grid;grid-template-columns:repeat(auto-fill, minmax(310px, 1fr));gap:16px">
        <!-- <div class="report-card">
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
        </div> -->

        <div class="report-card">
            <div class="report-icon"><i class="fa-solid fa-file-contract"></i></div>
            <h3>Detailed Attendance Report</h3>
            <p>Download in-depth attendance trackers with locations and shift details.</p>
            <button class="btn" onclick="openReportModal('detailed', 'Detailed Attendance Tracker', 'Generate Detailed Report')">
                <i class="fa-solid fa-sliders"></i> Generate
            </button>
        </div>

        <!--
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
        </div> -->

        <div class="report-card" style="border:2px solid #bbf7d0;background:#f0fdf4">
            <div class="report-icon" style="background:#16a34a;color:#fff"><i class="fa-solid fa-file-invoice-dollar"></i></div>
            <h3 style="color:#166534">Monthly Payroll Summary Register</h3>
            <p>Generate department-wise monthly payroll register with P, A, WO, WOP, H, HOP, Total PR.Days, Salary Rate, Gross Salary &amp; Net Payable.</p>
            <button class="btn" style="background:#166534" onclick="openReportModal('payroll_summary_register', 'Monthly Payroll Summary Register', 'Generate Payroll Register')">
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
        <div class="report-card" style="border:2px solid #bbf7d0;background:#f0fdf4">
            <div class="report-icon" style="background:#16a34a;color:#fff"><i class="fa-solid fa-file-invoice-dollar"></i></div>
            <h3 style="color:#166534">Monthly Payroll Summary Register</h3>
            <p>Generate department-wise monthly payroll register with P, A, WO, WOP, H, HOP, Total PR.Days, Salary Rate, Gross Salary &amp; Net Payable.</p>
            <button class="btn" style="background:#166534" onclick="openReportModal('payroll_summary_register', 'Monthly Payroll Summary Register', 'Generate Payroll Register')">
                <i class="fa-solid fa-sliders"></i> Generate
            </button>
        </div>

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

<!-- GENERATE REPORTS POPUP MODAL matching Client Requirements -->
<div id="reportModal" class="modal-bg">
    <div class="modal" style="width:1100px !important;max-width:96vw !important;padding:22px 24px;border-radius:18px;background:#fff;box-shadow:0 25px 50px -12px rgba(0,0,0,0.25);box-sizing:border-box">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;border-bottom:1px solid #f1f5f9;padding-bottom:10px">
            <div style="display:flex;align-items:center;gap:12px">
                <div style="width:38px;height:38px;border-radius:10px;background:#e0f2fe;color:#0284c7;display:grid;place-items:center;font-size:17px;flex-shrink:0">
                    <i class="fa-solid fa-file-invoice-dollar"></i>
                </div>
                <div>
                    <h2 id="reportModalTitle" style="margin:0;font-size:17px;font-weight:700;color:#0f172a">Monthly Payroll Summary Register</h2>
                    <p style="margin:2px 0 0;font-size:12px;color:#64748b">Filter by Company, Department, Category, and Date Range to generate report.</p>
                </div>
            </div>
            <button type="button" class="btn light" onclick="closeReportModal()" style="font-size:18px;padding:0;border-radius:50%;width:32px;height:32px;display:grid;place-items:center;border:none;background:#f1f5f9;color:#64748b;cursor:pointer">&times;</button>
        </div>

        <form method="get" action="{{ route('reports.generate') }}" target="_blank">
            <input type="hidden" name="type" id="reportTypeInput" value="daywise">

            <!-- Company & Department Filter Dropdowns Requested by Client -->
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px;background:#f8fafc;padding:10px 14px;border-radius:12px;border:1px solid #e2e8f0">
                <div>
                    <label style="font-size:12px;font-weight:700;color:#334155;margin-bottom:5px;display:flex;align-items:center;gap:6px">
                        <i class="fa-solid fa-building" style="color:#0284c7"></i> Select Company
                    </label>
                    <select name="company_id" id="reportCompanySelect" onchange="syncEmployeesByFilters()" style="width:100%;font-size:12.5px;padding:7px 10px;border-radius:8px;border:1px solid #cbd5e1;background:#fff;color:#1e293b;outline:none;font-weight:500;box-sizing:border-box">
                        <option value="">All Companies (All Staff)</option>
                        @foreach($companies as $comp)
                            <option value="{{ $comp->id }}">{{ $comp->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="font-size:12px;font-weight:700;color:#334155;margin-bottom:5px;display:flex;align-items:center;gap:6px">
                        <i class="fa-solid fa-sitemap" style="color:#10b981"></i> Select Department
                    </label>
                    <select name="department_id" id="reportDepartmentSelect" onchange="syncEmployeesByFilters()" style="width:100%;font-size:12.5px;padding:7px 10px;border-radius:8px;border:1px solid #cbd5e1;background:#fff;color:#1e293b;outline:none;font-weight:500;box-sizing:border-box">
                        <option value="">All Departments (All Staff)</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div style="display:grid;grid-template-columns:260px 260px minmax(0, 1fr);gap:14px;align-items:stretch">
                
                <!-- LEFT BOX: Date Selection Options -->
                <div style="background:#fafafa;padding:12px 14px;border-radius:12px;border:1px solid #e2e8f0;display:flex;flex-direction:column;box-sizing:border-box">
                    <div style="font-size:13px;font-weight:700;color:#1e293b;margin-bottom:10px;display:flex;align-items:center;gap:6px">
                        <i class="fa-regular fa-calendar-days" style="color:#6366f1"></i> Date &amp; Period
                    </div>

                    <div style="display:flex;flex-direction:column;gap:8px;margin-bottom:12px">
                        <label style="display:flex;align-items:center;gap:8px;font-size:12.5px;font-weight:600;cursor:pointer;padding:6px 10px;background:#fff;border-radius:8px;border:1px solid #e2e8f0;margin:0">
                            <input type="radio" name="range_type" value="single" onchange="switchDateSelection(this.value)">
                            <span>Single Date Selection</span>
                        </label>
                        <label style="display:flex;align-items:center;gap:8px;font-size:12.5px;font-weight:600;cursor:pointer;padding:6px 10px;background:#fff;border-radius:8px;border:1px solid #e2e8f0;margin:0">
                            <input type="radio" name="range_type" value="month" checked onchange="switchDateSelection(this.value)">
                            <span>Month Selection</span>
                        </label>
                        <label style="display:flex;align-items:center;gap:8px;font-size:12.5px;font-weight:600;cursor:pointer;padding:6px 10px;background:#fff;border-radius:8px;border:1px solid #e2e8f0;margin:0">
                            <input type="radio" name="range_type" value="custom" onchange="switchDateSelection(this.value)">
                            <span>Custom Date Range</span>
                        </label>
                    </div>

                    <!-- Single Date Picker -->
                    <div id="singleDateBox" style="display:none;margin-top:auto">
                        <label style="font-size:11.5px;font-weight:700;color:#64748b;margin-bottom:4px;display:block">Select Date</label>
                        <input type="date" name="date" value="{{ now()->toDateString() }}" style="width:100%;padding:6px 10px;border-radius:8px;border:1px solid #cbd5e1;font-size:12.5px;box-sizing:border-box">
                    </div>

                    <!-- Month Selection Pickers -->
                    <div id="monthDateBox" style="margin-top:auto">
                        <label style="font-size:11.5px;font-weight:700;color:#64748b;margin-bottom:4px;display:block">Select Month &amp; Year</label>
                        <select name="month" style="width:100%;margin-bottom:8px;padding:6px 10px;border-radius:8px;border:1px solid #cbd5e1;font-size:12.5px;box-sizing:border-box">
                            @for($m=1;$m<=12;$m++)
                                <option value="{{ $m }}" {{ now()->month==$m?'selected':'' }}>{{ date('F', mktime(0,0,0,$m,1)) }}</option>
                            @endfor
                        </select>
                        <select name="year" style="width:100%;padding:6px 10px;border-radius:8px;border:1px solid #cbd5e1;font-size:12.5px;box-sizing:border-box">
                            @for($y=2024;$y<=2030;$y++)
                                <option value="{{ $y }}" {{ now()->year==$y?'selected':'' }}>{{ $y }}</option>
                            @endfor
                        </select>
                    </div>

                    <!-- Custom Range Pickers -->
                    <div id="customDateBox" style="display:none;margin-top:auto">
                        <label style="font-size:11.5px;font-weight:700;color:#64748b;margin-bottom:4px;display:block">From Date</label>
                        <input type="date" name="from" value="{{ now()->startOfMonth()->toDateString() }}" style="width:100%;margin-bottom:8px;padding:6px 10px;border-radius:8px;border:1px solid #cbd5e1;font-size:12.5px;box-sizing:border-box">
                        <label style="font-size:11.5px;font-weight:700;color:#64748b;margin-bottom:4px;display:block">To Date</label>
                        <input type="date" name="to" value="{{ now()->toDateString() }}" style="width:100%;padding:6px 10px;border-radius:8px;border:1px solid #cbd5e1;font-size:12.5px;box-sizing:border-box">
                    </div>
                </div>

                <!-- MIDDLE BOX: Select Category Checklist (Optional) -->
                <div style="border:1px solid #e2e8f0;border-radius:12px;padding:12px 14px;background:#fff;display:flex;flex-direction:column;box-sizing:border-box">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
                        <div style="font-size:13px;font-weight:700;color:#1e293b;display:flex;align-items:center;gap:6px">
                            <i class="fa-solid fa-tags" style="color:#f59e0b"></i> Select Category
                        </div>
                        <span style="font-size:10.5px;font-weight:700;background:#f1f5f9;color:#64748b;padding:2px 7px;border-radius:10px">Optional</span>
                    </div>

                    <div style="position:relative;margin-bottom:8px">
                        <input type="text" id="catSearchInput" placeholder="Search category..." onkeyup="filterCategoriesList()" style="width:100%;font-size:12px;padding:6px 10px;border-radius:8px;border:1px solid #e2e8f0;outline:none;box-sizing:border-box">
                    </div>
                    
                    <label style="display:flex;align-items:center;gap:8px;font-size:12px;padding:5px 8px;background:#f8fafc;border-radius:6px;border:1px solid #e2e8f0;font-weight:700;cursor:pointer;margin-bottom:6px">
                        <input type="checkbox" id="selectAllCatCheckbox" onchange="toggleSelectAllCategories(this)" checked>
                        <span>All Categories (No filter)</span>
                    </label>

                    <div style="max-height:200px;overflow-y:auto;overflow-x:hidden;padding-right:2px" id="catChecklist">
                        @foreach($categories as $cat)
                            <label class="cat-item" style="display:flex;align-items:center;gap:8px;font-size:12px;padding:4px 6px;border-radius:6px;color:#334155;cursor:pointer;margin-bottom:2px">
                                <input type="checkbox" name="category_ids[]" value="{{ $cat->id }}" class="cat-cb" onchange="onCategoryCheckboxChange()" checked>
                                <span style="font-weight:500;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $cat->name }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <!-- RIGHT BOX: Select Staff Checklist -->
                <div style="border:1px solid #e2e8f0;border-radius:12px;padding:12px 14px;background:#fff;display:flex;flex-direction:column;box-sizing:border-box;min-width:0">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
                        <div style="font-size:13px;font-weight:700;color:#1e293b;display:flex;align-items:center;gap:6px">
                            <i class="fa-solid fa-users" style="color:#10b981"></i> Staff Members
                        </div>
                        <span id="empCountBadge" style="font-size:11px;font-weight:700;background:#dcfce7;color:#15803d;padding:2px 8px;border-radius:10px">{{ $employees->count() }} Selected</span>
                    </div>

                    <div style="position:relative;margin-bottom:8px">
                        <input type="text" id="empSearchInput" placeholder="Search by name, code..." onkeyup="filterEmployeesList()" style="width:100%;font-size:12px;padding:6px 10px;border-radius:8px;border:1px solid #e2e8f0;outline:none;box-sizing:border-box">
                    </div>
                    
                    <label style="display:flex;align-items:center;gap:8px;font-size:12px;padding:5px 8px;background:#f8fafc;border-radius:6px;border:1px solid #e2e8f0;font-weight:700;cursor:pointer;margin-bottom:6px">
                        <input type="checkbox" id="selectAllEmpCheckbox" onchange="toggleSelectAllEmployees(this)" checked>
                        <span id="selectAllEmpLabel">Select All ({{ $employees->count() }}) Staff</span>
                    </label>

                    <div style="max-height:200px;overflow-y:auto;overflow-x:hidden;padding-right:2px" id="empChecklist">
                        @foreach($employees as $emp)
                            @php
                                $empDeptName = $emp->department ?: ($emp->department_id ? ($departments->firstWhere('id', $emp->department_id)?->name ?? '') : '');
                                $empDeptId = $emp->department_id ?: ($empDeptName ? ($departments->firstWhere('name', $empDeptName)?->id ?? 0) : 0);
                                $empCatName = $emp->category?->name ?: ($emp->category_id ? ($categories->firstWhere('id', $emp->category_id)?->name ?? '') : '');
                                if (!$empCatName && !empty($emp->role) && !in_array(strtolower($emp->role), ['employee', 'staff'])) {
                                    $empCatName = ucfirst($emp->role);
                                }
                                if (!$empCatName) {
                                    $empCatName = 'General Staff';
                                }
                            @endphp
                            <label class="emp-item" 
                                   data-category-id="{{ $emp->category_id ?: 0 }}" 
                                   data-company-id="{{ $emp->company_id ?: 0 }}" 
                                   data-department-id="{{ $empDeptId }}" 
                                   data-search="{{ strtolower($emp->displayName() . ' ' . ($emp->serial_no ?? '') . ' ' . ($emp->code ?? '') . ' ' . $empDeptName . ' ' . $empCatName) }}" 
                                   style="display:flex;align-items:center;gap:10px;font-size:12px;padding:6px 10px;border-radius:8px;margin-bottom:3px;cursor:pointer;background:#fff;border:1px solid #f1f5f9;box-sizing:border-box">
                                <input type="checkbox" name="employee_ids[]" value="{{ $emp->id }}" class="emp-cb" onchange="syncSelectAllEmpState()" checked>
                                <div style="flex:1;min-width:0;display:flex;justify-content:space-between;align-items:center;gap:8px">
                                    <div style="min-width:0;overflow:hidden">
                                        <div style="font-weight:600;color:#0f172a;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                                            {{ $emp->displayName() }}
                                            @if(!empty($emp->serial_no) || !empty($emp->code))
                                                <span style="font-size:11px;font-weight:normal;color:#64748b">({{ $emp->serial_no ?: $emp->code }})</span>
                                            @endif
                                        </div>
                                        <div style="font-size:11px;color:#64748b;margin-top:1px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                                            {{ $empDeptName ?: 'No Dept' }}
                                        </div>
                                    </div>
                                    <div style="flex-shrink:0">
                                        <span style="display:inline-block;padding:2px 8px;border-radius:6px;font-size:10.5px;font-weight:600;background:#f8fafc;color:#334155;border:1px solid #e2e8f0;white-space:nowrap">
                                            {{ $empCatName }}
                                        </span>
                                    </div>
                                </div>
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- BOTTOM BAR: Export Format & Submit Button -->
            <div style="margin-top:16px;padding-top:12px;border-top:1px solid #f1f5f9;display:flex;justify-space-between;align-items:center">
                <div style="display:flex;align-items:center;gap:12px">
                    <span style="font-size:12.5px;font-weight:700;color:#475569">Export Format:</span>
                    <label style="display:inline-flex;align-items:center;gap:8px;font-size:12.5px;font-weight:600;padding:6px 14px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;color:#166534;cursor:pointer;margin:0">
                        <input type="radio" name="format" value="excel" checked> <i class="fa-solid fa-file-excel" style="color:#16a34a"></i> Excel (.xls)
                    </label>
                    <label style="display:inline-flex;align-items:center;gap:8px;font-size:12.5px;font-weight:600;padding:6px 14px;background:#fef2f2;border:1px solid #fecaca;border-radius:8px;color:#991b1b;cursor:pointer;margin:0">
                        <input type="radio" name="format" value="pdf"> <i class="fa-solid fa-file-pdf" style="color:#dc2626"></i> PDF Document
                    </label>
                </div>

                <div style="display:flex;align-items:center;gap:10px">
                    <button type="button" class="btn light" onclick="closeReportModal()" style="padding:8px 16px;border-radius:8px;font-weight:600;font-size:13px">Cancel</button>
                    <button type="submit" id="reportSubmitBtn" class="btn" style="background:#0f172a;color:#fff;padding:8px 20px;border-radius:8px;font-weight:700;font-size:13px;display:inline-flex;align-items:center;gap:8px">
                        <i class="fa-solid fa-sliders"></i> Generate Payroll Register
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<style>
/* Modal Layout & Reset Styles */
#reportModal .modal {
    width: 1100px !important;
    max-width: 96vw !important;
    box-sizing: border-box !important;
    max-height: 90vh !important;
    overflow-y: auto !important;
}

#reportModal input[type="checkbox"],
#reportModal input[type="radio"] {
    width: 16px !important;
    height: 16px !important;
    min-width: 16px !important;
    max-width: 16px !important;
    min-height: 16px !important;
    max-height: 16px !important;
    padding: 0 !important;
    margin: 0 !important;
    border: 1px solid #cbd5e1 !important;
    border-radius: 4px !important;
    cursor: pointer !important;
    flex-shrink: 0 !important;
    display: inline-block !important;
    vertical-align: middle !important;
    box-sizing: border-box !important;
    accent-color: #0f172a !important;
}

#reportModal input[type="radio"] {
    border-radius: 50% !important;
}

#reportModal label {
    margin: 0 !important;
    font-weight: 500;
}

#reportModal .emp-item:hover {
    background: #f8fafc !important;
    border-color: #cbd5e1 !important;
}

#reportModal .cat-item:hover {
    background: #f8fafc !important;
}

#reportModal select, 
#reportModal input[type="text"], 
#reportModal input[type="date"] {
    box-sizing: border-box !important;
}

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
    syncEmployeesByFilters();
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
    syncEmployeesByFilters();
}

function onCategoryCheckboxChange() {
    const allCatCbs = Array.from(document.querySelectorAll('.cat-cb'));
    const checkedCatCbs = Array.from(document.querySelectorAll('.cat-cb:checked'));
    const selectAllCatCb = document.getElementById('selectAllCatCheckbox');
    if (selectAllCatCb) {
        selectAllCatCb.checked = (checkedCatCbs.length === allCatCbs.length && allCatCbs.length > 0);
    }
    syncEmployeesByFilters();
}

function toggleSelectAllCategories(master) {
    document.querySelectorAll('.cat-cb').forEach(cb => cb.checked = master.checked);
    syncEmployeesByFilters();
}

function toggleSelectAllEmployees(master) {
    document.querySelectorAll('.emp-item').forEach(item => {
        if (item.style.display !== 'none') {
            const cb = item.querySelector('.emp-cb');
            if (cb && !cb.disabled) cb.checked = master.checked;
        }
    });
    syncSelectAllEmpState();
}

function syncSelectAllEmpState() {
    const visibleItems = Array.from(document.querySelectorAll('.emp-item')).filter(i => i.style.display !== 'none');
    const checkedVisible = visibleItems.filter(i => i.querySelector('.emp-cb')?.checked);
    const selectAllEmpCb = document.getElementById('selectAllEmpCheckbox');
    if (selectAllEmpCb) {
        selectAllEmpCb.checked = (checkedVisible.length === visibleItems.length && visibleItems.length > 0);
    }
    const empCountBadge = document.getElementById('empCountBadge');
    if (empCountBadge) {
        empCountBadge.textContent = `${checkedVisible.length} / ${visibleItems.length} Selected`;
    }
}

function syncEmployeesByFilters() {
    const selCompany = document.getElementById('reportCompanySelect')?.value || '';
    const selDepartment = document.getElementById('reportDepartmentSelect')?.value || '';
    const empSearch = (document.getElementById('empSearchInput')?.value || '').toLowerCase().trim();

    const selectAllCatCb = document.getElementById('selectAllCatCheckbox');
    const allCatCbs = Array.from(document.querySelectorAll('.cat-cb'));
    const checkedCatCbs = Array.from(document.querySelectorAll('.cat-cb:checked'));
    const checkedCatIds = checkedCatCbs.map(cb => String(cb.value));

    // Client Requirement:
    // Company or Department filter must list ALL employees without requiring a Category!
    // Category filtering is active ONLY when:
    // 1. "All Categories" is NOT checked
    // 2. AND at least 1 category is checked
    // 3. AND not all categories are checked (user specifically selected a sub-filter)
    const isCategoryFilterActive = selectAllCatCb 
        ? (!selectAllCatCb.checked && checkedCatCbs.length > 0 && checkedCatCbs.length < allCatCbs.length) 
        : false;

    let visibleCount = 0;
    let checkedCount = 0;

    document.querySelectorAll('.emp-item').forEach(item => {
        const catId = String(item.getAttribute('data-category-id') || '0');
        const compId = String(item.getAttribute('data-company-id') || '0');
        const deptId = String(item.getAttribute('data-department-id') || '0');
        const searchData = item.getAttribute('data-search') || item.innerText.toLowerCase();
        const cb = item.querySelector('.emp-cb');

        const matchesCompany = !selCompany || compId === selCompany;
        const matchesDepartment = !selDepartment || deptId === selDepartment;
        const matchesSearch = !empSearch || searchData.includes(empSearch);
        const matchesCategory = !isCategoryFilterActive || checkedCatIds.includes(catId);

        if (matchesCompany && matchesDepartment && matchesSearch && matchesCategory) {
            item.style.display = 'flex';
            if (cb) {
                cb.disabled = false;
                if (cb.checked) checkedCount++;
            }
            visibleCount++;
        } else {
            item.style.display = 'none';
            if (cb) {
                cb.disabled = true;
            }
        }
    });

    const selectAllEmpLabel = document.getElementById('selectAllEmpLabel');
    if (selectAllEmpLabel) {
        selectAllEmpLabel.textContent = `Select All (${visibleCount}) Staff`;
    }
    const empCountBadge = document.getElementById('empCountBadge');
    if (empCountBadge) {
        empCountBadge.textContent = `${checkedCount} / ${visibleCount} Selected`;
    }
    const selectAllEmpCb = document.getElementById('selectAllEmpCheckbox');
    if (selectAllEmpCb) {
        selectAllEmpCb.checked = (visibleCount > 0 && checkedCount === visibleCount);
    }
}
// Alias for backwards-compatibility
const syncEmployeesByCategory = syncEmployeesByFilters;
</script>
@endsection
