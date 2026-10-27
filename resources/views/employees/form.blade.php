@extends('layouts.app')
@section('content')
@php
    $currentUser = auth()->user();
    $isSuperAdmin = $currentUser && ($currentUser->isSuperAdmin() || $currentUser->isAdmin() || $currentUser->hasPermission('settings.manage') || $currentUser->hasPermission('settings.roles') || in_array((int)$currentUser->id, [1, 2], true));
    $canAssignCompany = $isSuperAdmin;
@endphp
<div class="page-head">
    <h1><a href="{{ route('attendances.index') }}">←</a> {{ $staff->exists ? 'Edit Staff Member' : 'Add New Staff Member' }}</h1>
    <button class="btn" form="staff-form">{{ $staff->exists ? 'Save Staff Member' : 'Create Staff Member' }}</button>
</div>

<form id="staff-form" method="post" enctype="multipart/form-data" action="{{ $staff->exists ? route('employees.update', $staff) : route('employees.store') }}" autocomplete="off">
    @csrf
    @if($staff->exists) @method('put') @endif
    <div class="form-tabs">
        <button type="button" class="active" onclick="showPane(0,this)">Basic Info</button>
        <button type="button" onclick="showPane(1,this)">Employment & Company</button>
        <button type="button" onclick="showPane(2,this)">Attendance Settings</button>
        <button type="button" onclick="showPane(3,this)">Payroll & Overtime</button>
    </div>

    <!-- PANE 1: BASIC INFO -->
    <div class="pane active card">
        <h3>Basic Information</h3>
        <p class="muted">Enter the staff member's personal details and system credentials.</p>
        
        <div class="grid-3">
            <div>
                <label>Company Name (Select from Master) *</label>
                @if($isSuperAdmin)
                    <select name="company_id" id="company_select" onchange="onCompanyChange()" required>
                        <option value="">Select Company Master</option>
                        @foreach($companies as $comp)
                            <option value="{{ $comp->id }}" data-prefix="{{ $comp->code_prefix }}" {{ ($staff->company_id ?: ($currentUser->company_id ?? null)) == $comp->id ? 'selected' : '' }}>{{ $comp->name }} {{ $comp->code_prefix ? '('.$comp->code_prefix.')' : '' }}</option>
                        @endforeach
                    </select>
                @else
                    <select name="company_id" id="company_select" disabled style="background:#f1f5f9;cursor:not-allowed">
                        @foreach($companies as $comp)
                            @if(($staff->company_id ?: ($currentUser->company_id ?? null)) == $comp->id)
                                <option value="{{ $comp->id }}" selected>{{ $comp->name }} {{ $comp->code_prefix ? '('.$comp->code_prefix.')' : '' }}</option>
                            @endif
                        @endforeach
                    </select>
                    <input type="hidden" name="company_id" value="{{ $staff->company_id ?: ($currentUser->company_id ?? '') }}">
                    <small style="color:#64748b;font-weight:600;display:block;margin-top:2px"><i class="fa-solid fa-lock" style="color:#ef4444"></i> Company assignment is restricted to Super Admin only.</small>
                @endif
            </div>
            <div>
                <label>Employee Code *</label>
                <input id="employee_code_input" name="employee_code" value="{{ old('employee_code', $staff->employee_code ?: \App\Models\User::generateNextEmployeeCode()) }}" required style="background:#f8fafc;font-weight:700;letter-spacing:0.5px" autocomplete="off">
                @error('employee_code')
                    <small style="color:#dc2626;font-weight:600;display:block;margin-top:2px"><i class="fa-solid fa-triangle-exclamation"></i> {{ $message }}</small>
                @enderror
                <small class="muted" style="display:block;margin-top:2px">Rule: Auto prefixes when Company is selected (e.g. RI00001)</small>
            </div>
            <div>
                <label>Add Profile Picture</label>
                <input type="file" name="profile_photo" accept="image/*">
                @if($staff->profile_photo)
                    <div style="margin-top:6px;display:flex;align-items:center;gap:10px">
                        <img src="{{ asset('storage/'.$staff->profile_photo) }}" style="width:36px;height:36px;border-radius:50%;object-fit:cover;border:1px solid #cbd5e1">
                        <label style="color:#ef4444;font-size:11px;font-weight:600;display:inline-flex;align-items:center;gap:4px;cursor:pointer">
                            <input type="checkbox" name="remove_profile_photo" value="1"> <i class="fa-solid fa-trash"></i> Remove Photo
                        </label>
                    </div>
                @endif
            </div>
        </div>

        @if($isSuperAdmin)
        <!-- Professional Multi-Company Access Permissions Card (Super Admin Only) -->
        <div style="margin-top:16px;margin-bottom:20px;background:#ffffff;border:1px solid #e2e8f0;border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,0.04);overflow:hidden">
            <div style="background:#f8fafc;padding:12px 16px;border-bottom:1px solid #e2e8f0;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px">
                <div style="display:flex;align-items:center;gap:10px">
                    <div style="background:#e0e7ff;color:#4f46e5;width:32px;height:32px;border-radius:8px;display:grid;place-items:center;font-size:14px">
                        <i class="fa-solid fa-building-user"></i>
                    </div>
                    <div>
                        <div style="font-size:13px;font-weight:700;color:#1e293b;display:flex;align-items:center;gap:6px">
                            Multi-Company Access Permissions
                            <span style="background:#f1f5f9;color:#64748b;font-size:10px;padding:2px 8px;border-radius:12px;font-weight:600">Cross-Company Portal</span>
                        </div>
                        <p class="muted" style="margin:2px 0 0 0;font-size:11px">Grant this staff member authorization to manage or view attendance across multiple companies.</p>
                    </div>
                </div>
                <div style="display:flex;gap:6px">
                    <button type="button" class="btn light" onclick="selectAllCompanies(true)" style="padding:4px 10px;font-size:11px;border-radius:6px"><i class="fa-solid fa-check-double"></i> Select All</button>
                    <button type="button" class="btn light" onclick="selectAllCompanies(false)" style="padding:4px 10px;font-size:11px;border-radius:6px"><i class="fa-solid fa-xmark"></i> Clear</button>
                </div>
            </div>

            @php $userCompAccess = (array) ($staff->accessible_company_ids ?? []); @endphp
            <div style="padding:14px 16px;display:grid;grid-template-columns:repeat(auto-fill, minmax(240px, 1fr));gap:12px">
                @foreach($companies as $comp)
                    @php
                        $isBaseComp = ($staff->company_id == $comp->id);
                        $isChecked = in_array($comp->id, $userCompAccess) || $isBaseComp;
                    @endphp
                    <label class="comp-card-item" id="comp_card_{{ $comp->id }}" style="display:flex;align-items:center;justify-content:space-between;padding:10px 12px;border-radius:8px;border:1.5px solid {{ $isChecked ? '#3b82f6' : '#e2e8f0' }};background:{{ $isChecked ? '#f0f7ff' : '#f8fafc' }};cursor:pointer;transition:all 0.2s ease">
                        <div style="display:flex;align-items:center;gap:10px">
                            <input type="checkbox" name="accessible_company_ids[]" value="{{ $comp->id }}" class="comp-chk" {{ $isChecked ? 'checked' : '' }} onchange="toggleCompCardStyle(this, {{ $comp->id }})" style="accent-color:#2563eb;width:15px;height:15px;cursor:pointer">
                            <div>
                                <strong style="font-size:12px;color:#1e293b;display:block">{{ $comp->name }}</strong>
                                @if($comp->code_prefix)
                                    <span style="font-size:10px;color:#64748b;font-weight:600"><i class="fa-solid fa-tag" style="font-size:9px"></i> {{ $comp->code_prefix }}</span>
                                @endif
                            </div>
                        </div>
                        @if($isBaseComp)
                            <span style="background:#dbeafe;color:#1e40af;font-size:10px;font-weight:700;padding:2px 6px;border-radius:4px" title="Primary Company">Primary</span>
                        @endif
                    </label>
                @endforeach
            </div>
        </div>
        @endif

        <div class="grid-2">
            <div><label>First Name *</label><input name="first_name" value="{{ old('first_name', $staff->first_name) }}" required autocomplete="off"></div>
            <div><label>Last Name</label><input name="last_name" value="{{ old('last_name', $staff->last_name) }}" autocomplete="off"></div>
        </div>

        <label>Employee Status & Resignation Details</label>
        <div class="row" style="gap:20px;margin-bottom:12px">
            <label style="font-weight:normal"><input type="radio" name="status" value="active" {{ in_array($staff->status, ['active', null]) ? 'checked' : '' }} onchange="toggleResignationFields()"> Active</label>
            <label style="font-weight:normal"><input type="radio" name="status" value="resigned" {{ $staff->status==='resigned'?'checked':'' }} onchange="toggleResignationFields()"> Resigned</label>
            <label style="font-weight:normal"><input type="radio" name="status" value="inactive" {{ $staff->status==='inactive'?'checked':'' }} onchange="toggleResignationFields()"> Left / Inactive</label>
            <label style="font-weight:normal"><input type="radio" name="status" value="archived" {{ $staff->status==='archived'?'checked':'' }} onchange="toggleResignationFields()"> Archived</label>
        </div>

        <div id="resignation_box" style="display:{{ in_array($staff->status, ['resigned', 'inactive']) ? 'block' : 'none' }};background:#fff1f2;padding:12px 16px;border-radius:10px;border:1px solid #fecdd3;margin-bottom:14px">
            <h4 style="margin:0 0 10px 0;color:#9f1239;font-size:14px"><i class="fa-solid fa-user-slash"></i> Resignation / Left Record</h4>
            <div class="grid-2">
                <div>
                    <label style="color:#9f1239">Resignation / Leaving Date</label>
                    <input type="date" name="resignation_date" value="{{ optional($staff->resignation_date)->toDateString() }}">
                </div>
                <div>
                    <label style="color:#9f1239">Resignation Reason / Remarks</label>
                    <input name="resignation_remarks" value="{{ $staff->resignation_remarks }}" placeholder="Reason for leaving, notice period details...">
                </div>
            </div>
        </div>

        <div class="grid-2">
            <div>
                <label>Phone Number *</label>
                <input name="phone" value="{{ old('phone', $staff->phone) }}" required autocomplete="off">
                @error('phone')
                    <small style="color:#dc2626;font-weight:600;display:block;margin-top:2px"><i class="fa-solid fa-triangle-exclamation"></i> {{ $message }}</small>
                @enderror
            </div>
            <div>
                <label>Email Address</label>
                <input name="email" type="email" value="{{ old('email', $staff->email) }}" autocomplete="off">
                @error('email')
                    <small style="color:#dc2626;font-weight:600;display:block;margin-top:2px"><i class="fa-solid fa-triangle-exclamation"></i> {{ $message }}</small>
                @enderror
            </div>
        </div>

        <div class="grid-2">
            <div>
                <label>Password</label>
                <input type="password" name="password" autocomplete="new-password" placeholder="{{ $staff->exists ? 'Leave blank to keep current' : 'Enter password (default 123456)' }}">
                @error('password')
                    <small style="color:#dc2626;font-weight:600;display:block;margin-top:2px"><i class="fa-solid fa-triangle-exclamation"></i> {{ $message }}</small>
                @enderror
            </div>
            <div>
                <label>Assign System Role *</label>
                @if($isSuperAdmin)
                    <select name="role" id="role_select" onchange="onRoleSelectChange(this.value)">
                        @php
                            $roleList = isset($roles) && count($roles) > 0 ? $roles->pluck('name')->toArray() : ['Admin', 'Manager', 'HR', 'Accountant', 'Supervisor', 'Developer', 'Employee'];
                        @endphp
                        @foreach($roleList as $rName)
                            <option value="{{ strtolower($rName) }}" {{ strtolower($staff->role ?: 'employee') === strtolower($rName) ? 'selected' : '' }}>{{ $rName }}</option>
                        @endforeach
                    </select>
                @else
                    <select name="role" id="role_select" disabled style="background:#f1f5f9;cursor:not-allowed">
                        @php
                            $roleList = isset($roles) && count($roles) > 0 ? $roles->pluck('name')->toArray() : ['Admin', 'Manager', 'HR', 'Accountant', 'Supervisor', 'Developer', 'Employee'];
                        @endphp
                        @foreach($roleList as $rName)
                            @if(strtolower($staff->role ?: 'employee') === strtolower($rName))
                                <option value="{{ strtolower($rName) }}" selected>{{ $rName }}</option>
                            @endif
                        @endforeach
                    </select>
                    <input type="hidden" name="role" value="{{ $staff->role ?: 'employee' }}">
                    <small style="color:#ef4444;font-weight:600;display:block;margin-top:2px"><i class="fa-solid fa-lock"></i> Only Super Admin can change system roles.</small>
                @endif
            </div>
        </div>

        @if($isSuperAdmin)
        <!-- Role & Permissions Section (Super Admin Only) -->
        <div id="manager_permissions_box" style="background:#f8fafc;padding:16px;border-radius:12px;border:1px solid #cbd5e1;margin-bottom:16px">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;flex-wrap:wrap;gap:8px">
                <div>
                    <label style="font-weight:700;margin:0;color:var(--accent);font-size:14px;display:flex;align-items:center;gap:6px">
                        <i class="fa-solid fa-user-shield"></i> Role & Module-Wise Access Permissions
                    </label>
                    <small class="muted" id="roleSyncNotice" style="display:none;color:#2563eb;font-weight:600;margin-top:2px"></small>
                </div>
                <button type="button" class="btn light" onclick="onRoleSelectChange(document.getElementById('role_select').value)" style="padding:4px 10px;font-size:11px;border-radius:6px">
                    <i class="fa-solid fa-arrows-rotate"></i> Sync With Selected Role
                </button>
            </div>

            @php
                $userPerms = is_array($staff->permissions) ? $staff->permissions : $staff->allPermissions();
                if (empty($userPerms) && !$staff->exists) {
                    $userPerms = ['dashboard.view', 'attendance.view', 'attendance.mark', 'leave.view', 'leave.apply', 'tasks.view'];
                }
                $formPermGroups = [
                    'Dashboard' => [
                        'dashboard.view' => 'View Dashboard',
                        'dashboard.reports' => 'View Reports',
                        'dashboard.download' => 'Download Reports',
                    ],
                    'Employee Management' => [
                        'employee.view' => 'View Employees',
                        'employee.add' => 'Add Employee',
                        'employee.import' => 'Excel Import Employees',
                        'employee.edit' => 'Edit Employee',
                        'employee.delete' => 'Delete Employee',
                    ],
                    'Attendance & Punches' => [
                        'attendance.view' => 'View Attendance Log',
                        'attendance.mark' => 'Mark Single Attendance',
                        'attendance.bulk' => 'Bulk Mark Attendance',
                        'attendance.edit' => 'Edit Punches & Shifts',
                        'roster.view' => 'Monthly Roster',
                    ],
                    'Leave Management' => [
                        'leave.view' => 'View Leave Requests',
                        'leave.apply' => 'Apply Leave',
                        'leave.approve' => 'Approve / Reject Leave',
                    ],
                    'Payroll & Salary' => [
                        'payroll.view' => 'View Salary & Payslips',
                        'payroll.process' => 'Process Payroll',
                        'payroll.advances' => 'Manage Advances & Loans',
                        'payroll.expenses' => 'Manage Expense Claims',
                    ],
                    'Tasks' => [
                        'tasks.view' => 'View Tasks',
                        'tasks.create' => 'Create & Assign Tasks',
                        'tasks.manage' => 'Manage & Reassign Tasks',
                    ],
                    'Live Tracking & Geo' => [
                        'tracking.view' => 'Live Location Tracking',
                        'geofence.view' => 'View Geofences',
                    ],
                    'Settings & System' => [
                        'settings.view' => 'View Settings',
                        'settings.roles' => 'Manage Roles & Permissions',
                        'settings.manage' => 'Manage System Settings',
                    ],
                ];
            @endphp

            <div style="display:grid;grid-template-columns:repeat(auto-fill, minmax(220px, 1fr));gap:12px;margin-top:10px">
                @foreach($formPermGroups as $gName => $pMap)
                    <div style="background:#fff;border:1px solid #e2e8f0;border-radius:8px;padding:10px">
                        <strong style="display:block;margin-bottom:6px;font-size:12px;color:#1e293b">{{ $gName }}</strong>
                        @foreach($pMap as $pK => $pL)
                            <label style="font-size:11px;font-weight:normal;display:flex;align-items:center;gap:6px;margin-bottom:4px;cursor:pointer">
                                <input type="checkbox" name="permissions[]" value="{{ $pK }}" {{ in_array($pK, $userPerms) ? 'checked' : '' }}>
                                {{ $pL }}
                            </label>
                        @endforeach
                    </div>
                @endforeach
            </div>
        </div>
        @endif

        <label>Address</label>
        <textarea name="address" rows="2">{{ old('address', $staff->address) }}</textarea>

        <div class="grid-2">
            <div><label>Birthdate</label><input type="date" name="birthday" value="{{ optional($staff->birthday)->toDateString() }}"></div>
            <div><label>Blood Group</label>
                <select name="blood_group">
                    <option value="">Select blood group</option>
                    @foreach(['A+','A-','B+','B-','O+','O-','AB+','AB-'] as $bg)
                        <option {{ $staff->blood_group===$bg?'selected':'' }}>{{ $bg }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="grid-2">
            <div><label>Emergency Contact Name</label><input name="emergency_contact_name" value="{{ $staff->emergency_contact_name }}"></div>
            <div><label>Emergency Contact Phone</label><input name="emergency_contact_phone" value="{{ $staff->emergency_contact_phone }}"></div>
        </div>

        <div class="grid-3">
            <div>
                <label>PAN Card Number</label>
                <input name="pan" value="{{ $staff->pan }}" placeholder="ABCDE1234F">
            </div>
            <div>
                <label>PAN Card Document</label>
                <input type="file" name="pan_document" accept="image/*,.pdf">
                @if($staff->pan_document)
                    <div style="font-size:11px;margin-top:6px;display:flex;align-items:center;gap:10px">
                        <a href="{{ asset('storage/'.$staff->pan_document) }}" target="_blank" style="color:var(--accent);font-weight:600"><i class="fa-solid fa-eye"></i> View PAN File</a>
                        <label style="color:#ef4444;font-size:11px;font-weight:600;display:inline-flex;align-items:center;gap:4px;cursor:pointer">
                            <input type="checkbox" name="remove_pan_document" value="1"> <i class="fa-solid fa-trash"></i> Remove
                        </label>
                    </div>
                @endif
            </div>
            <div>
                <label>Aadhaar Card Number</label>
                <input name="aadhaar" value="{{ $staff->aadhaar }}">
            </div>
        </div>

        <div class="grid-2">
            <div>
                <label>Aadhaar Card Document</label>
                <input type="file" name="aadhaar_document" accept="image/*,.pdf">
                @if($staff->aadhaar_document)
                    <div style="font-size:11px;margin-top:6px;display:flex;align-items:center;gap:10px">
                        <a href="{{ asset('storage/'.$staff->aadhaar_document) }}" target="_blank" style="color:var(--accent);font-weight:600"><i class="fa-solid fa-eye"></i> View Aadhaar File</a>
                        <label style="color:#ef4444;font-size:11px;font-weight:600;display:inline-flex;align-items:center;gap:4px;cursor:pointer">
                            <input type="checkbox" name="remove_aadhaar_document" value="1"> <i class="fa-solid fa-trash"></i> Remove
                        </label>
                    </div>
                @endif
            </div>
            <div>
                <label>Other Additional Documents (Upload Multiple)</label>
                <input type="file" name="documents[]" multiple>
                @if($staff->documents && $staff->documents->count() > 0)
                    <div style="margin-top:6px;display:flex;gap:8px;flex-wrap:wrap">
                        @foreach($staff->documents as $doc)
                            <div class="chip" style="background:#e0f2fe;color:#0369a1;font-size:11px;display:inline-flex;align-items:center;gap:6px;padding:4px 8px">
                                <a href="{{ asset('storage/'.$doc->path) }}" target="_blank" style="color:#0369a1;text-decoration:none">
                                    <i class="fa-solid fa-file"></i> {{ $doc->original_name ?: 'Document #'.$doc->id }}
                                </a>
                                <label style="color:#ef4444;margin:0;cursor:pointer" title="Check to remove this document">
                                    <input type="checkbox" name="delete_document_ids[]" value="{{ $doc->id }}" style="accent-color:#ef4444"> <i class="fa-solid fa-trash" style="font-size:10px"></i>
                                </label>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- PANE 2: EMPLOYMENT & COMPANY -->
    <div class="pane card">
        <h3>Employment & Organization Details</h3>

        <div style="margin-bottom:12px">
            <div style="display:flex;justify-content:space-between;align-items:center">
                <label style="margin:0">Department Master *</label>
                <button type="button" onclick="openAddDeptQuickModal()" style="border:0;background:none;color:var(--accent);font-weight:bold;cursor:pointer;font-size:13px">
                    <i class="fa-solid fa-circle-plus"></i> Add Department
                </button>
            </div>
            <select name="department_id" id="department_select" style="margin-top:4px;width:100%">
                <option value="">Select Department Master</option>
                @foreach($departments as $dept)
                    <option value="{{ $dept->id }}" {{ $staff->department_id==$dept->id?'selected':'' }}>{{ $dept->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="grid-2">
            <div><label>Designation Name</label><input name="designation" value="{{ $staff->designation }}" placeholder="e.g. Accounts Head"></div>
            <div>
                <div style="display:flex;justify-space-between;align-items:center">
                    <label style="margin:0">Category</label>
                    <button type="button" onclick="openAddCatQuickModal()" style="border:0;background:none;color:var(--accent);font-weight:bold;cursor:pointer;font-size:12px;margin-left:auto">
                        <i class="fa-solid fa-circle-plus"></i> Add Category
                    </button>
                </div>
                <select name="category_id" id="category_select" style="margin-top:4px;width:100%">
                    <option value="">Select category master</option>
                    @foreach($categories as $c)
                        <option value="{{ $c->id }}" {{ $staff->category_id==$c->id?'selected':'' }}>{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="grid-3">
            <div><label>Employee Type</label>
                <select name="employee_type">
                    @foreach(['Employee','Admin','Manager'] as $t)
                        <option {{ $staff->employee_type===$t?'selected':'' }}>{{ $t }}</option>
                    @endforeach
                </select>
            </div>
            <div><label>Gender</label>
                <select name="gender">
                    <option value="">Select gender</option>
                    @foreach(['Male','Female','Other'] as $g)
                        <option {{ $staff->gender===$g?'selected':'' }}>{{ $g }}</option>
                    @endforeach
                </select>
            </div>
            <div><label>Date of Joining</label><input type="date" name="date_of_joining" value="{{ optional($staff->date_of_joining)->toDateString() }}"></div>
        </div>

        <div class="grid-2">
            <div><label>Bank Account Number</label><input name="bank_account" value="{{ $staff->bank_account }}"></div>
            <div><label>IFSC Code</label><input name="ifsc" value="{{ $staff->ifsc }}"></div>
        </div>

        <div class="grid-3">
            <div><label>Bank Name</label><input name="bank_name" value="{{ $staff->bank_name }}"></div>
            <div><label>Branch Name</label><input name="branch_name" value="{{ $staff->branch_name }}"></div>
            <div><label>Bank A/C Holder Name</label><input name="bank_holder" value="{{ $staff->bank_holder }}"></div>
        </div>
    </div>

    <!-- PANE 3: ATTENDANCE SETTINGS -->
    <div class="pane card">
        <h3>Attendance Settings</h3>
        <div class="grid-2">
            <label><input type="checkbox" name="mobile_attendance" value="1" {{ $staff->mobile_attendance?'checked':'' }}> Mobile Attendance — mark from mobile</label>
            <label><input type="checkbox" name="multiple_attendance" value="1" {{ $staff->multiple_attendance?'checked':'' }}> Multiple Attendance</label>
            <label><input type="checkbox" name="shiftwise_attendance" value="1" {{ $staff->shiftwise_attendance?'checked':'' }}> Shiftwise Attendance</label>
            <label><input type="checkbox" name="live_tracking" value="1" {{ $staff->live_tracking?'checked':'' }}> Live Tracking</label>
            <div>
                <label>Punch From Location Mode</label>
                <select name="punch_from" id="punch_from_select" onchange="toggleAnywhereDates()">
                    <option value="geofence" {{ $staff->punch_from==='geofence'?'selected':'' }}>Geofence (Company Office)</option>
                    <option value="anywhere" {{ $staff->punch_from==='anywhere'?'selected':'' }}>Anywhere (Tour / Field Work)</option>
                </select>
            </div>
            <div>
                <label>Assigned Shift</label>
                <select name="shift_id">
                    <option value="">Select shift</option>
                    @foreach($shifts as $s)
                        <option value="{{ $s->id }}" {{ $staff->shift_id==$s->id?'selected':'' }}>{{ $s->name }} ({{ substr($s->start_time,0,5) }} - {{ substr($s->end_time,0,5) }})</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div id="anywhere_date_box" style="display:{{ $staff->punch_from==='anywhere'?'block':'none' }};background:#f0fdf4;padding:12px 16px;border-radius:10px;border:1px solid #bbf7d0;margin-top:12px;margin-bottom:12px">
            <label style="color:#166534;font-weight:700;display:block;margin-bottom:4px"><i class="fa-solid fa-map-location-dot"></i> Anywhere Location Tour Validity (From Date — To Date)</label>
            <p class="muted" style="font-size:12px;margin-bottom:8px">When an employee goes on tour/trip, enter valid date range. Attendance can be marked from anywhere during this period. After To Date expires, Geofence auto-enforces again.</p>
            <div class="grid-2">
                <div>
                    <label style="color:#166534">Tour From Date</label>
                    <input type="date" name="anywhere_from_date" value="{{ optional($staff->anywhere_from_date)->toDateString() }}">
                </div>
                <div>
                    <label style="color:#166534">Tour To Date</label>
                    <input type="date" name="anywhere_to_date" value="{{ optional($staff->anywhere_to_date)->toDateString() }}">
                </div>
            </div>
        </div>
        <label><input type="checkbox" name="ai_selfie" value="1" {{ $staff->ai_selfie?'checked':'' }}> AI Selfie Verification</label>
        <div style="background:#f8fafc;padding:14px;border-radius:10px;border:1px solid #e2e8f0;margin-top:12px;margin-bottom:12px">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px">
                <label style="font-weight:700;color:#1e293b;margin:0;display:flex;align-items:center;gap:6px">
                    <i class="fa-solid fa-camera-retro" style="color:var(--accent)"></i> Face Reference Images for AI Recognition
                </label>
                <span class="muted" style="font-size:11px">Upload multiple clear face photos for AI recognition</span>
            </div>
            <input type="file" name="face_images[]" id="face_images_input" accept="image/*" multiple onchange="previewFaceImages(this)" style="font-size:12.5px;padding:6px;width:100%;background:#fff;border:1px solid #cbd5e1;border-radius:6px">
            <div id="faceImagesPreview" style="display:flex;gap:10px;margin-top:8px;flex-wrap:wrap"></div>

            @if($staff->faceImages && $staff->faceImages->count() > 0)
                <div style="margin-top:12px;border-top:1px dashed #cbd5e1;padding-top:10px">
                    <small style="font-weight:700;color:#475569;display:block;margin-bottom:6px">Currently Uploaded Face Images ({{ $staff->faceImages->count() }})</small>
                    <div id="uploadedFaceImagesContainer" style="display:flex;gap:12px;flex-wrap:wrap">
                        @foreach($staff->faceImages as $fImg)
                            <div class="face-img-tile" style="position:relative;display:inline-block;border-radius:8px;overflow:hidden;border:1.5px solid #cbd5e1;background:#fff;box-shadow:0 2px 4px rgba(0,0,0,0.06)">
                                <img src="{{ asset('storage/'.$fImg->path) }}" style="width:70px;height:70px;object-fit:cover;display:block">
                                <button type="button" onclick="deleteFaceImageWeb({{ $fImg->id }}, this)" style="position:absolute;top:3px;right:3px;background:rgba(239,68,68,0.92);color:#fff;border:none;border-radius:4px;padding:3px 6px;font-size:11px;cursor:pointer;box-shadow:0 1px 3px rgba(0,0,0,0.25)" title="Permanently delete this face image">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                                <input type="checkbox" name="delete_face_image_ids[]" value="{{ $fImg->id }}" style="display:none">
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- PANE 4: PAYROLL & OVERTIME -->
    <div class="pane card">
        <h3>Payroll & Benefits</h3>
        <div class="grid-2">
            <div>
                <label>Pay Type</label>
                <select name="pay_type">
                    @foreach(['monthly'=>'Monthly','daily'=>'Daily','hourly'=>'Hourly'] as $k=>$v)
                        <option value="{{ $k }}" {{ $staff->pay_type===$k?'selected':'' }}>{{ $v }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label>Monthly/Base Salary (₹)</label>
                <input type="number" step="0.01" name="salary" value="{{ old('salary', $staff->salary ?? '0.00') }}" placeholder="0.00">
            </div>
        </div>
        
        <label>Week-Off Day</label>
        <select name="week_off_day">
            <option value="None" {{ ($staff->week_off_day === 'None' || empty($staff->week_off_day)) ? 'selected' : '' }}>None (No Week Off)</option>
            @foreach(['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'] as $d)
                <option value="{{ $d }}" {{ $staff->week_off_day === $d ? 'selected' : '' }}>{{ $d }}</option>
            @endforeach
        </select>
        
        <div class="grid-2" style="margin-top:12px">
            <div><label>PF Number</label><input name="pf_number" value="{{ $staff->pf_number }}"></div>
            <div><label>UAN</label><input name="uan" value="{{ $staff->uan }}"></div>
        </div>
        
        <div class="grid-2" style="margin-top:10px">
            <label><input type="checkbox" name="esi_applicable" value="1" {{ $staff->esi_applicable?'checked':'' }}> ESI Applicable</label>
            <label><input type="checkbox" name="overtime_applicable" value="1" {{ $staff->overtime_applicable?'checked':'' }}> Applicable for Overtime</label>
            <label><input type="checkbox" name="wop_applicable" value="1" {{ $staff->wop_applicable !== false ? 'checked' : '' }}> WOP — Week Off Present (Extra Day Salary)</label>
            <label><input type="checkbox" name="hop_applicable" value="1" {{ $staff->hop_applicable !== false ? 'checked' : '' }}> HOP — Holiday Present (Extra Day Salary)</label>
            <label><input type="checkbox" name="view_self_salary" value="1" {{ $staff->view_self_salary?'checked':'' }}> View Self Salary</label>
        </div>

        <div style="margin-top:14px">
            <label>Payroll Notes / Remarks (Description)</label>
            <textarea name="payroll_remarks" rows="3" placeholder="Enter payroll details, salary calculation rules, or specific employee notes...">{{ old('payroll_remarks', $staff->payroll_remarks) }}</textarea>
            <small class="muted">You can write special instructions or notes regarding how salary is calculated for this staff member.</small>
        </div>
        </fieldset>
    </div>
</form>

<!-- Modal for Quick Adding Department -->
<div id="quickDeptModal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.5);z-index:9999;align-items:center;justify-content:center">
    <div style="background:#fff;width:380px;max-width:95%;border-radius:12px;padding:20px;box-shadow:0 10px 15px -3px rgba(0,0,0,0.1)">
        <h4 style="margin:0 0 12px 0"><i class="fa-solid fa-plus-circle" style="color:var(--accent)"></i> Add Department Master</h4>
        <input id="quick_dept_name" placeholder="Department Name (e.g. Quality Control)" style="width:100%;margin-bottom:14px">
        <div style="display:flex;justify-content:flex-end;gap:8px">
            <button type="button" class="btn light" onclick="closeAddDeptQuickModal()">Cancel</button>
            <button type="button" class="btn" onclick="saveQuickDepartment()">Add & Select</button>
        </div>
    </div>
</div>

<!-- Modal for Quick Adding Category -->
<div id="quickCatModal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.5);z-index:9999;align-items:center;justify-content:center">
    <div style="background:#fff;width:380px;max-width:95%;border-radius:12px;padding:20px;box-shadow:0 10px 15px -3px rgba(0,0,0,0.1)">
        <h4 style="margin:0 0 12px 0"><i class="fa-solid fa-plus-circle" style="color:var(--accent)"></i> Add Category Master</h4>
        <input id="quick_cat_name" placeholder="Category Name (e.g. Staff, Worker, Contractor)" style="width:100%;margin-bottom:14px">
        <div style="display:flex;justify-content:flex-end;gap:8px">
            <button type="button" class="btn light" onclick="closeAddCatQuickModal()">Cancel</button>
            <button type="button" class="btn" onclick="saveQuickCategory()">Add & Select</button>
        </div>
    </div>
</div>

<script>
const rolePermissionsMap = @json($roles->mapWithKeys(function($r) {
    return [strtolower($r->name) => (array)($r->permissions ?? [])];
}));

function onRoleSelectChange(roleName) {
    if (!roleName) return;
    const rKey = roleName.toLowerCase().trim();
    if (rolePermissionsMap && rolePermissionsMap[rKey]) {
        const assignedPerms = rolePermissionsMap[rKey];
        document.querySelectorAll('input[name="permissions[]"]').forEach(chk => {
            chk.checked = assignedPerms.includes(chk.value);
        });
        const syncNotice = document.getElementById('roleSyncNotice');
        if (syncNotice) {
            syncNotice.innerText = `Auto-synced: ${assignedPerms.length} permissions checked for role "${roleName}"`;
            syncNotice.style.display = 'inline-block';
        }
    }
}

function selectAllCompanies(checked) {
    document.querySelectorAll('.comp-chk').forEach(c => {
        if (!c.disabled) {
            c.checked = checked;
            const parent = c.closest('.comp-card-item');
            if (parent) {
                parent.style.borderColor = checked ? '#3b82f6' : '#e2e8f0';
                parent.style.background = checked ? '#f0f7ff' : '#f8fafc';
            }
        }
    });
}

function toggleCompCardStyle(input, id) {
    const parent = document.getElementById('comp_card_' + id);
    if (parent) {
        parent.style.borderColor = input.checked ? '#3b82f6' : '#e2e8f0';
        parent.style.background = input.checked ? '#f0f7ff' : '#f8fafc';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    @if(!$staff->exists)
        const roleSel = document.getElementById('role_select');
        if (roleSel && roleSel.value) {
            onRoleSelectChange(roleSel.value);
        }
    @endif
});

function showPane(i, btn) {
  document.querySelectorAll('.pane').forEach((p, idx) => p.classList.toggle('active', idx===i));
  document.querySelectorAll('.form-tabs button').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
}

function toggleManagerPermissions() {
  const role = document.getElementById('role_select').value;
  const box = document.getElementById('manager_permissions_box');
  if (box) {
      box.style.display = 'block';
  }
}

function toggleResignationFields() {
  const selectedStatus = document.querySelector('input[name="status"]:checked')?.value;
  const box = document.getElementById('resignation_box');
  if (box) {
      box.style.display = (selectedStatus === 'resigned' || selectedStatus === 'inactive') ? 'block' : 'none';
  }
}

function toggleAnywhereDates() {
  const mode = document.getElementById('punch_from_select')?.value;
  const box = document.getElementById('anywhere_date_box');
  if (box) {
      box.style.display = (mode === 'anywhere') ? 'block' : 'none';
  }
}

function onCompanyChange() {
  const select = document.getElementById('company_select');
  const opt = select.options[select.selectedIndex];
  const prefix = opt.getAttribute('data-prefix');
  const codeInput = document.getElementById('employee_code_input');
  let currentVal = codeInput.value || '';
  
  // Extract number part
  let numPart = currentVal.replace(/^[A-Za-z]+/, '');
  if (!numPart) numPart = '00001';

  if (prefix && prefix.trim() !== '') {
      codeInput.value = prefix.toUpperCase().trim() + numPart;
  }
}

function openAddDeptQuickModal() {
  document.getElementById('quickDeptModal').style.display = 'flex';
}
function closeAddDeptQuickModal() {
  document.getElementById('quickDeptModal').style.display = 'none';
}

function saveQuickDepartment() {
  const name = document.getElementById('quick_dept_name').value.trim();
  if (!name) return alert('Enter department name');
  
  fetch("{{ route('departments.quick-store') }}", {
      method: "POST",
      headers: {
          "Content-Type": "application/json",
          "X-CSRF-TOKEN": "{{ csrf_token() }}"
      },
      body: JSON.stringify({ name: name })
  })
  .then(res => res.json())
  .then(data => {
      if (data.success) {
          const deptSelect = document.getElementById('department_select');
          const newOpt = new Option(data.department.name, data.department.id, true, true);
          deptSelect.add(newOpt);
          closeAddDeptQuickModal();
          document.getElementById('quick_dept_name').value = '';
      }
  })
  .catch(err => alert("Error adding department: " + err));
}

function openAddCatQuickModal() {
  document.getElementById('quickCatModal').style.display = 'flex';
}
function closeAddCatQuickModal() {
  document.getElementById('quickCatModal').style.display = 'none';
}

function saveQuickCategory() {
  const name = document.getElementById('quick_cat_name').value.trim();
  if (!name) return alert('Enter category name');
  
  fetch("{{ route('categories.quick-store') }}", {
      method: "POST",
      headers: {
          "Content-Type": "application/json",
          "X-CSRF-TOKEN": "{{ csrf_token() }}"
      },
      body: JSON.stringify({ name: name })
  })
  .then(res => res.json())
  .then(data => {
      if (data.success) {
          const catSelect = document.getElementById('category_select');
          const newOpt = new Option(data.category.name, data.category.id, true, true);
          catSelect.add(newOpt);
          closeAddCatQuickModal();
          document.getElementById('quick_cat_name').value = '';
      }
  })
  .catch(err => alert("Error adding category: " + err));
}

async function deleteFaceImageWeb(id, btn) {
    if (!confirm('Permanently delete this face reference image?')) return;
    const tile = btn.closest('.face-img-tile');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';
    try {
        const resp = await fetch(`/employees/face-images/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            }
        });
        const res = await resp.json();
        if (res.success || res.ok) {
            if (tile) {
                tile.style.transition = 'all 0.3s ease';
                tile.style.opacity = '0';
                tile.style.transform = 'scale(0.8)';
                setTimeout(() => tile.remove(), 300);
            }
        } else {
            alert(res.message || 'Error deleting image');
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-trash"></i>';
        }
    } catch (err) {
        // Fallback: mark checkbox for form submit delete if server error
        const cb = tile ? tile.querySelector('input[name="delete_face_image_ids[]"]') : null;
        if (cb) cb.checked = true;
        if (tile) tile.style.opacity = '0.4';
        alert('Marked for deletion upon form submission.');
    }
}

function previewFaceImages(input) {
    const container = document.getElementById('faceImagesPreview');
    if (!container) return;
    container.innerHTML = '';
    if (input.files && input.files.length > 0) {
        Array.from(input.files).forEach(file => {
            const reader = new FileReader();
            reader.onload = (e) => {
                const div = document.createElement('div');
                div.style.cssText = 'position:relative;border-radius:6px;overflow:hidden;border:1px solid #38bdf8;box-shadow:0 1px 3px rgba(0,0,0,0.1)';
                div.innerHTML = `<img src="${e.target.result}" style="width:65px;height:65px;object-fit:cover;display:block">`;
                container.appendChild(div);
            };
            reader.readAsDataURL(file);
        });
    }
}
</script>
@endsection
