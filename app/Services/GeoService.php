<?php

namespace App\Services;

use App\Models\CompanyGeofence;
use App\Models\Setting;
use App\Models\User;

class GeoService
{
    public static function distanceMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earth = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return 2 * $earth * atan2(sqrt($a), sqrt(1 - $a));
    }

    public static function insideOffice(?float $lat, ?float $lng, ?Setting $setting = null, ?User $user = null): bool
    {
        if ($lat === null || $lng === null) {
            return false;
        }

        // 1. If user has anywhere punch enabled and date validity is active, return true
        if ($user && $user->isAnywherePunchValid()) {
            return true;
        }

        // 2. Check company multi-geofences if user company is set
        if ($user && $user->company_id) {
            $geofences = CompanyGeofence::where('company_id', $user->company_id)
                ->where('status', 'active')
                ->get();

            foreach ($geofences as $fence) {
                // Check category or employee assignment filter if set
                if (!empty($fence->assigned_employees) && !in_array($user->id, $fence->assigned_employees)) {
                    continue;
                }
                if (!empty($fence->assigned_categories) && !in_array($user->category_id, $fence->assigned_categories)) {
                    continue;
                }

                $m = self::distanceMeters($lat, $lng, (float) $fence->latitude, (float) $fence->longitude);
                if ($m <= (int) $fence->radius) {
                    return true;
                }
            }
        }

        // 3. Fallback to global setting geofence
        $setting ??= Setting::current();
        if ($setting->office_lat && $setting->office_lng) {
            $meters = self::distanceMeters($lat, $lng, (float) $setting->office_lat, (float) $setting->office_lng);
            return $meters <= ($setting->geofence_radius_m ?: 100);
        }

        return true;
    }

    public static function greetingFor(User $user, ?\DateTimeInterface $at = null): string
    {
        $hour = (int) ($at?->format('G') ?? now('Asia/Kolkata')->format('G'));
        $name = strtoupper($user->first_name ?: $user->displayName());
        if ($hour < 12) {
            $part = 'GOOD MORNING';
        } elseif ($hour < 17) {
            $part = 'GOOD AFTERNOON';
        } else {
            $part = 'GOOD EVENING';
        }

        return "{$part} {$name}";
    }
}
