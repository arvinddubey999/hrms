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

    <div class="pane active card">
        <h3>Basic Information</h3>
        <p class="muted">Enter the staff member's personal details</p>
        
        <div class="grid-2">
            <div>
                <label>Employee Code (Unique & Auto Generated) *</label>
                <input name="employee_code" value="{{ old('employee_code', $staff->employee_code ?: \App\Models\User::generateNextEmployeeCode()) }}" required readonly style="background:#f3f4f6;font-weight:700">
            </div>
            <div>
                <label>Add Profile Picture</label>
                <input type="file" name="profile_photo" accept="image/*">
            </div>
        </div>

        <div class="grid-2">
            <div><label>First Name *</label><input name="first_name" value="{{ old('first_name', $staff->first_name) }}" required></div>
            <div><label>Last Name</label><input name="last_name" value="{{ old('last_name', $staff->last_name) }}"></div>
        </div>

        <label>Change Employee Status</label>
        <div class="row" style="gap:20px;margin-bottom:12px">
            <label style="font-weight:normal"><input type="radio" name="status" value="active" {{ $staff->status!=='archived'?'checked':'' }}> Active</label>
            <label style="font-weight:normal"><input type="radio" name="status" value="archived" {{ $staff->status==='archived'?'checked':'' }}> Archived</label>
        </div>

        <div class="grid-2">
            <div><label>Phone Number *</label><input name="phone" value="{{ old('phone', $staff->phone) }}" required></div>
            <div><label>Email Address</label><input name="email" value="{{ old('email', $staff->email) }}"></div>
        </div>

        <div class="grid-2">
            <div><label>Password</label><input type="password" name="password" placeholder="{{ $staff->exists ? 'Leave blank to keep current' : 'Enter password' }}"></div>
            <div><label>App Role</label>
                <select name="role">
                    @foreach(['employee'=>'Employee','manager'=>'Manager','admin'=>'Admin'] as $k=>$v)
                        <option value="{{ $k }}" {{ $staff->role===$k?'selected':'' }}>{{ $v }}</option>
                    @endforeach
                </select>
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
                <label>Upload PAN Card Document (Browse)</label>
                <input type="file" name="pan_document" accept="image/*,.pdf">
                @if($staff->pan_document)
                    <div style="font-size:11px;margin-top:4px"><a href="{{ asset('storage/'.$staff->pan_document) }}" target="_blank" style="color:var(--accent)">View Uploaded PAN</a></div>
                @endif
            </div>
            <div>
                <label>Aadhaar Card Number</label>
                <input name="aadhaar" value="{{ $staff->aadhaar }}">
            </div>
        </div>

        <div class="grid-2">
            <div>
                <label>Upload Aadhaar Card Document (Browse)</label>
                <input type="file" name="aadhaar_document" accept="image/*,.pdf">
                @if($staff->aadhaar_document)
                    <div style="font-size:11px;margin-top:4px"><a href="{{ asset('storage/'.$staff->aadhaar_document) }}" target="_blank" style="color:var(--accent)">View Uploaded Aadhaar</a></div>
                @endif
            </div>
            <div>
                <label>Other Additional Documents</label>
                <input type="file" name="documents[]" multiple>
            </div>
        </div>
    </div>

    <div class="pane card">
        <h3>Employment & Organization Details</h3>

        <div class="grid-2">
            <div>
                <label>Company Name (Select from Master) *</label>
                <select name="company_id">
                    <option value="">Select Company Master</option>
                    @foreach($companies as $comp)
                        <option value="{{ $comp->id }}" {{ $staff->company_id==$comp->id?'selected':'' }}>{{ $comp->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label>Department Master (Select from Master) *</label>
                <select name="department_id">
                    <option value="">Select Department Master</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept->id }}" {{ $staff->department_id==$dept->id?'selected':'' }}>{{ $dept->name }}</option>
                    @endforeach
                </select>
            </div>
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
        <label>Add Face Reference Images for AI Recognition</label>
        <input type="file" name="face_images[]" accept="image/*" multiple>
    </div>

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
            <div><label>Monthly/Base Salary (₹)</label><input type="number" step="0.01" name="salary" value="{{ $staff->salary }}"></div>
        </div>
        <label>Week-Off Day</label>
        <select name="week_off_day">
            @foreach(['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'] as $d)
                <option {{ ($staff->week_off_day??'Sunday')===$d?'selected':'' }}>{{ $d }}</option>
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

<script>
function showPane(i, btn) {
  document.querySelectorAll('.pane').forEach((p, idx) => p.classList.toggle('active', idx===i));
  document.querySelectorAll('.form-tabs button').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
}
</script>
@endsection
