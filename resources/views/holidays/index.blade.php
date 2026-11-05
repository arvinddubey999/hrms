@extends('layouts.app')

@section('content')
<div class="row" style="justify-content:space-between;align-items:center;margin-bottom:18px">
    <div>
        <h1 style="margin:0;font-size:22px;display:flex;align-items:center;gap:10px">
            <i class="fa-regular fa-calendar-check" style="color:var(--accent)"></i> Holidays — {{ $year }}
        </h1>
        <p class="muted" style="margin:4px 0 0;font-size:12.5px">Official company holidays calendar and scheduled festival breaks.</p>
    </div>

    <div style="display:flex;align-items:center;gap:10px">
        <form method="get" class="row" style="gap:8px;align-items:center;margin:0">
            @if($companies->count() > 1 || (auth()->user() && auth()->user()->isSuperAdmin()))
                <select name="company_id" onchange="this.form.submit()" style="font-size:12.5px;padding:6px 10px;border-radius:8px">
                    <option value="">All Companies</option>
                    @foreach($companies as $comp)
                        <option value="{{ $comp->id }}" {{ $companyId==$comp->id?'selected':'' }}>{{ $comp->name }}</option>
                    @endforeach
                </select>
            @endif

            <select name="year" onchange="this.form.submit()" style="font-size:12.5px;padding:6px 12px;border-radius:8px;font-weight:700">
                @for($y = 2024; $y <= 2030; $y++)
                    <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                @endfor
            </select>
        </form>

        @if($canManage)
            <button type="button" class="btn" onclick="openAddHolidayModal()" style="background:#1e293b;color:#fff;border-radius:8px;padding:8px 16px;font-size:12.5px;font-weight:700;display:inline-flex;align-items:center;gap:6px">
                <i class="fa-solid fa-plus"></i> Add Holiday
            </button>
        @endif
    </div>
</div>

<!-- STAT SUMMARY CARDS -->
<div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(220px, 1fr));gap:14px;margin-bottom:20px">
    <div class="card" style="padding:14px 18px;display:flex;align-items:center;gap:14px">
        <div style="width:44px;height:44px;border-radius:12px;background:#e0f2fe;color:#0284c7;display:grid;place-items:center;font-size:20px">
            <i class="fa-solid fa-calendar-days"></i>
        </div>
        <div>
            <div class="muted" style="font-size:12px;font-weight:600">Total Holidays ({{ $year }})</div>
            <div style="font-size:22px;font-weight:800;color:#0f172a">{{ $totalCount }}</div>
        </div>
    </div>

    <div class="card" style="padding:14px 18px;display:flex;align-items:center;gap:14px">
        <div style="width:44px;height:44px;border-radius:12px;background:#dcfce7;color:#16a34a;display:grid;place-items:center;font-size:20px">
            <i class="fa-regular fa-clock"></i>
        </div>
        <div>
            <div class="muted" style="font-size:12px;font-weight:600">Upcoming Holidays</div>
            <div style="font-size:22px;font-weight:800;color:#16a34a">{{ $upcomingCount }}</div>
        </div>
    </div>

    <div class="card" style="padding:14px 18px;display:flex;align-items:center;gap:14px">
        <div style="width:44px;height:44px;border-radius:12px;background:#f1f5f9;color:#64748b;display:grid;place-items:center;font-size:20px">
            <i class="fa-solid fa-check-double"></i>
        </div>
        <div>
            <div class="muted" style="font-size:12px;font-weight:600">Past Holidays</div>
            <div style="font-size:22px;font-weight:800;color:#64748b">{{ $pastCount }}</div>
        </div>
    </div>
</div>

<!-- HOLIDAYS GRID -->
<div class="card" style="padding:20px">
    <div style="display:grid;grid-template-columns:repeat(auto-fill, minmax(340px, 1fr));gap:16px">
        @forelse($holidays as $hol)
            @php
                $isUpcoming = $hol->date->isFuture() || $hol->date->isToday();
                $diffDays = now()->startOfDay()->diffInDays($hol->date->startOfDay(), false);
            @endphp
            <div style="background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:16px;display:flex;flex-direction:column;justify-content:space-between;gap:12px;transition:all 0.15s ease;box-shadow:0 1px 3px rgba(0,0,0,0.05)" onmouseover="this.style.boxShadow='0 4px 12px rgba(0,0,0,0.08)'" onmouseout="this.style.boxShadow='0 1px 3px rgba(0,0,0,0.05)'">
                <div style="display:flex;align-items:flex-start;gap:14px">
                    <!-- Date Badge -->
                    <div style="min-width:54px;height:60px;border-radius:10px;background:{{ $isUpcoming ? '#0284c7' : '#64748b' }};color:#fff;display:flex;flex-direction:column;align-items:center;justify-content:center;flex-shrink:0">
                        <span style="font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px">{{ $hol->date->format('M') }}</span>
                        <span style="font-size:20px;font-weight:800;line-height:1">{{ $hol->date->format('d') }}</span>
                    </div>

                    <!-- Details -->
                    <div style="flex:1;min-width:0">
                        <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px">
                            <span style="font-weight:700;font-size:15px;color:#1e293b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                                {{ $hol->name }}
                            </span>
                            @if($isUpcoming)
                                @if($diffDays === 0)
                                    <span style="background:#fef08a;color:#854d0e;font-size:10px;font-weight:700;padding:2px 6px;border-radius:6px">Today</span>
                                @else
                                    <span style="background:#e0f2fe;color:#0369a1;font-size:10px;font-weight:700;padding:2px 6px;border-radius:6px">In {{ $diffDays }} days</span>
                                @endif
                            @else
                                <span style="background:#f1f5f9;color:#64748b;font-size:10px;font-weight:600;padding:2px 6px;border-radius:6px">Past</span>
                            @endif
                        </div>

                        <div style="font-size:12px;color:#64748b;margin-bottom:6px">
                            <i class="fa-regular fa-calendar" style="margin-right:4px"></i> {{ $hol->date->format('l, d F Y') }}
                        </div>

                        @if($hol->description)
                            <div style="font-size:11.5px;color:#475569;background:#f8fafc;padding:5px 8px;border-radius:6px;border:1px dashed #e2e8f0;margin-top:4px">
                                {{ $hol->description }}
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Applicable Rules & Actions -->
                <div style="display:flex;align-items:center;justify-content:space-between;padding-top:10px;border-top:1px solid #f1f5f9;font-size:11px;color:#64748b">
                    <div>
                        @if(!empty($hol->category_ids) && count($hol->category_ids) > 0)
                            <span style="color:#0369a1;background:#e0f2fe;padding:2px 6px;border-radius:4px;font-weight:600">{{ count($hol->category_ids) }} Categories</span>
                        @else
                            <span style="color:#166534;background:#dcfce7;padding:2px 6px;border-radius:4px;font-weight:600">All Categories</span>
                        @endif
                    </div>

                    @if($canManage)
                        <div style="display:flex;align-items:center;gap:6px">
                            <button type="button" class="btn light" onclick="openEditHolidayModal({{ json_encode([
                                'id' => $hol->id,
                                'name' => $hol->name,
                                'date' => $hol->date ? $hol->date->format('Y-m-d') : '',
                                'description' => $hol->description,
                                'company_ids' => $hol->company_ids,
                                'department_ids' => $hol->department_ids,
                                'category_ids' => $hol->category_ids,
                            ]) }})" style="padding:4px 8px;border-radius:6px;border:1px solid #e2e8f0;font-size:11.5px" title="Edit Holiday">
                                <i class="fa-solid fa-pencil" style="color:#475569"></i>
                            </button>
                            <form method="post" action="{{ route('settings.holidays.destroy', $hol) }}" style="display:inline;margin:0" onsubmit="return confirm('Delete {{ $hol->name }}?')">
                                @csrf @method('delete')
                                <button type="submit" class="btn light" style="padding:4px 8px;border-radius:6px;border:1px solid #fecdd3;font-size:11.5px;color:#dc2626" title="Delete Holiday">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                            </form>
                        </div>
                    @endif
                </div>
            </div>
        @empty
            <div style="grid-column:1/-1;text-align:center;padding:48px 20px;color:#94a3b8">
                <i class="fa-regular fa-calendar-xmark" style="font-size:40px;color:#cbd5e1;margin-bottom:12px;display:block"></i>
                <div style="font-size:15px;font-weight:700;color:#64748b">No Holidays scheduled for {{ $year }}</div>
                <div style="font-size:12.5px;margin-top:4px">Check back later or select another year.</div>
            </div>
        @endforelse
    </div>
</div>

@if($canManage)
<!-- Modal: Add / Edit Holiday -->
<div id="holidayModal" style="display:none;position:fixed;inset:0;background:rgba(15,23,42,0.5);backdrop-filter:blur(3px);z-index:9999;align-items:center;justify-content:center;padding:16px;box-sizing:border-box">
    <div style="background:#fff;width:520px;max-width:96vw;max-height:92vh;border-radius:16px;box-shadow:0 20px 40px rgba(0,0,0,0.2);display:flex;flex-direction:column;overflow:hidden">
        <div style="padding:16px 20px;border-bottom:1px solid #e5e7eb;display:flex;justify-content:space-between;align-items:center;flex-shrink:0">
            <h3 id="holidayModalTitle" style="margin:0;font-size:16px;font-weight:700;color:#111827">Add Holiday</h3>
            <button type="button" onclick="closeHolidayModal()" style="border:0;background:none;font-size:20px;cursor:pointer;color:#64748b;line-height:1">&times;</button>
        </div>

        <form id="holidayForm" method="post" action="{{ route('settings.holidays.store') }}" style="padding:18px 20px;overflow-y:auto;flex:1;display:flex;flex-direction:column;gap:14px;margin:0">
            @csrf
            <input type="hidden" name="_method" id="holiday_form_method" value="post">

            <div>
                <label style="display:block;font-size:12.5px;font-weight:600;color:#374151;margin-bottom:6px">Holiday Name *</label>
                <input name="name" id="holiday_name" placeholder="Enter holiday name (e.g. Diwali / Republic Day)" required style="width:100%;border:1px solid #d1d5db;border-radius:8px;padding:8px 12px;font-size:13px;outline:none;box-sizing:border-box">
            </div>

            <div>
                <label style="display:block;font-size:12.5px;font-weight:600;color:#374151;margin-bottom:6px">Holiday Date</label>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:10px">
                    <div>
                        <label style="font-size:11.5px;font-weight:600;color:#475569;display:block;margin-bottom:4px">From Date *</label>
                        <input type="date" name="date" id="holiday_date" value="{{ now()->toDateString() }}" required style="width:100%;border:1px solid #cbd5e1;border-radius:8px;padding:7px 10px;font-size:12.5px;box-sizing:border-box" onchange="selectedHolidayDateStr = this.value; var td = document.getElementById('holiday_to_date'); if(td) td.min = this.value; if(this.value){ const p = this.value.split('-'); holidayCalYear = parseInt(p[0],10); holidayCalMonth = parseInt(p[1],10)-1; renderHolidayCalendar(); }">
                    </div>
                    <div id="holidayToDateWrapper">
                        <label style="font-size:11.5px;font-weight:600;color:#475569;display:block;margin-bottom:4px">To Date <span style="font-size:10.5px;color:#64748b;font-weight:normal">(Multi-day)</span></label>
                        <input type="date" name="to_date" id="holiday_to_date" placeholder="Leave blank for single day" style="width:100%;border:1px solid #cbd5e1;border-radius:8px;padding:7px 10px;font-size:12.5px;box-sizing:border-box">
                    </div>
                </div>

                <div style="border:1px solid #e5e7eb;border-radius:12px;padding:12px 14px;background:#fff">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px">
                        <button type="button" onclick="navigateHolidayCal(-1)" style="border:0;background:none;font-size:16px;color:#64748b;cursor:pointer;padding:4px 8px">&larr;</button>
                        <strong id="holidayCalTitle" style="font-size:13.5px;color:#1e293b;font-weight:700">October 2026</strong>
                        <button type="button" onclick="navigateHolidayCal(1)" style="border:0;background:none;font-size:16px;color:#64748b;cursor:pointer;padding:4px 8px">&rarr;</button>
                    </div>
                    <div style="display:grid;grid-template-columns:repeat(7,1fr);text-align:center;font-size:11.5px;font-weight:600;color:#64748b;margin-bottom:6px">
                        <div>Su</div><div>Mo</div><div>Tu</div><div>We</div><div>Th</div><div>Fr</div><div>Sa</div>
                    </div>
                    <div id="holidayDaysGrid" style="display:grid;grid-template-columns:repeat(7,1fr);gap:4px;text-align:center"></div>
                </div>
            </div>

            <div>
                <label style="display:block;font-size:12.5px;font-weight:600;color:#374151;margin-bottom:6px">Select Categories</label>
                <div style="border:1px solid #e5e7eb;border-radius:10px;padding:10px 12px;max-height:120px;overflow-y:auto;display:flex;flex-direction:column;gap:6px">
                    @forelse($categories as $cat)
                        <label style="display:flex;align-items:center;gap:8px;font-size:12.5px;color:#334155;cursor:pointer;margin:0">
                            <input type="checkbox" name="category_ids[]" value="{{ $cat->id }}" class="hol-cat-chk" style="width:15px;height:15px;margin:0;accent-color:#1e293b">
                            <span>{{ $cat->name }}</span>
                        </label>
                    @empty
                        <span class="muted" style="font-size:11.5px">No categories configured.</span>
                    @endforelse
                </div>
            </div>

            <!-- 4. Additional Fields (Applicable Companies & Departments) -->
            <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:12px;display:flex;flex-direction:column;gap:10px">
                <div style="font-size:12px;font-weight:700;color:#475569"><i class="fa-solid fa-sliders"></i> Applicable Rules (Optional)</div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
                    <div>
                        <label style="font-size:11.5px;font-weight:600;color:#64748b;margin-bottom:4px;display:block">Applicable Companies (Blank = All)</label>
                        <div style="max-height:85px;overflow-y:auto;background:#fff;border:1px solid #cbd5e1;border-radius:6px;padding:6px 8px;display:flex;flex-direction:column;gap:4px">
                            @foreach($companies as $comp)
                                <label style="display:flex;align-items:center;gap:6px;font-size:11.5px;color:#334155;cursor:pointer;margin:0">
                                    <input type="checkbox" name="company_ids[]" value="{{ $comp->id }}" class="hol-comp-chk" style="width:13px;height:13px;margin:0">
                                    <span>{{ $comp->name }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                    <div>
                        <label style="font-size:11.5px;font-weight:600;color:#64748b;margin-bottom:4px;display:block">Applicable Departments (Blank = All)</label>
                        <div style="max-height:85px;overflow-y:auto;background:#fff;border:1px solid #cbd5e1;border-radius:6px;padding:6px 8px;display:flex;flex-direction:column;gap:4px">
                            @foreach($departments as $dept)
                                <label style="display:flex;align-items:center;gap:6px;font-size:11.5px;color:#334155;cursor:pointer;margin:0">
                                    <input type="checkbox" name="department_ids[]" value="{{ $dept->id }}" class="hol-dept-chk" style="width:13px;height:13px;margin:0">
                                    <span>{{ $dept->name }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div>
                    <label style="font-size:11.5px;font-weight:600;color:#64748b;margin-bottom:4px;display:block">Description</label>
                    <input name="description" id="holiday_description" placeholder="Public or Festival Holiday" style="width:100%;border:1px solid #cbd5e1;border-radius:6px;padding:6px 10px;font-size:12px;background:#fff;box-sizing:border-box">
                </div>
            </div>

            <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:6px;padding-top:10px;border-top:1px solid #e5e7eb">
                <button type="button" onclick="closeHolidayModal()" style="border:1px solid #d1d5db;background:#fff;color:#374151;border-radius:8px;padding:7px 18px;font-size:12.5px;font-weight:600;cursor:pointer">Cancel</button>
                <button type="submit" id="holidaySubmitBtn" style="background:#1e293b;color:#fff;border:0;border-radius:8px;padding:7px 22px;font-size:12.5px;font-weight:700;cursor:pointer">Save Holiday</button>
            </div>
        </form>
    </div>
</div>

<script>
function getLocalDateString(d) {
    d = d || new Date();
    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

let holidayCalYear = new Date().getFullYear();
let holidayCalMonth = new Date().getMonth();
let selectedHolidayDateStr = getLocalDateString();
const monthNamesList = ['January','February','March','April','May','June','July','August','September','October','November','December'];

function renderHolidayCalendar() {
    const titleEl = document.getElementById('holidayCalTitle');
    const gridEl = document.getElementById('holidayDaysGrid');
    if (!titleEl || !gridEl) return;

    titleEl.textContent = monthNamesList[holidayCalMonth] + ' ' + holidayCalYear;
    gridEl.innerHTML = '';

    const firstDayIndex = new Date(holidayCalYear, holidayCalMonth, 1).getDay();
    const daysInMonth = new Date(holidayCalYear, holidayCalMonth + 1, 0).getDate();

    for (let i = 0; i < firstDayIndex; i++) {
        gridEl.appendChild(document.createElement('div'));
    }

    for (let day = 1; day <= daysInMonth; day++) {
        const mStr = String(holidayCalMonth + 1).padStart(2, '0');
        const dStr = String(day).padStart(2, '0');
        const thisDateStr = `${holidayCalYear}-${mStr}-${dStr}`;
        const isSelected = (thisDateStr === selectedHolidayDateStr);

        const dayBtn = document.createElement('div');
        dayBtn.style.padding = '6px 2px';
        dayBtn.style.fontSize = '12px';
        dayBtn.style.cursor = 'pointer';
        dayBtn.style.borderRadius = '6px';
        dayBtn.style.fontWeight = isSelected ? '800' : '500';
        dayBtn.style.color = isSelected ? '#ffffff' : '#1e293b';
        dayBtn.style.background = isSelected ? '#1f2937' : 'transparent';
        dayBtn.textContent = day;

        if (!isSelected) {
            dayBtn.onmouseover = function() { this.style.background = '#f1f5f9'; };
            dayBtn.onmouseout = function() { this.style.background = 'transparent'; };
        }

        dayBtn.onclick = function() {
            selectedHolidayDateStr = thisDateStr;
            document.getElementById('holiday_date').value = thisDateStr;
            var td = document.getElementById('holiday_to_date');
            if (td) td.min = thisDateStr;
            renderHolidayCalendar();
        };

        gridEl.appendChild(dayBtn);
    }
}

function navigateHolidayCal(direction) {
    holidayCalMonth += direction;
    if (holidayCalMonth < 0) {
        holidayCalMonth = 11;
        holidayCalYear--;
    } else if (holidayCalMonth > 11) {
        holidayCalMonth = 0;
        holidayCalYear++;
    }
    renderHolidayCalendar();
}

function openAddHolidayModal() {
    document.getElementById('holidayModalTitle').textContent = 'Add Holiday';
    document.getElementById('holidaySubmitBtn').textContent = 'Save Holiday';
    document.getElementById('holidayForm').action = "{{ route('settings.holidays.store') }}";
    document.getElementById('holiday_form_method').value = 'post';
    document.getElementById('holiday_name').value = '';
    document.getElementById('holiday_description').value = '';

    const today = new Date();
    holidayCalYear = today.getFullYear();
    holidayCalMonth = today.getMonth();
    selectedHolidayDateStr = getLocalDateString(today);
    document.getElementById('holiday_date').value = selectedHolidayDateStr;

    var toEl = document.getElementById('holiday_to_date');
    if (toEl) {
        toEl.value = '';
        toEl.min = selectedHolidayDateStr;
    }
    var toWrap = document.getElementById('holidayToDateWrapper');
    if (toWrap) toWrap.style.display = 'block';

    document.querySelectorAll('.hol-cat-chk').forEach(c => c.checked = false);
    document.querySelectorAll('.hol-comp-chk').forEach(c => c.checked = true);
    document.querySelectorAll('.hol-dept-chk').forEach(c => c.checked = true);
    renderHolidayCalendar();
    document.getElementById('holidayModal').style.display = 'flex';
}

function openEditHolidayModal(hol) {
    document.getElementById('holidayModalTitle').textContent = 'Edit Holiday';
    document.getElementById('holidaySubmitBtn').textContent = 'Update Holiday';
    document.getElementById('holidayForm').action = "/settings/holidays/" + hol.id;
    document.getElementById('holiday_form_method').value = 'put';
    document.getElementById('holiday_name').value = hol.name || '';
    document.getElementById('holiday_description').value = hol.description || '';

    var toWrap = document.getElementById('holidayToDateWrapper');
    if (toWrap) toWrap.style.display = 'none';
    var toEl = document.getElementById('holiday_to_date');
    if (toEl) toEl.value = '';

    const dateStr = (hol.date || '').substring(0, 10);
    selectedHolidayDateStr = dateStr;
    document.getElementById('holiday_date').value = dateStr;

    if (dateStr) {
        const parts = dateStr.split('-');
        holidayCalYear = parseInt(parts[0], 10);
        holidayCalMonth = parseInt(parts[1], 10) - 1;
    }

    const catIds = Array.isArray(hol.category_ids) ? hol.category_ids : [];
    document.querySelectorAll('.hol-cat-chk').forEach(c => {
        c.checked = catIds.includes(parseInt(c.value)) || catIds.includes(String(c.value));
    });

    const compIds = Array.isArray(hol.company_ids) ? hol.company_ids : [];
    document.querySelectorAll('.hol-comp-chk').forEach(c => {
        c.checked = compIds.length === 0 || compIds.includes(parseInt(c.value)) || compIds.includes(String(c.value));
    });

    const deptIds = Array.isArray(hol.department_ids) ? hol.department_ids : [];
    document.querySelectorAll('.hol-dept-chk').forEach(c => {
        c.checked = deptIds.length === 0 || deptIds.includes(parseInt(c.value)) || deptIds.includes(String(c.value));
    });

    renderHolidayCalendar();
    document.getElementById('holidayModal').style.display = 'flex';
}

function closeHolidayModal() {
    document.getElementById('holidayModal').style.display = 'none';
}
</script>
@endif
@endsection

