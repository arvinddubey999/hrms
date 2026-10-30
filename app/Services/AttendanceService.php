<?php

namespace App\Services;

use App\Models\AttendancePunch;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Models\Setting;
use App\Models\User;
use App\Services\FirebaseNotificationService;
use App\Services\GeoService;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

class AttendanceService
{
    public function punch(
        User $user,
        string $type,
        ?float $lat,
        ?float $lng,
        ?string $locationText,
        ?UploadedFile $photo,
        bool $faceDetected,
        string $source = 'mobile',
        ?User $markedBy = null,
        bool $skipGeofence = false,
        ?string $remarks = null,
        ?string $customWorkDate = null,
        ?string $customPunchedAt = null
    ): AttendancePunch {
        $setting = Setting::current();
        $now = $customPunchedAt ? Carbon::parse($customPunchedAt) : now('Asia/Kolkata');
        $workDate = $customWorkDate ?: ($customPunchedAt ? Carbon::parse($customPunchedAt)->toDateString() : $now->toDateString());
        $type = strtolower($type) === 'out' ? 'out' : 'in';

        if ($user->punch_from === 'geofence' && ! $skipGeofence && $source === 'mobile') {
            if (! GeoService::insideOffice($lat, $lng, $setting, $user)) {
                throw ValidationException::withMessages(['lat' => 'You are outside the company location. Attendance can be marked only at office.']);
            }
        }

        if ($user->ai_selfie && $source === 'mobile' && ! $skipGeofence) {
            if (! $faceDetected) {
                throw ValidationException::withMessages(['photo' => 'Face not detected. Please take a clear selfie.']);
            }
        }

        $openIn = AttendancePunch::query()
            ->where('user_id', $user->id)
            ->whereDate('work_date', $workDate)
            ->where('type', 'in')
            ->orderByDesc('punched_at')
            ->first();

        $last = AttendancePunch::query()
            ->where('user_id', $user->id)
            ->whereDate('work_date', $workDate)
            ->orderByDesc('punched_at')
            ->first();

        if (! $user->multiple_attendance && $source !== 'admin') {
            if ($type === 'in' && $openIn && (! $last || $last->type === 'in')) {
                throw ValidationException::withMessages(['type' => 'Already punched IN for this date.']);
            }
            if ($type === 'out' && (! $openIn || ($last && $last->type === 'out'))) {
                throw ValidationException::withMessages(['type' => 'No open IN punch to mark OUT for this date.']);
            }
        }

        $path = null;
        if ($photo) {
            $path = $photo->store('punches', 'public');
        }

        $greeting = $type === 'in' ? GeoService::greetingFor($user, $now) : 'Have a good day '.$user->first_name;

        $punch = AttendancePunch::query()->create([
            'user_id' => $user->id,
            'marked_by' => $markedBy?->id,
            'work_date' => $workDate,
            'type' => $type,
            'source' => $source,
            'punched_at' => $now,
            'lat' => $lat,
            'lng' => $lng,
            'location_text' => $locationText ?: $this->fallbackLocation($lat, $lng, $setting),
            'photo' => $path,
            'face_detected' => $faceDetected,
            'greeting' => $greeting,
            'remarks' => $remarks,
        ]);

        FirebaseNotificationService::sendPunchNotification($user, $type, $punch->location_text);

        return $punch;
    }

    public function autoOutIfOutside(User $user, float $lat, float $lng, ?string $address = null): ?AttendancePunch
    {
        if ($user->punch_from !== 'geofence') {
            return null;
        }
        if (GeoService::insideOffice($lat, $lng)) {
            return null;
        }

        $today = now('Asia/Kolkata')->toDateString();
        $last = AttendancePunch::query()
            ->where('user_id', $user->id)
            ->whereDate('work_date', $today)
            ->orderByDesc('punched_at')
            ->first();

        if (! $last || $last->type !== 'in') {
            return null;
        }

        $punch = AttendancePunch::query()->create([
            'user_id' => $user->id,
            'work_date' => $today,
            'type' => 'out',
            'source' => 'geofence',
            'punched_at' => now('Asia/Kolkata'),
            'lat' => $lat,
            'lng' => $lng,
            'location_text' => $address ?: 'Auto OUT — left company location',
            'face_detected' => false,
            'greeting' => 'You left the company location. Attendance marked OUT.',
        ]);

        FirebaseNotificationService::sendPunchNotification($user, 'out', $punch->location_text);

        return $punch;
    }

    public function isPresentOnDate(User $user, Carbon $date): bool
    {
        return AttendancePunch::query()
            ->where('user_id', $user->id)
            ->whereDate('work_date', $date->toDateString())
            ->where('type', 'in')
            ->exists();
    }

    public function isApplicableHoliday(User $user, Carbon $date): ?Holiday
    {
        return Holiday::query()
            ->whereDate('date', $date->toDateString())
            ->get()
            ->first(fn($h) => 
                $h->isApplicableToCompany($user->company_id) &&
                $h->isApplicableToDepartment($user->department_id) &&
                $h->isApplicableToCategory($user->category_id)
            );
    }

    public function isWeekOffDay(User $user, Carbon $date): bool
    {
        return (!empty($user->week_off_day) && strcasecmp($user->week_off_day, 'None') !== 0)
            ? (strcasecmp($user->week_off_day, $date->format('l')) === 0)
            : false;
    }

    public function isNonWorkingDay(User $user, Carbon $date): bool
    {
        return $this->isWeekOffDay($user, $date) || ($this->isApplicableHoliday($user, $date) !== null);
    }

    /**
     * Find the preceding working day before a non-working cluster (skipping adjacent week-offs and holidays)
     */
    public function getPrecedingWorkingDay(User $user, Carbon $date): Carbon
    {
        $prev = $date->copy()->subDay();
        while ($this->isNonWorkingDay($user, $prev) && $prev->gt($date->copy()->subDays(14))) {
            $prev->subDay();
        }
        return $prev;
    }

    /**
     * Check if employee was present or on approved leave on given date
     */
    public function isPresentOrOnLeave(User $user, Carbon $date): bool
    {
        if ($this->isPresentOnDate($user, $date)) {
            return true;
        }

        return LeaveRequest::query()
            ->where('user_id', $user->id)
            ->where('status', 'approved')
            ->whereDate('from_date', '<=', $date)
            ->whereDate('to_date', '>=', $date)
            ->exists();
    }

    public function isWeekOffQualified(User $user, Carbon $date): bool
    {
        // Client Rule:
        // Week off / holiday credit requires presence on the preceding working day.
        // E.g. If Saturday is working, Sunday is Week Off, Monday is Holiday:
        // - Present on Saturday => Sunday (Week Off) and Monday (Holiday) are both credited.
        // - Absent on Saturday => Sunday and Monday are both marked ABSENT.
        $prevWorkingDay = $this->getPrecedingWorkingDay($user, $date);
        return $this->isPresentOrOnLeave($user, $prevWorkingDay);
    }

    public function isHolidayQualified(User $user, Carbon $holidayDate): bool
    {
        $prevWorkingDay = $this->getPrecedingWorkingDay($user, $holidayDate);
        return $this->isPresentOrOnLeave($user, $prevWorkingDay);
    }

    public function dayStatus(User $user, Carbon $date): string
    {
        $hasIn = $this->isPresentOnDate($user, $date);

        // Check Leave
        $leave = LeaveRequest::query()
            ->where('user_id', $user->id)
            ->where('status', 'approved')
            ->whereDate('from_date', '<=', $date)
            ->whereDate('to_date', '>=', $date)
            ->exists();
        if ($leave) {
            return 'leave';
        }

        // Check Holiday
        $holiday = $this->isApplicableHoliday($user, $date);

        if ($holiday) {
            if ($hasIn) {
                return 'hop'; // Holiday Present (HOP)
            }
            if ($date->isFuture() || $date->isToday() || $this->isHolidayQualified($user, $date)) {
                return 'holiday';
            } else {
                return 'absent'; // Not qualified for holiday credit as per sandwich rule
            }
        }

        // Check Week Off
        $isWeekOffDay = $this->isWeekOffDay($user, $date);

        if ($isWeekOffDay) {
            if ($hasIn) {
                // Rule: WEEK OFF HAI AND WEEK OFF KE DIN AGAR KOI BHI EMPLOYEE PRESENT HOTA HAI TO USKO WOP AANA CHAHIYE , ( WEEK OFF PRESENT )
                return 'wop';
            }
            // Future dates cannot be marked absent in advance - they show as scheduled week_off
            if ($date->isFuture() || $date->isToday()) {
                return 'week_off';
            }
            if ($this->isWeekOffQualified($user, $date)) {
                return 'week_off';
            } else {
                return 'absent';
            }
        }

        if ($hasIn) {
            $firstIn = AttendancePunch::query()
                ->where('user_id', $user->id)
                ->whereDate('work_date', $date->toDateString())
                ->where('type', 'in')
                ->orderBy('punched_at')
                ->first();

            $isLate = false;
            $shiftStart = $user->shift?->start_time;
            if ($shiftStart && $firstIn && $firstIn->punched_at->format('H:i:s') > $shiftStart) {
                $isLate = true;
            }

            // Punch exists -> Present (or Late if after shift start time). Never show '-' for present days.
            return $isLate ? 'late' : 'present';
        }

        if ($date->isToday() || $date->isFuture()) {
            return 'not_marked';
        }

        return 'absent';
    }

    public function getShiftMinutes(User $user): int
    {
        if ($user->shift && $user->shift->start_time && $user->shift->end_time) {
            $start = Carbon::parse($user->shift->start_time);
            $end = Carbon::parse($user->shift->end_time);
            if ($end->lt($start)) {
                $end->addDay();
            }
            $diff = $start->diffInMinutes($end);
            if ($diff > 0) {
                return $diff;
            }
        }
        return 480; // Standard 8 hours
    }

    public function getDayWorkedMinutes(User $user, Carbon $date): int
    {
        $ins = AttendancePunch::query()
            ->where('user_id', $user->id)
            ->whereDate('work_date', $date->toDateString())
            ->where('type', 'in')
            ->orderBy('punched_at')
            ->get();
        $outs = AttendancePunch::query()
            ->where('user_id', $user->id)
            ->whereDate('work_date', $date->toDateString())
            ->where('type', 'out')
            ->orderBy('punched_at')
            ->get();

        $minutes = 0;
        $pairs = max($ins->count(), $outs->count());
        for ($i = 0; $i < $pairs; $i++) {
            if (isset($ins[$i], $outs[$i])) {
                $minutes += $ins[$i]->punched_at->diffInMinutes($outs[$i]->punched_at);
            }
        }
        return $minutes;
    }

    public function monthSummary(User $user, int $year, int $month, ?array $dateRange = null): array
    {
        if ($dateRange && !empty($dateRange)) {
            $days = count($dateRange);
            $dates = $dateRange;
        } else {
            $start = Carbon::create($year, $month, 1);
            $days = $start->daysInMonth;
            $dates = [];
            for ($d = 1; $d <= $days; $d++) {
                $dates[] = Carbon::create($year, $month, $d);
            }
        }

        $present = $absent = $leave = $weekOff = $late = $holiday = $half = $wop = $hop = 0;
        $totalOtMinutes = $totalShortMinutes = 0;
        $rows = [];
        $shiftMinutes = $this->getShiftMinutes($user);

        foreach ($dates as $date) {
            $status = $this->dayStatus($user, $date);

            $holidayObj = $this->isApplicableHoliday($user, $date);

            $ins = AttendancePunch::query()
                ->where('user_id', $user->id)
                ->whereDate('work_date', $date->toDateString())
                ->where('type', 'in')
                ->orderBy('punched_at')
                ->get();
            $outs = AttendancePunch::query()
                ->where('user_id', $user->id)
                ->whereDate('work_date', $date->toDateString())
                ->where('type', 'out')
                ->orderBy('punched_at')
                ->get();

            $hasInNoOut = ($ins->count() > 0 && $outs->count() === 0);

            if ($status === 'present') {
                $present++;
            } elseif ($status === 'late') {
                $late++;
                $present++;
            } elseif ($status === 'wop') {
                $wop++;
            } elseif ($status === 'hop') {
                $hop++;
            } elseif ($status === 'absent') {
                $absent++;
            } elseif ($status === 'leave') {
                $leave++;
            } elseif ($status === 'week_off') {
                $weekOff++;
            } elseif ($status === 'holiday') {
                $holiday++;
            }

            $minutes = 0;
            $pairs = max($ins->count(), $outs->count());
            for ($i = 0; $i < $pairs; $i++) {
                if (isset($ins[$i], $outs[$i])) {
                    $minutes += $ins[$i]->punched_at->diffInMinutes($outs[$i]->punched_at);
                }
            }

            // OT and Short calculation
            $otMins = 0;
            $shortMins = 0;
            if ($minutes > 0) {
                if ($minutes > $shiftMinutes) {
                    $otMins = $minutes - $shiftMinutes;
                    $totalOtMinutes += $otMins;
                } elseif ($minutes < $shiftMinutes) {
                    $shortMins = $shiftMinutes - $minutes;
                    $totalShortMinutes += $shortMins;
                }
            }

            $holidayTitle = null;
            if ($holidayObj) {
                $holidayTitle = $holidayObj->name;
                if (!empty($holidayObj->description)) {
                    $holidayTitle .= ' (' . $holidayObj->description . ')';
                }
            }

            $rows[] = [
                'date' => $date->toDateString(),
                'status' => $status,
                'holiday_title' => $holidayTitle,
                'has_in_no_out' => $hasInNoOut,
                'ins' => $ins,
                'outs' => $outs,
                'hours' => sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60),
                'ot_hours' => sprintf('%02d:%02d', intdiv($otMins, 60), $otMins % 60),
                'short_hours' => sprintf('%02d:%02d', intdiv($shortMins, 60), $shortMins % 60),
                'worked_minutes' => $minutes,
                'ot_minutes' => $otMins,
                'short_minutes' => $shortMins,
            ];
        }

        // Payable formula: Regular Present + Week Off + Holiday + WOP + HOP + Leave
        $payable = $present + $weekOff + $holiday + $wop + $hop + $leave;
        $holidayPresent = $hop;
        $totalOtHoursFormatted = sprintf('%02d:%02d', intdiv($totalOtMinutes, 60), $totalOtMinutes % 60);
        $totalShortHoursFormatted = sprintf('%02d:%02d', intdiv($totalShortMinutes, 60), $totalShortMinutes % 60);

        return compact('present', 'absent', 'leave', 'weekOff', 'late', 'holiday', 'half', 'wop', 'hop', 'holidayPresent', 'days', 'rows', 'payable', 'totalOtMinutes', 'totalShortMinutes', 'totalOtHoursFormatted', 'totalShortHoursFormatted');
    }

    private function fallbackLocation(?float $lat, ?float $lng, Setting $setting): string
    {
        if ($lat && $lng && GeoService::insideOffice($lat, $lng, $setting)) {
            return $setting->company_name.' office';
        }
        if ($lat && $lng) {
            return round($lat, 5).', '.round($lng, 5);
        }

        return 'Unknown';
    }
}
