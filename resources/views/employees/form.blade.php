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
        <button type="button" onclick="showPane(1,this)">Employment & Bank</button>
        <button type="button" onclick="showPane(2,this)">Attendance Settings</button>
        <button type="button" onclick="showPane(3,this)">Payroll & Overtime</button>
    </div>

    <div class="pane active card">
        <h3>Basic Information</h3>
        <p class="muted">Enter the staff member's personal details</p>
        <label>Add Profile Picture</label>
        <input type="file" name="profile_photo" accept="image/*">
        <div class="grid-2">
            <div><label>First Name *</label><input name="first_name" value="{{ old('first_name', $staff->first_name) }}" required></div>
            <div><label>Last Name *</label><input name="last_name" value="{{ old('last_name', $staff->last_name) }}"></div>
        </div>
        <label>Change Employee Status</label>
        <label><input type="radio" name="status" value="active" {{ $staff->status!=='archived'?'checked':'' }}> Active</label>
        <label><input type="radio" name="status" value="archived" {{ $staff->status==='archived'?'checked':'' }}> Archived</label>
        <div class="grid-2">
            <div><label>Phone *</label><input name="phone" value="{{ old('phone', $staff->phone) }}" required></div>
            <div><label>Email Address</label><input name="email" value="{{ old('email', $staff->email) }}"></div>
        </div>
        <div class="grid-2">
            <div><label>Password</label><input type="password" name="password" placeholder="{{ $staff->exists ? 'Leave unchanged to keep' : 'Required' }}"></div>
            <div><label>Role</label>
                <select name="role">
                    @foreach(['employee','manager','admin'] as $r)
                        <option value="{{ $r }}" {{ $staff->role===$r?'selected':'' }}>{{ ucfirst($r) }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <label>Address</label>
        <textarea name="address" rows="3">{{ old('address', $staff->address) }}</textarea>
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
            <div><label>Marital Status</label>
                <select name="marital_status">
                    <option value="">Select status</option>
                    @foreach(['Single','Married','Other'] as $m)
                        <option {{ $staff->marital_status===$m?'selected':'' }}>{{ $m }}</option>
                    @endforeach
                </select>
            </div>
            <div><label>PAN Card Number</label><input name="pan" value="{{ $staff->pan }}" placeholder="ABCDE1234F"></div>
            <div><label>Aadhaar Number</label><input name="aadhaar" value="{{ $staff->aadhaar }}"></div>
        </div>
        <div class="grid-2">
            <div><label>PF Number</label><input name="pf_number" value="{{ $staff->pf_number }}"></div>
            <div><label>UAN</label><input name="uan" value="{{ $staff->uan }}"></div>
        </div>
        <label><input type="checkbox" name="esi_applicable" value="1" {{ $staff->esi_applicable?'checked':'' }}> ESI Applicable</label>
        <label>+ Add Document</label>
        <input type="file" name="documents[]" multiple>
    </div>

    <div class="pane card">
        <h3>Employment Details</h3>
        <div class="grid-2">
            <div><label>Employee Type</label>
                <select name="employee_type">
                    @foreach(['Employee','Admin','Manager'] as $t)
                        <option {{ $staff->employee_type===$t?'selected':'' }}>{{ $t }}</option>
                    @endforeach
                </select>
            </div>
            <div><label>Select Category</label>
                <select name="category_id">
                    <option value="">Select category</option>
                    @foreach($categories as $c)
                        <option value="{{ $c->id }}" {{ $staff->category_id==$c->id?'selected':'' }}>{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="grid-2">
            <div><label>Designation Name</label><input name="designation" value="{{ $staff->designation }}"></div>
            <div><label>Department</label><input name="department" value="{{ $staff->department }}"></div>
        </div>
        <div class="grid-3">
            <div><label>Employee Code</label><input name="employee_code" value="{{ $staff->employee_code }}"></div>
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
        <div class="grid-2">
            <div><label>Bank Name</label><input name="bank_name" value="{{ $staff->bank_name }}"></div>
            <div><label>Branch Name</label><input name="branch_name" value="{{ $staff->branch_name }}"></div>
        </div>
        <label>Bank A/C Holder Name</label>
        <input name="bank_holder" value="{{ $staff->bank_holder }}">
    </div>

    <div class="pane card">
        <h3>Attendance Settings</h3>
        <div class="grid-2">
            <label><input type="checkbox" name="mobile_attendance" value="1" {{ $staff->mobile_attendance?'checked':'' }}> Mobile Attendance — mark from mobile</label>
            <label><input type="checkbox" name="multiple_attendance" value="1" {{ $staff->multiple_attendance?'checked':'' }}> Multiple Attendance</label>
            <label><input type="checkbox" name="shiftwise_attendance" value="1" {{ $staff->shiftwise_attendance?'checked':'' }}> Shiftwise Attendance</label>
            <label><input type="checkbox" name="self_odometer" value="1" {{ $staff->self_odometer?'checked':'' }}> Self Odometer Reading</label>
            <label><input type="checkbox" name="live_tracking" value="1" {{ $staff->live_tracking?'checked':'' }}> Live Tracking</label>
            <div>
                <label>Punch From</label>
                <select name="punch_from">
                    <option value="geofence" {{ $staff->punch_from==='geofence'?'selected':'' }}>Geofence</option>
                    <option value="anywhere" {{ $staff->punch_from==='anywhere'?'selected':'' }}>Anywhere</option>
                </select>
            </div>
        </div>
        <label>Assigned Shift</label>
        <select name="shift_id">
            <option value="">Select shift</option>
            @foreach($shifts as $s)
                <option value="{{ $s->id }}" {{ $staff->shift_id==$s->id?'selected':'' }}>{{ $s->name }} ({{ substr($s->start_time,0,5) }} - {{ substr($s->end_time,0,5) }})</option>
            @endforeach
        </select>
        <label><input type="checkbox" name="ai_selfie" value="1" {{ $staff->ai_selfie?'checked':'' }}> AI Selfie Verification</label>
        <label>+ Add Face Image</label>
        <input type="file" name="face_images[]" accept="image/*" multiple>
        <div class="grid-2" style="margin-top:12px">
            <div><label>No of Casual Leaves</label><input type="number" name="casual_leaves" value="{{ $staff->casual_leaves ?? 12 }}"></div>
            <div><label>No of Sick Leaves</label><input type="number" name="sick_leaves" value="{{ $staff->sick_leaves ?? 6 }}"></div>
            <div><label>No of Privilege Leaves</label><input type="number" name="privilege_leaves" value="{{ $staff->privilege_leaves ?? 6 }}"></div>
            <div><label>No of Emergency Leaves</label><input type="number" name="emergency_leaves" value="{{ $staff->emergency_leaves ?? 2 }}"></div>
        </div>
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
            <div><label>Salary</label><input type="number" step="0.01" name="salary" value="{{ $staff->salary }}"></div>
        </div>
        <label>Week-Off Day</label>
        <select name="week_off_day">
            @foreach(['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'] as $d)
                <option {{ ($staff->week_off_day??'Sunday')===$d?'selected':'' }}>{{ $d }}</option>
            @endforeach
        </select>
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
