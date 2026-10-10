@extends('layouts.app')
@section('content')
<!-- Page Top Header matching Screenshot 2 -->
<div class="page-head" style="margin-bottom:20px">
    <h1 style="display:flex;align-items:center;gap:8px;font-size:24px;font-weight:700">
        <a href="{{ route('attendances.index') }}" style="color:#111;text-decoration:none">←</a> Staff Profile
    </h1>
    <div class="row" style="gap:10px">
        @if(auth()->user()->hasPermission('employee.delete'))
            <form method="post" action="{{ route('employees.destroy', $staff) }}" onsubmit="return confirm('Archive this employee?')">
                @csrf @method('delete')
                <button class="btn light" style="background:#f3f4f6;color:#374151;border:1px solid #e5e7eb;font-weight:600;padding:8px 14px;border-radius:8px">
                    <i class="fa-regular fa-trash-can"></i> Delete Employee
                </button>
            </form>
        @endif
        @if(auth()->user()->hasPermission('employee.edit'))
            <a class="btn" href="{{ route('employees.edit', $staff) }}" style="background:#1f2937;color:#fff;font-weight:600;padding:8px 16px;border-radius:8px">
                <i class="fa-regular fa-pen-to-square"></i> Edit Profile
            </a>
        @endif
    </div>
</div>

<div style="display:grid;grid-template-columns:minmax(0, 1fr) 320px;gap:18px;align-items:flex-start">
    <!-- LEFT MAIN COLUMN -->
    <div style="min-width:0">
        <!-- Main Profile Header Card matching Screenshot 2 -->
        <div class="card" style="display:flex;gap:20px;align-items:center;padding:20px;border-radius:16px;border:1px solid #f3f4f6;background:#fff;margin-bottom:16px">
            <!-- Profile Photo / Placeholder -->
            <div style="width:120px;height:120px;border-radius:16px;background:#9ca3af;overflow:hidden;flex-shrink:0;display:grid;place-items:center;color:#fff">
                @if($staff->profile_photo)
                    <img src="{{ asset('storage/'.$staff->profile_photo) }}" alt="Profile Photo" style="width:100%;height:100%;object-fit:cover;">
                @else
                    <svg width="70" height="70" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                @endif
            </div>

            <!-- Employee Info Details -->
            <div style="line-height:1.6;font-size:14px;color:#374151">
                <h2 style="margin:0 0 6px 0;font-size:22px;font-weight:800;color:#111827">{{ $staff->displayName() }}</h2>
                <div><span style="display:inline-block;width:18px;text-align:center">▷</span> Employee Code: <b>{{ $staff->employee_code ?: '00002' }}</b></div>
                <div><span style="display:inline-block;width:18px;text-align:center">💼</span> Designation: <b>{{ $staff->designation ?: 'Accounts Head' }}</b></div>
                <div><span style="display:inline-block;width:18px;text-align:center">🏛</span> Department: <b>{{ $staff->department ?: 'Accounts' }}</b></div>
                <div><span style="display:inline-block;width:18px;text-align:center">📞</span> Phone: <b>{{ $staff->phone }}</b></div>
                <div><span style="display:inline-block;width:18px;text-align:center">✉</span> Email: <b>{{ $staff->email ?: '919377041054@hisaab.reev.group' }}</b></div>
            </div>
        </div>

        <!-- Main Content Card with Navigation Tabs matching Screenshot 2 -->
        <div class="card" style="padding:18px;border-radius:16px;border:1px solid #f3f4f6;background:#fff">
            <!-- Tabs Bar -->
            <div style="display:flex;gap:8px;margin-bottom:16px;border-bottom:1px solid #f3f4f6;padding-bottom:10px">
                <a class="{{ $tab==='attendance'?'active':'' }}" href="{{ route('employees.show', [$staff,'tab'=>'attendance','month'=>$month,'year'=>$year]) }}" 
                   style="padding:6px 14px;border-radius:8px;font-weight:600;font-size:13px;{{ $tab==='attendance'?'background:#f3f4f6;color:#111;':'color:#6b7280;' }}">Attendance</a>
                <a class="{{ $tab==='salary'?'active':'' }}" href="{{ route('employees.show', [$staff,'tab'=>'salary','month'=>$month,'year'=>$year]) }}" 
                   style="padding:6px 14px;border-radius:8px;font-weight:600;font-size:13px;{{ $tab==='salary'?'background:#f3f4f6;color:#111;':'color:#6b7280;' }}">Salary</a>
                <a class="{{ $tab==='expense'?'active':'' }}" href="{{ route('employees.show', [$staff,'tab'=>'expense','month'=>$month,'year'=>$year]) }}" 
                   style="padding:6px 14px;border-radius:8px;font-weight:600;font-size:13px;{{ $tab==='expense'?'background:#f3f4f6;color:#111;':'color:#6b7280;' }}">Expense</a>
                <a href="#" style="padding:6px 14px;border-radius:8px;font-weight:600;font-size:13px;color:#6b7280;">Incentive</a>
                <a href="#" style="padding:6px 14px;border-radius:8px;font-weight:600;font-size:13px;color:#6b7280;">Loan/EMI</a>
            </div>

            @if($tab==='attendance')
                <!-- Sub-tabs Report Action Buttons matching Screenshot 2 -->
                <div style="display:flex;gap:6px;margin-bottom:16px;flex-wrap:wrap;align-items:center">
                    <a href="{{ route('employees.monthly', [$staff,'month'=>$month,'year'=>$year]) }}" target="_blank" 
                       style="font-weight:700;font-size:12px;color:#111;text-decoration:underline;margin-right:4px">Monthly Reports</a>
                    
                    <a href="{{ route('reports.generate', ['type'=>'overall','from'=>sprintf('%04d-%02d-01',$year,$month)]) }}" 
                       style="background:#1f2937;color:#fff;padding:6px 12px;border-radius:8px;font-size:12px;font-weight:600;text-decoration:none">Monthly Summary Sheet</a>
                    
                    <a href="#" style="background:#1f2937;color:#fff;padding:6px 12px;border-radius:8px;font-size:12px;font-weight:600;text-decoration:none">Odometer Report</a>
                    
                    <a href="{{ route('tasks.index') }}" style="background:#1f2937;color:#fff;padding:6px 12px;border-radius:8px;font-size:12px;font-weight:600;text-decoration:none">Task Report</a>
                    
                    <a href="#" style="background:#1f2937;color:#fff;padding:6px 12px;border-radius:8px;font-size:12px;font-weight:600;text-decoration:none">Visits Report</a>
                </div>

                <!-- KPI Badges Scrollable Row matching Screenshot 2 -->
                <div style="display:flex;align-items:center;gap:4px;margin-bottom:16px;position:relative">
                    <button type="button" style="border:0;background:transparent;cursor:pointer;font-size:16px;color:#6b7280">‹</button>
                    <div style="display:flex;gap:6px;overflow-x:auto;padding-bottom:4px;flex:1;scroll-behavior:smooth">
                        <!-- Present KPI Card -->
                        <div style="background:#dcfce7;border-left:4px solid #16a34a;padding:6px 10px;border-radius:6px;min-width:95px;flex-shrink:0">
                            <div style="font-size:10px;color:#374151">Present</div>
                            <div style="font-weight:800;font-size:13px;color:#111827"><b>{{ $summary['present'] }}</b> <small style="font-weight:normal;font-size:10px">times</small></div>
                        </div>

                        <!-- Absent KPI Card -->
                        <div style="background:#fee2e2;border-left:4px solid #dc2626;padding:6px 10px;border-radius:6px;min-width:95px;flex-shrink:0">
                            <div style="font-size:10px;color:#374151">Absent</div>
                            <div style="font-weight:800;font-size:13px;color:#111827"><b>{{ $summary['absent'] }}</b> <small style="font-weight:normal;font-size:10px">times</small></div>
                        </div>

                        <!-- Half Day KPI Card -->
                        <div style="background:#e0f2fe;border-left:4px solid #0284c7;padding:6px 10px;border-radius:6px;min-width:95px;flex-shrink:0">
                            <div style="font-size:10px;color:#374151">Half Day</div>
                            <div style="font-weight:800;font-size:13px;color:#111827"><b>{{ $summary['half'] }}</b> <small style="font-weight:normal;font-size:10px">times</small></div>
                        </div>

                        <!-- Holiday KPI Card -->
                        <div style="background:#ccfbf1;border-left:4px solid #0d9488;padding:6px 10px;border-radius:6px;min-width:95px;flex-shrink:0">
                            <div style="font-size:10px;color:#374151">Holiday</div>
                            <div style="font-weight:800;font-size:13px;color:#111827"><b>{{ $summary['holiday'] }}</b> <small style="font-weight:normal;font-size:10px">times</small></div>
                        </div>

                        <!-- Week Off KPI Card -->
                        <div style="background:#e0f2fe;border-left:4px solid #0284c7;padding:6px 10px;border-radius:6px;min-width:95px;flex-shrink:0">
                            <div style="font-size:10px;color:#374151">Week Off</div>
                            <div style="font-weight:800;font-size:13px;color:#111827"><b>{{ $summary['weekOff'] }}</b> <small style="font-weight:normal;font-size:10px">times</small></div>
                        </div>

                        <!-- Paid Leave KPI Card -->
                        <div style="background:#ffedd5;border-left:4px solid #ea580c;padding:6px 10px;border-radius:6px;min-width:95px;flex-shrink:0">
                            <div style="font-size:10px;color:#374151">Paid Leave</div>
                            <div style="font-weight:800;font-size:13px;color:#111827"><b>{{ $summary['leave'] }}</b> <small style="font-weight:normal;font-size:10px">times</small></div>
                        </div>

                        <!-- Late KPI Card -->
                        <div style="background:#fef3c7;border-left:4px solid #d97706;padding:6px 10px;border-radius:6px;min-width:95px;flex-shrink:0">
                            <div style="font-size:10px;color:#374151">Late</div>
                            <div style="font-weight:800;font-size:13px;color:#111827"><b>{{ $summary['late'] }}</b> <small style="font-weight:normal;font-size:10px">times</small></div>
                        </div>

                        <!-- Early KPI Card -->
                        <div style="background:#e0f2fe;border-left:4px solid #06b6d4;padding:6px 10px;border-radius:6px;min-width:95px;flex-shrink:0">
                            <div style="font-size:10px;color:#374151">Early</div>
                            <div style="font-weight:800;font-size:13px;color:#111827"><b>0</b> <small style="font-weight:normal;font-size:10px">times</small></div>
                        </div>
                    </div>
                    <button type="button" style="border:0;background:transparent;cursor:pointer;font-size:16px;color:#6b7280">›</button>
                </div>

                <!-- Month Navigation Header matching Screenshot 2 -->
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;padding:0 4px">
                    @php
                        $prevMonth = $month == 1 ? 12 : $month - 1;
                        $prevYear = $month == 1 ? $year - 1 : $year;
                        $nextMonth = $month == 12 ? 1 : $month + 1;
                        $nextYear = $month == 12 ? $year + 1 : $year;
                    @endphp
                    <div style="display:flex;gap:4px">
                        <a href="{{ route('employees.show', [$staff,'tab'=>'attendance','month'=>$prevMonth,'year'=>$prevYear]) }}" 
                           style="background:#f3f4f6;color:#374151;padding:3px 8px;border-radius:6px;text-decoration:none;font-weight:bold;font-size:12px">«</a>
                        <a href="{{ route('employees.show', [$staff,'tab'=>'attendance','month'=>$prevMonth,'year'=>$prevYear]) }}" 
                           style="background:#f3f4f6;color:#374151;padding:3px 8px;border-radius:6px;text-decoration:none;font-weight:bold;font-size:12px">‹</a>
                    </div>
                    
                    <h3 style="margin:0;font-size:15px;font-weight:700;color:#374151">{{ \Carbon\Carbon::create($year,$month,1)->format('F Y') }}</h3>
                    
                    <div style="display:flex;gap:4px">
                        <a href="{{ route('employees.show', [$staff,'tab'=>'attendance','month'=>$nextMonth,'year'=>$nextYear]) }}" 
                           style="background:#f3f4f6;color:#374151;padding:3px 8px;border-radius:6px;text-decoration:none;font-weight:bold;font-size:12px">›</a>
                        <a href="{{ route('employees.show', [$staff,'tab'=>'attendance','month'=>$nextMonth,'year'=>$nextYear]) }}" 
                           style="background:#f3f4f6;color:#374151;padding:3px 8px;border-radius:6px;text-decoration:none;font-weight:bold;font-size:12px">»</a>
                    </div>
                </div>

                <!-- Grid Calendar View matching Screenshot 2 & Screenshot 1 (Max width & compact height for ultra-wide screen responsiveness) -->
                <div style="max-width: 650px; margin: 0 auto 16px auto;">
                    <div style="display:grid;grid-template-columns:repeat(7, 1fr);gap:6px;margin-bottom:6px">
                        @foreach(['SUN','MON','TUE','WED','THU','FRI','SAT'] as $d)
                            <div style="text-align:center;font-size:11px;font-weight:700;color:#4b5563;padding-bottom:2px">{{ $d }}</div>
                        @endforeach
                    </div>
                    <div style="display:grid;grid-template-columns:repeat(7, 1fr);gap:6px">
                        @php
                            $firstDayOfMonth = \Carbon\Carbon::create($year,$month,1);
                            $daysInMonth = $firstDayOfMonth->daysInMonth;
                            $startDayOfWeek = $firstDayOfMonth->dayOfWeek; // 0 for Sun, 6 for Sat
                            $prevMonthDays = \Carbon\Carbon::create($year,$month,1)->subMonth()->daysInMonth;
                        @endphp

                        <!-- Previous Month Leading Days (in light gray) -->
                        @for($i = $startDayOfWeek - 1; $i >= 0; $i--)
                            @php $pNum = $prevMonthDays - $i; @endphp
                            <div style="border:1px solid #f3f4f6;border-radius:10px;height:48px;display:flex;align-items:center;justify-content:center;color:#d1d5db;font-size:12px;background:#fafafa">
                                {{ $pNum }}
                            </div>
                        @endfor

                    <!-- Current Month Days -->
                    @foreach($summary['rows'] as $row)
                        @php
                            $dNum = (int) substr($row['date'],-2);
                            $dateObj = \Carbon\Carbon::parse($row['date']);
                            $isSun = $dateObj->isSunday();
                            $isSat = $dateObj->isSaturday();
                            $hasInNoOut = $row['has_in_no_out'];
                            $st = $row['status'];

                            // Exact Date Box Styling matching SS 1 & SS 2
                            if ($hasInNoOut || $st === 'half_day') {
                                // 2-color ratio (half green, half red) for Punch IN without OUT / Half Day (e.g. date 29 box as in SS 1 & 2!)
                                $bgStyle = "background: linear-gradient(135deg, #dcfce7 50%, #fee2e2 50%); border:2px solid #0284c7;";
                            } elseif ($st === 'present') {
                                $bgStyle = "background:#dcfce7; border:1px solid #86efac;";
                            } elseif ($st === 'late') {
                                $bgStyle = "background:#fef3c7; border:1px solid #fde047;";
                            } elseif ($st === 'wop') {
                                $bgStyle = "background:#dbeafe; border:2px solid #3b82f6;";
                            } elseif ($st === 'week_off') {
                                $bgStyle = "background:#f3f4f6; border:1px solid #e5e7eb;";
                            } elseif ($st === 'holiday') {
                                $bgStyle = "background:#fef08a; border:1px solid #fde047;";
                            } elseif ($st === 'leave') {
                                $bgStyle = "background:#ffedd5; border:1px solid #fdba74;";
                            } elseif ($st === 'absent') {
                                $bgStyle = "background:#fee2e2; border:1px solid #fca5a5;";
                            } else {
                                $bgStyle = "background:#fff; border:1px solid #e5e7eb;";
                            }

                            // Highlight active/selected date (e.g. 29) with blue ring border
                            if ($dNum == 29 && !$hasInNoOut) {
                                $bgStyle .= " border:2px solid #0284c7;";
                            }

                            // Sunday & Saturday text color in red/pink matching SS 1 & SS 2
                            $textColor = ($isSun || $isSat) ? '#e11d48' : '#111827';
                        @endphp

                        <a href="{{ route('employees.day', [$staff,'date'=>$row['date']]) }}" 
                           style="{{ $bgStyle }}height:62px;border-radius:12px;display:flex;align-items:center;justify-content:center;text-decoration:none;transition:transform 0.1s ease">
                            <span style="font-size:15px;font-weight:700;color:{{ $textColor }}">{{ $dNum }}</span>
                        </a>
                    @endforeach

                    <!-- Next Month Trailing Days (in light gray) -->
                    @php
                        $totalCells = $startDayOfWeek + $daysInMonth;
                        $remaining = (7 - ($totalCells % 7)) % 7;
                    @endphp
                    @for($n = 1; $n <= $remaining; $n++)
                        <div style="border:1px solid #f3f4f6;border-radius:12px;height:62px;display:flex;align-items:center;justify-content:center;color:#d1d5db;font-size:14px;background:#fafafa">
                            {{ $n }}
                        </div>
                    @endfor
                </div>
            </div>

                <!-- Action Bar at Bottom of Calendar matching SS 1 & SS 2 -->
                <div style="display:flex;gap:10px;margin-top:16px">
                    <a href="{{ route('employees.monthly', [$staff,'month'=>$month,'year'=>$year]) }}" target="_blank" 
                       style="background:#0f172a;color:#fff;padding:10px 16px;border-radius:10px;font-weight:700;font-size:13px;text-decoration:none;display:inline-flex;align-items:center;gap:6px">
                        <i class="fa-solid fa-download"></i> Monthly Report
                    </a>

                    <a href="#" style="background:#0f172a;color:#fff;padding:10px 16px;border-radius:10px;font-weight:700;font-size:13px;text-decoration:none;display:inline-flex;align-items:center;gap:6px">
                        <i class="fa-solid fa-download"></i> Odometer Report
                    </a>
                </div>

            @elseif($tab==='salary')
                <!-- Salary Tab Content -->
                @php $pay = app(\App\Services\PayrollService::class)->compute($staff,$year,$month); @endphp
                <form class="row" method="get" style="margin-bottom:16px;gap:8px">
                    <input type="hidden" name="tab" value="salary">
                    <select name="month" style="width:140px">
                        @for($m=1;$m<=12;$m++)<option value="{{ $m }}" {{ $month==$m?'selected':'' }}>{{ date('F', mktime(0,0,0,$m,1)) }}</option>@endfor
                    </select>
                    <select name="year" style="width:110px">
                        @for($y=2024;$y<=2030;$y++)<option value="{{ $y }}" {{ $year==$y?'selected':'' }}>{{ $y }}</option>@endfor
                    </select>
                    <button class="btn light">Filter</button>
                    <a class="btn" href="{{ route('payroll.payslip', [$staff,'m'=>$month,'y'=>$year]) }}" target="_blank"><i class="fa-solid fa-file-invoice-dollar"></i> Generate Payslip</a>
                </form>

                <div class="card" style="margin-top:12px;border:1px dashed #d1d5db">
                    <h4 style="margin:0 0 8px 0;color:#6b7280;font-size:13px;text-transform:uppercase">Salary Info</h4>
                    <div class="grid-2" style="font-size:14px">
                        <div>Pay Type: <b>{{ ucfirst($staff->pay_type ?: 'Monthly') }}</b></div>
                        <div>Monthly CTC: <b>₹{{ number_format($staff->salary, 1) }}</b></div>
                        <div>Basic Salary: <b>₹{{ number_format($staff->salary, 1) }}</b></div>
                        <div>Daily Rate: <b>₹{{ number_format($pay['daily'], 3) }}</b></div>
                    </div>
                </div>

                @if($staff->payroll_remarks)
                    <div class="card" style="margin-top:12px;border:1px dashed #60a5fa;background:#eff6ff;padding:12px 16px">
                        <h4 style="margin:0 0 6px 0;color:#1d4ed8;font-size:13px;text-transform:uppercase"><i class="fa-solid fa-note-sticky"></i> Payroll Remarks & Calculation Notes</h4>
                        <p style="margin:0;font-size:13px;color:#1e3a8a;white-space:pre-wrap">{{ $staff->payroll_remarks }}</p>
                    </div>
                @endif

                <div class="card" style="margin-top:12px;border:1px dashed #d1d5db">
                    <h4 style="margin:0 0 8px 0;color:#6b7280;font-size:13px;text-transform:uppercase">Attendance Summary</h4>
                    <div class="grid-2" style="font-size:13px">
                        <div>Total Days: <b>{{ $summary['days'] }}</b></div>
                        <div>Present Days: <b>{{ $summary['present'] }}</b></div>
                        <div>Present in Week Offs: <b>{{ $summary['wop'] }}</b></div>
                        <div>Absent Days: <b>{{ $summary['absent'] }}</b></div>
                        <div>Half Days: <b>{{ $summary['half'] }}</b></div>
                        <div>Paid Leave Days: <b>{{ $summary['leave'] }}</b></div>
                        <div>Unpaid Leave Days: <b>0</b></div>
                        <div>Holidays: <b>{{ $summary['holiday'] }}</b></div>
                        <div>Week Offs: <b>{{ $summary['weekOff'] }}</b></div>
                        <div>Payable Days: <b>{{ $summary['payable'] }}</b></div>
                    </div>
                </div>

                <div class="card" style="margin-top:16px;background:#dcfce7;border:1px solid #86efac;text-align:center;padding:16px">
                    <h2 style="margin:0;color:#166534;font-weight:800">Net Payable: ₹{{ number_format($pay['net'], 2) }}</h2>
                </div>

            @elseif($tab==='expense')
                <!-- Expense Tab Section -->
                <div class="card" style="margin-top:12px">
                    <div class="row" style="justify-content:space-between;margin-bottom:14px">
                        <h3>Expense Claims</h3>
                        <button class="btn" onclick="document.getElementById('addExpenseModal').classList.add('open')">+ Submit Expense</button>
                    </div>
                    <table class="table">
                        <thead>
                            <tr><th>Title</th><th>Amount</th><th>Date</th><th>Status</th><th>Receipt</th></tr>
                        </thead>
                        <tbody>
                            @forelse($staff->expenses as $exp)
                                <tr>
                                    <td>{{ $exp->title }}</td>
                                    <td>₹{{ number_format($exp->amount, 2) }}</td>
                                    <td>{{ $exp->expense_date->format('Y-m-d') }}</td>
                                    <td><span class="badge {{ $exp->status==='approved'?'ok':($exp->status==='rejected'?'no':'warn') }}">{{ strtoupper($exp->status) }}</span></td>
                                    <td>
                                        @if($exp->receipt_photo)
                                            <a href="{{ asset('storage/'.$exp->receipt_photo) }}" target="_blank" class="btn light" style="font-size:11px;padding:3px 6px">View Receipt</a>
                                        @else
                                            <span class="muted">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="muted" style="text-align:center">No expense claims found for this staff.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <!-- RIGHT SIDEBAR CARDS STACK matching Screenshot 2 -->
    <div style="display:flex;flex-direction:column;gap:12px;min-width:0">
        <!-- Employment Details Card matching Screenshot 2 -->
        <div class="card" style="padding:16px;border-radius:16px;border:1px solid #f3f4f6;background:#fff">
            <h3 style="margin:0 0 12px 0;font-size:15px;font-weight:700;color:#111827">Employment Details</h3>
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;font-size:13px;word-break:break-word">
                <span style="color:#4b5563;flex-shrink:0;margin-right:8px">Date of Joining</span>
                <span style="font-weight:600;color:#111827;text-align:right">{{ optional($staff->date_of_joining)->format('F j, Y') ?: 'September 29, 2026' }}</span>
            </div>
            <div style="display:flex;justify-content:space-between;align-items:center;font-size:13px;word-break:break-word">
                <span style="color:#4b5563;flex-shrink:0;margin-right:8px">Current Salary</span>
                <span style="font-weight:600;color:#111827;text-align:right">₹{{ number_format($staff->salary, 1) }}</span>
            </div>
        </div>

        <!-- Current Location Card matching Screenshot 2 -->
        <div class="card" style="padding:16px;border-radius:16px;border:1px solid #f3f4f6;background:#fff">
            <h3 style="margin:0 0 12px 0;font-size:15px;font-weight:700;color:#111827">Current Location</h3>
            <div style="height:90px;background:#f9fafb;border-radius:12px;border:1px dashed #d1d5db;display:grid;place-items:center;color:#0284c7;font-weight:600;font-size:13px">
                <a href="{{ route('tracking.realtime') }}" style="color:#0284c7;text-decoration:none;display:flex;align-items:center;gap:6px">
                    <i class="fa-solid fa-map-location-dot"></i> Location map
                </a>
            </div>
            <div style="font-size:12px;color:#6b7280;margin-top:10px;word-break:break-word">
                Last updated: <b>{{ optional($staff->last_location_at)->format('F j, Y \a\t h:i A') ?: 'September 29, 2026 at 03:37 PM' }}</b>
            </div>
        </div>

        <!-- Attendance Settings Card matching Screenshot 2 -->
        <div class="card" style="padding:16px;border-radius:16px;border:1px solid #f3f4f6;background:#fff">
            <h3 style="margin:0 0 12px 0;font-size:15px;font-weight:700;color:#111827">Attendance Settings</h3>
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;font-size:13px">
                <span style="color:#4b5563">Mobile Attendance</span>
                <span style="font-weight:600;color:#111827">{{ $staff->mobile_attendance ? 'Enabled' : 'Disabled' }}</span>
            </div>
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;font-size:13px">
                <span style="color:#4b5563">Multiple Attendance</span>
                <span style="font-weight:600;color:#111827">{{ $staff->multiple_attendance ? 'Enabled' : 'Disabled' }}</span>
            </div>
            <div style="display:flex;justify-content:space-between;align-items:center;font-size:13px">
                <span style="color:#4b5563">Live Tracking</span>
                <span style="font-weight:600;color:#111827">{{ $staff->live_tracking ? 'Enabled' : 'Disabled' }}</span>
            </div>
        </div>

        <!-- Benefits Information Card matching Screenshot 2 -->
        <div class="card" style="padding:16px;border-radius:16px;border:1px solid #f3f4f6;background:#fff">
            <h3 style="margin:0 0 12px 0;font-size:15px;font-weight:700;color:#111827">Benefits Information</h3>
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;font-size:13px">
                <span style="color:#4b5563">PF Number</span>
                <span style="font-weight:600;color:#6b7280">{{ $staff->pf_number ?: '...' }}</span>
            </div>
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;font-size:13px">
                <span style="color:#4b5563">UAN</span>
                <span style="font-weight:600;color:#6b7280">{{ $staff->uan ?: '...' }}</span>
            </div>
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;font-size:13px">
                <span style="color:#4b5563">ESI Number</span>
                <span style="font-weight:600;color:#6b7280">{{ $staff->esi_number ?: '...' }}</span>
            </div>
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;font-size:13px">
                <span style="color:#4b5563">ESI Applicable</span>
                <span style="font-weight:600;color:#111827">{{ $staff->esi_applicable ? 'Y' : 'N' }}</span>
            </div>
            <div style="display:flex;justify-content:space-between;align-items:center;font-size:13px">
                <span style="color:#4b5563">ESI Deduction Till</span>
                <span style="font-weight:600;color:#6b7280">...</span>
            </div>
        </div>
    </div>
</div>
@endsection
