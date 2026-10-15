@extends('layouts.app')
@section('content')
<div style="max-width:1100px;margin:0 auto;padding:10px 15px 40px">
    <form id="profile-settings-form" method="post" action="{{ route('profile.settings.update') }}">
        @csrf
        
        <!-- Header with Title & Save changes button matching screenshot -->
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:30px;flex-wrap:wrap;gap:12px">
            <div>
                <h2 style="margin:0;font-size:22px;font-weight:700;color:#1e293b">Profile Settings</h2>
                <p class="muted" style="margin:4px 0 0 0;font-size:13px">Manage your personal details and account security.</p>
            </div>
            <button type="submit" style="background:#1e293b;color:#fff;border:none;border-radius:10px;padding:9px 20px;font-size:13.5px;font-weight:600;display:inline-flex;align-items:center;gap:8px;cursor:pointer;box-shadow:0 2px 4px rgba(0,0,0,0.1);transition:background 0.2s" onmouseover="this.style.background='#0f172a'" onmouseout="this.style.background='#1e293b'">
                <i class="fa-regular fa-circle-check"></i> Save changes
            </button>
        </div>

        <!-- Section 1: Personal Information -->
        <div style="display:grid;grid-template-columns:260px 1fr;gap:36px;margin-bottom:36px;align-items:start">
            <div>
                <h3 style="font-size:15px;font-weight:700;color:#1e293b;margin:0 0 6px 0">Personal Information</h3>
                <p style="font-size:13px;color:#64748b;margin:0;line-height:1.4">Update your personal information and contact details</p>
            </div>
            <div style="background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:22px;box-shadow:0 1px 3px rgba(0,0,0,0.03)">
                <div style="display:flex;flex-direction:column;gap:16px">
                    <div>
                        <label style="display:block;font-size:13px;font-weight:600;color:#475569;margin-bottom:6px">First Name</label>
                        <input type="text" value="{{ $user->first_name }}" readonly style="width:100%;background:#f8fafc;color:#64748b;border:1px solid #cbd5e1;border-radius:8px;padding:9px 12px;font-size:14px;cursor:not-allowed" title="Name cannot be changed directly. Contact administrator if needed.">
                    </div>
                    <div>
                        <label style="display:block;font-size:13px;font-weight:600;color:#475569;margin-bottom:6px">Last Name</label>
                        <input type="text" value="{{ $user->last_name }}" readonly style="width:100%;background:#f8fafc;color:#64748b;border:1px solid #cbd5e1;border-radius:8px;padding:9px 12px;font-size:14px;cursor:not-allowed" title="Name cannot be changed directly. Contact administrator if needed.">
                    </div>
                    <div>
                        <label style="display:block;font-size:13px;font-weight:600;color:#334155;margin-bottom:6px">Email</label>
                        <input type="email" name="email" value="{{ old('email', $user->email) }}" placeholder="e.g. name@company.com" style="width:100%;background:#fff;color:#1e293b;border:1px solid #cbd5e1;border-radius:8px;padding:9px 12px;font-size:14px">
                        @error('email')
                            <small style="color:#dc2626;font-weight:600;display:block;margin-top:4px"><i class="fa-solid fa-triangle-exclamation"></i> {{ $message }}</small>
                        @enderror
                    </div>
                    <div>
                        <label style="display:block;font-size:13px;font-weight:600;color:#334155;margin-bottom:6px">Mobile Number *</label>
                        <input type="text" name="phone" value="{{ old('phone', $user->phone) }}" required placeholder="e.g. 9876543210" style="width:100%;background:#fff;color:#1e293b;border:1px solid #cbd5e1;border-radius:8px;padding:9px 12px;font-size:14px">
                        @error('phone')
                            <small style="color:#dc2626;font-weight:600;display:block;margin-top:4px"><i class="fa-solid fa-triangle-exclamation"></i> {{ $message }}</small>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 2: Password -->
        <div style="display:grid;grid-template-columns:260px 1fr;gap:36px;margin-bottom:30px;align-items:start">
            <div>
                <h3 style="font-size:15px;font-weight:700;color:#1e293b;margin:0 0 6px 0">Password</h3>
                <p style="font-size:13px;color:#64748b;margin:0;line-height:1.4">Change your password to keep your account secure</p>
            </div>
            <div style="background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:22px;box-shadow:0 1px 3px rgba(0,0,0,0.03)">
                <div style="display:flex;flex-direction:column;gap:16px">
                    <div>
                        <label style="display:block;font-size:13px;font-weight:600;color:#334155;margin-bottom:6px">New Password</label>
                        <input type="password" id="new_password_input" name="password" minlength="6" placeholder="Leave blank to keep current password" style="width:100%;background:#fff;color:#1e293b;border:1px solid #cbd5e1;border-radius:8px;padding:9px 12px;font-size:14px">
                        @error('password')
                            <small style="color:#dc2626;font-weight:600;display:block;margin-top:4px"><i class="fa-solid fa-triangle-exclamation"></i> {{ $message }}</small>
                        @enderror
                    </div>
                    <div>
                        <label style="display:block;font-size:13px;font-weight:600;color:#334155;margin-bottom:6px">Confirm New Password</label>
                        <input type="password" id="confirm_password_input" name="password_confirmation" minlength="6" placeholder="Confirm your new password" style="width:100%;background:#fff;color:#1e293b;border:1px solid #cbd5e1;border-radius:8px;padding:9px 12px;font-size:14px">
                    </div>
                    <div>
                        <label style="display:inline-flex;align-items:center;gap:8px;font-size:13px;color:#475569;cursor:pointer;user-select:none">
                            <input type="checkbox" id="showPasswordChk" onchange="togglePasswordVisibility(this)" style="width:16px;height:16px;accent-color:#2563eb;cursor:pointer">
                            Show password
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
function togglePasswordVisibility(chk) {
    var p1 = document.getElementById('new_password_input');
    var p2 = document.getElementById('confirm_password_input');
    var type = chk.checked ? 'text' : 'password';
    if (p1) p1.type = type;
    if (p2) p2.type = type;
}
</script>
@endsection
