@extends('layouts.app')
@section('content')
<div class="page-head">
    <h1><a href="{{ route('attendances.index') }}">←</a> {{ $staff->exists ? 'Edit Staff Member' : 'Add New Staff Member' }}</h1>
    <button class="btn" form="staff-form">{{ $staff->exists ? 'Save Staff Member' : 'Create Staff Member' }}</button>
</div>

<form id="staff-form" method="post" enctype="multipart/form-data" action="{{ $staff->exists ? route('employees.update', $staff) : route('employees.store') }}">
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
                <select name="company_id" id="company_select" onchange="onCompanyChange()" required>
                    <option value="">Select Company Master</option>
                    @foreach($companies as $comp)
                        <option value="{{ $comp->id }}" data-prefix="{{ $comp->code_prefix }}" {{ $staff->company_id==$comp->id?'selected':'' }}>{{ $comp->name }} {{ $comp->code_prefix ? '('.$comp->code_prefix.')' : '' }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label>Employee Code *</label>
                <input id="employee_code_input" name="employee_code" value="{{ old('employee_code', $staff->employee_code ?: \App\Models\User::generateNextEmployeeCode()) }}" required style="background:#f8fafc;font-weight:700;letter-spacing:0.5px">
                <small class="muted" style="display:block;margin-top:2px">Rule: Auto prefixes when Company is selected (e.g. RI00001)</small>
            </div>
            <div>
                <label>Add Profile Picture</label>
                <input type="file" name="profile_photo" accept="image/*">
                @if($staff->profile_photo)
                    <div style="margin-top:4px"><img src="{{ asset('storage/'.$staff->profile_photo) }}" style="width:36px;height:36px;border-radius:50%;object-fit:cover"></div>
                @endif
            </div>
        </div>

        <div class="grid-2">
            <div><label>First Name *</label><input name="first_name" value="{{ old('first_name', $staff->first_name) }}" required></div>
            <div><label>Last Name</label><input name="last_name" value="{{ old('last_name', $staff->last_name) }}"></div>
        </div>

        <label>Employee Status</label>
        <div class="row" style="gap:20px;margin-bottom:12px">
            <label style="font-weight:normal"><input type="radio" name="status" value="active" {{ $staff->status!=='archived'?'checked':'' }}> Active</label>
            <label style="font-weight:normal"><input type="radio" name="status" value="archived" {{ $staff->status==='archived'?'checked':'' }}> Archived</label>
        </div>

        <div class="grid-2">
            <div><label>Phone Number *</label><input name="phone" value="{{ old('phone', $staff->phone) }}" required></div>
            <div><label>Email Address</label><input name="email" value="{{ old('email', $staff->email) }}"></div>
        </div>

        <div class="grid-2">
            <div><label>Password</label><input type="password" name="password" placeholder="{{ $staff->exists ? 'Leave blank to keep current' : 'Enter password (default 123456)' }}"></div>
            <div><label>App Role</label>
                <select name="role" id="role_select" onchange="toggleManagerPermissions()">
                    @foreach(['employee'=>'Employee','manager'=>'Manager','admin'=>'Admin'] as $k=>$v)
                        <option value="{{ $k }}" {{ $staff->role===$k?'selected':'' }}>{{ $v }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <!-- Manager Rights & Permissions Section -->
        <div id="manager_permissions_box" style="display:{{ in_array($staff->role, ['manager','admin']) ? 'block' : 'none' }};background:#f8fafc;padding:12px;border-radius:8px;border:1px solid #cbd5e1;margin-bottom:14px">
            <label style="font-weight:600;margin-bottom:6px;display:block;color:var(--accent)"><i class="fa-solid fa-user-shield"></i> Manager Rights & Access Permissions</label>
            <div class="grid-3">
                @php $perms = $staff->permissions ?? ['attendance_view', 'attendance_mark', 'tasks_manage', 'requests_manage']; @endphp
                <label style="font-weight:normal"><input type="checkbox" name="permissions[]" value="attendance_view" {{ in_array('attendance_view', $perms)?'checked':'' }}> View Staff Attendance</label>
                <label style="font-weight:normal"><input type="checkbox" name="permissions[]" value="attendance_mark" {{ in_array('attendance_mark', $perms)?'checked':'' }}> Mark Staff Attendance</label>
                <label style="font-weight:normal"><input type="checkbox" name="permissions[]" value="tasks_manage" {{ in_array('tasks_manage', $perms)?'checked':'' }}> Create & Assign Tasks</label>
                <label style="font-weight:normal"><input type="checkbox" name="permissions[]" value="requests_manage" {{ in_array('requests_manage', $perms)?'checked':'' }}> Approve Leave Requests</label>
                <label style="font-weight:normal"><input type="checkbox" name="permissions[]" value="payroll_view" {{ in_array('payroll_view', $perms)?'checked':'' }}> View Payroll Summaries</label>
                <label style="font-weight:normal"><input type="checkbox" name="permissions[]" value="reports_view" {{ in_array('reports_view', $perms)?'checked':'' }}> Generate Reports</label>
            </div>
        </div>

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
                    <div style="font-size:11px;margin-top:4px"><a href="{{ asset('storage/'.$staff->pan_document) }}" target="_blank" style="color:var(--accent)">View PAN File</a></div>
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
                    <div style="font-size:11px;margin-top:4px"><a href="{{ asset('storage/'.$staff->aadhaar_document) }}" target="_blank" style="color:var(--accent)">View Aadhaar File</a></div>
                @endif
            </div>
            <div>
                <label>Other Additional Documents (Upload Multiple)</label>
                <input type="file" name="documents[]" multiple>
                @if($staff->documents && $staff->documents->count() > 0)
                    <div style="margin-top:6px;display:flex;gap:6px;flex-wrap:wrap">
                        @foreach($staff->documents as $doc)
                            <a href="{{ asset('storage/'.$doc->path) }}" target="_blank" class="chip" style="background:#e0f2fe;color:#0369a1;font-size:11px">
                                <i class="fa-solid fa-file"></i> {{ $doc->original_name ?: 'Document #'.$doc->id }}
                            </a>
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
            <div><label>Category</label>
                <select name="category_id">
                    <option value="">Select category</option>
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
                <label>Punch From</label>
                <select name="punch_from">
                    <option value="geofence" {{ $staff->punch_from==='geofence'?'selected':'' }}>Geofence</option>
                    <option value="anywhere" {{ $staff->punch_from==='anywhere'?'selected':'' }}>Anywhere</option>
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
        <label><input type="checkbox" name="ai_selfie" value="1" {{ $staff->ai_selfie?'checked':'' }}> AI Selfie Verification</label>
        <label style="margin-top:10px;display:block">Face Reference Images for AI Recognition</label>
        <input type="file" name="face_images[]" accept="image/*" multiple>
        @if($staff->faceImages && $staff->faceImages->count() > 0)
            <div style="display:flex;gap:8px;margin-top:8px">
                @foreach($staff->faceImages as $fImg)
                    <img src="{{ asset('storage/'.$fImg->path) }}" style="width:50px;height:50px;object-fit:cover;border-radius:6px;border:1px solid #ccc">
                @endforeach
            </div>
        @endif
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
            <option value="">None (No Week Off)</option>
            @foreach(['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'] as $d)
                <option value="{{ $d }}" {{ ($staff->week_off_day??'Sunday')===$d?'selected':'' }}>{{ $d }}</option>
            @endforeach
        </select>
        
        <div class="grid-2" style="margin-top:12px">
            <div><label>PF Number</label><input name="pf_number" value="{{ $staff->pf_number }}"></div>
            <div><label>UAN</label><input name="uan" value="{{ $staff->uan }}"></div>
        </div>
        <label><input type="checkbox" name="esi_applicable" value="1" {{ $staff->esi_applicable?'checked':'' }}> ESI Applicable</label>
        <label><input type="checkbox" name="overtime_applicable" value="1" {{ $staff->overtime_applicable?'checked':'' }}> Applicable for Overtime</label>
        <label><input type="checkbox" name="view_self_salary" value="1" {{ $staff->view_self_salary?'checked':'' }}> View Self Salary</label>
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

<script>
function showPane(i, btn) {
  document.querySelectorAll('.pane').forEach((p, idx) => p.classList.toggle('active', idx===i));
  document.querySelectorAll('.form-tabs button').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
}

function toggleManagerPermissions() {
  const role = document.getElementById('role_select').value;
  const box = document.getElementById('manager_permissions_box');
  if (role === 'manager' || role === 'admin') {
      box.style.display = 'block';
  } else {
      box.style.display = 'none';
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
</script>
@endsection
