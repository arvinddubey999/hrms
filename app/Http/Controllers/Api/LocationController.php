<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LocationPing;
use App\Models\User;
use App\Services\AttendanceService;
use App\Services\GeoService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    /**
     * Standard Realtime Ping from Mobile App
     */
    public function ping(Request $request, AttendanceService $attendance)
    {
        $data = $request->validate([
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
            'accuracy' => 'nullable',
            'address' => 'nullable|string',
            'journey_status' => 'nullable|string',
            'points' => 'nullable|integer',
            'battery' => 'nullable',
            'time' => 'nullable|string',
            'time_range' => 'nullable|string',
            'stayed_minutes' => 'nullable|integer',
            'speed' => 'nullable|numeric',
            'date' => 'nullable|date',
        ]);

        $user = $this->resolveUser($request);
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated or user not found'], 401);
        }

        $inside = GeoService::insideOffice((float) $data['lat'], (float) $data['lng']);

        $user->forceFill([
            'last_lat' => $data['lat'],
            'last_lng' => $data['lng'],
            'last_location_at' => now(),
        ])->save();

        $ping = $user->locationPings()->create([
            'lat' => (float) $data['lat'],
            'lng' => (float) $data['lng'],
            'accuracy' => $this->sanitizeAccuracy($data['accuracy'] ?? null),
            'address' => $data['address'] ?? null,
            'journey_status' => $this->sanitizeStatus($data['journey_status'] ?? 'Transit'),
            'points' => !empty($data['points']) ? (int) $data['points'] : 1,
            'battery' => $this->sanitizeBattery($data['battery'] ?? null),
            'time_range' => $data['time_range'] ?? $data['time'] ?? now()->format('h:i A'),
            'stayed_minutes' => !empty($data['stayed_minutes']) ? (int)$data['stayed_minutes'] : null,
            'speed' => !empty($data['speed']) ? (float)$data['speed'] : null,
            'inside_geofence' => $inside,
            'pinged_at' => now(),
        ]);

        $autoOut = null;
        if (! $inside) {
            $autoOut = $attendance->autoOutIfOutside($user, (float) $data['lat'], (float) $data['lng'], $data['address'] ?? null);
        }

        return response()->json([
            'success' => true,
            'inside' => $inside,
            'ping_id' => $ping->id,
            'google_maps_url' => "https://www.google.com/maps?q={$data['lat']},{$data['lng']}",
            'auto_out' => $autoOut ? [
                'id' => $autoOut->id,
                'greeting' => $autoOut->greeting,
            ] : null,
        ]);
    }

    /**
     * Store Timeline Data (Supports single location object or batch array)
     * POST /api/tracking/store or POST /api/tracking/timeline
     */
    public function storeTimeline(Request $request, AttendanceService $attendance)
    {
        $user = $this->resolveUser($request);
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found. Pass valid Bearer token in header or "user_id" / "employee_code" in request body.'
            ], 422);
        }

        // Support both batch payload {"locations": [...]} and single payload
        $locations = $request->input('locations');
        if (!is_array($locations)) {
            // Single location ping
            $single = $request->all();
            if (!isset($single['lat']) || !isset($single['lng'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Missing required lat and lng fields.'
                ], 422);
            }
            $locations = [$single];
        }

        $stored = [];
        $lastLat = null;
        $lastLng = null;
        $lastAddress = null;
        $lastPingedAt = now();

        foreach ($locations as $item) {
            if (!isset($item['lat']) || !isset($item['lng'])) {
                continue;
            }

            $lat = (float) $item['lat'];
            $lng = (float) $item['lng'];
            $accuracy = $this->sanitizeAccuracy($item['accuracy'] ?? null);
            $battery = $this->sanitizeBattery($item['battery'] ?? null);
            $points = isset($item['points']) ? (int) $item['points'] : 1;
            $status = $this->sanitizeStatus($item['journey_status'] ?? $item['status'] ?? 'Transit');
            $address = $item['address'] ?? null;
            $stayedMinutes = isset($item['stayed_minutes']) ? (int)$item['stayed_minutes'] : null;
            $speed = isset($item['speed']) ? (float)$item['speed'] : null;
            $timeRange = $item['time_range'] ?? $item['time'] ?? null;

            // Determine pinged_at timestamp
            $pingedAt = null;
            if (!empty($item['pinged_at'])) {
                try {
                    $pingedAt = Carbon::parse($item['pinged_at']);
                } catch (\Throwable $e) {}
            }
            if (!$pingedAt && !empty($item['date'])) {
                try {
                    $timeStr = !empty($timeRange) ? explode('-', $timeRange)[0] : 'now';
                    $pingedAt = Carbon::parse($item['date'] . ' ' . trim($timeStr));
                } catch (\Throwable $e) {
                    $pingedAt = Carbon::parse($item['date']);
                }
            }
            if (!$pingedAt) {
                $pingedAt = now();
            }

            if (empty($timeRange)) {
                $timeRange = $pingedAt->format('h:i A');
            }

            $inside = GeoService::insideOffice($lat, $lng);

            $record = LocationPing::create([
                'user_id' => $user->id,
                'lat' => $lat,
                'lng' => $lng,
                'accuracy' => $accuracy,
                'address' => $address,
                'journey_status' => $status,
                'points' => $points,
                'battery' => $battery,
                'time_range' => $timeRange,
                'stayed_minutes' => $stayedMinutes,
                'speed' => $speed,
                'inside_geofence' => $inside,
                'pinged_at' => $pingedAt,
            ]);

            $stored[] = [
                'id' => $record->id,
                'lat' => $lat,
                'lng' => $lng,
                'address' => $address,
                'time' => $timeRange,
                'journey_status' => $status,
                'points' => $points,
                'battery' => $battery,
                'google_maps_url' => "https://www.google.com/maps?q={$lat},{$lng}",
            ];

            $lastLat = $lat;
            $lastLng = $lng;
            $lastAddress = $address;
            $lastPingedAt = $pingedAt;
        }

        if ($lastLat !== null && $lastLng !== null) {
            $user->forceFill([
                'last_lat' => $lastLat,
                'last_lng' => $lastLng,
                'last_location_at' => $lastPingedAt,
            ])->save();

            // Auto-out check
            $insideLast = GeoService::insideOffice((float) $lastLat, (float) $lastLng);
            if (!$insideLast) {
                $attendance->autoOutIfOutside($user, (float) $lastLat, (float) $lastLng, $lastAddress);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Tracking timeline stored successfully',
            'saved_count' => count($stored),
            'user' => [
                'id' => $user->id,
                'name' => $user->displayName(),
                'employee_code' => $user->employee_code,
            ],
            'last_location' => [
                'lat' => $lastLat,
                'lng' => $lastLng,
                'address' => $lastAddress,
                'google_maps_url' => "https://www.google.com/maps?q={$lastLat},{$lastLng}",
            ],
            'data' => $stored,
        ]);
    }

    /**
     * Get Timeline Data for Mobile or Web
     * GET /api/tracking/timeline?user_id=1&date=2026-10-15
     */
    public function getTimeline(Request $request)
    {
        $user = $this->resolveUser($request);
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found. Pass valid Bearer token or "user_id" / "employee_code" query parameter.'
            ], 422);
        }

        $date = $request->get('date', now()->toDateString());

        $pings = LocationPing::query()
            ->where('user_id', $user->id)
            ->whereDate('pinged_at', $date)
            ->orderBy('pinged_at', 'asc')
            ->get();

        $totalLocations = $pings->count();
        $totalPoints = $pings->sum(fn($p) => $p->points ?: 1);

        $timeline = [];
        foreach ($pings as $idx => $p) {
            $isLast = ($idx === $totalLocations - 1);
            $indexNumber = $idx + 1; // 1 to N

            $timeline[] = [
                'id' => $p->id,
                'index' => $indexNumber,
                'lat' => (float)$p->lat,
                'lng' => (float)$p->lng,
                'google_maps_url' => "https://www.google.com/maps?q={$p->lat},{$p->lng}",
                'address' => $p->address ?: 'Unknown Location',
                'time' => $p->formatted_time,
                'journey_status' => $p->journey_status ?: ($isLast ? 'End' : ($idx === 0 ? 'Start' : 'Transit')),
                'points' => $p->points ?: 1,
                'accuracy' => $p->accuracy,
                'accuracy_formatted' => $p->accuracy_formatted,
                'battery' => $p->battery,
                'battery_formatted' => $p->battery_formatted,
                'stayed_minutes' => $p->stayed_minutes,
                'speed' => $p->speed,
                'inside_geofence' => (bool)$p->inside_geofence,
                'pinged_at' => $p->pinged_at ? $p->pinged_at->toDateTimeString() : null,
                'is_last' => $isLast,
            ];
        }

        return response()->json([
            'success' => true,
            'date' => $date,
            'employee' => [
                'id' => $user->id,
                'name' => $user->displayName(),
                'employee_code' => $user->employee_code,
                'phone' => $user->phone,
                'company' => $user->company?->name,
                'department' => $user->department?->name,
            ],
            'summary' => [
                'total_locations' => $totalLocations,
                'total_points' => $totalPoints,
                'first_ping' => $pings->first()?->formatted_time,
                'last_ping' => $pings->last()?->formatted_time,
            ],
            'timeline' => $timeline,
        ]);
    }

    /**
     * Helper to resolve User from auth, Bearer token, or request parameters
     */
    protected function resolveUser(Request $request): ?User
    {
        // 1. Authenticated user via session/guard
        $user = $request->user();
        if ($user) return $user;

        // 2. Bearer token in headers
        $bearer = $request->bearerToken();
        if ($bearer) {
            $user = User::where('api_token', $bearer)->first();
            if ($user) return $user;
        }

        // 3. User ID or Employee ID in request
        $userId = $request->input('user_id') ?? $request->input('employee_id');
        if ($userId) {
            $user = User::find($userId);
            if ($user) return $user;
        }

        // 4. Employee code in request
        if ($request->filled('employee_code')) {
            $user = User::where('employee_code', $request->input('employee_code'))->first();
            if ($user) return $user;
        }

        // 5. Phone or Email in request
        if ($request->filled('phone')) {
            $user = User::where('phone', $request->input('phone'))->first();
            if ($user) return $user;
        }

        return null;
    }

    protected function sanitizeBattery($val): ?int
    {
        if ($val === null || $val === '') return null;
        $clean = preg_replace('/[^0-9]/', '', (string)$val);
        return is_numeric($clean) ? (int)$clean : null;
    }

    protected function sanitizeAccuracy($val): ?float
    {
        if ($val === null || $val === '') return null;
        $clean = preg_replace('/[^0-9.]/', '', (string)$val);
        return is_numeric($clean) ? (float)$clean : null;
    }

    protected function sanitizeStatus(?string $val): string
    {
        if (empty($val)) return 'Transit';
        $v = strtolower(trim($val));
        if (str_contains($v, 'start')) return 'Start';
        if (str_contains($v, 'end')) return 'End';
        if (str_contains($v, 'stay')) return 'Stayed';
        return 'Transit';
    }
}

