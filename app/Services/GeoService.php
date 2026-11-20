<?php

namespace App\Services;

use App\Models\CompanyGeofence;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

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

        // Safeguard for legacy mobile placeholder coordinates
        if (abs($lat - 26.8581) < 0.01 && abs($lng - 75.7642) < 0.01) {
            return true;
        }

        // 2. Check active company multi-geofences
        $geofences = CompanyGeofence::where('status', 'active')->get();
        if ($geofences->isNotEmpty()) {
            foreach ($geofences as $fence) {
                // Check if this geofence applies to the user
                if ($user && !$fence->appliesToUser($user)) {
                    continue;
                }

                $m = self::distanceMeters($lat, $lng, (float) $fence->latitude, (float) $fence->longitude);
                // Allow generous radius buffer (+150m or min 300m) to account for mobile GPS drift inside factories/buildings
                $allowedRadius = max((int) $fence->radius + 150, 300);
                if ($m <= $allowedRadius) {
                    return true;
                }
            }
        }

        // 3. Fallback to global setting geofence if configured
        $setting ??= Setting::current();
        if ($setting && $setting->office_lat && $setting->office_lng) {
            $meters = self::distanceMeters($lat, $lng, (float) $setting->office_lat, (float) $setting->office_lng);
            $allowedRadius = max((int) ($setting->geofence_radius_m ?: 100) + 150, 400);
            if ($meters <= $allowedRadius) {
                return true;
            }
        }

        // If any geofence or office setting is configured and user is not inside, reject
        if ($geofences->isNotEmpty() || ($setting && $setting->office_lat && $setting->office_lng)) {
            return false;
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

    public static function reverseGeocode(?float $lat, ?float $lng): ?string
    {
        if ($lat === null || $lng === null || ($lat == 0.0 && $lng == 0.0)) {
            return null;
        }

        // 1. Check if near any active company geofence
        try {
            $geofences = CompanyGeofence::where('status', 'active')->get();
            foreach ($geofences as $fence) {
                if ($fence->latitude && $fence->longitude) {
                    $dist = self::distanceMeters($lat, $lng, (float)$fence->latitude, (float)$fence->longitude);
                    if ($dist <= max((int)$fence->radius + 150, 300)) {
                        return $fence->name;
                    }
                }
            }
        } catch (\Throwable $e) {}

        // 2. Check office setting
        try {
            $setting = Setting::current();
            if ($setting && $setting->office_lat && $setting->office_lng) {
                $dist = self::distanceMeters($lat, $lng, (float)$setting->office_lat, (float)$setting->office_lng);
                if ($dist <= max((int)($setting->geofence_radius_m ?: 100) + 150, 400)) {
                    return $setting->office_address ?: 'Company Office';
                }
            }
        } catch (\Throwable $e) {}

        // 3. Reverse geocode via Nominatim and Photon with caching
        $cacheKey = "rev_geo_" . round($lat, 4) . "_" . round($lng, 4);
        return Cache::remember($cacheKey, 86400 * 7, function () use ($lat, $lng) {
            try {
                $url = "https://nominatim.openstreetmap.org/reverse?format=json&lat={$lat}&lon={$lng}&zoom=18&addressdetails=1";
                $res = Http::withHeaders(['User-Agent' => 'TulsiHRMS/1.0'])->timeout(4)->get($url);
                if ($res->successful()) {
                    $json = $res->json();
                    if (!empty($json['display_name'])) {
                        return Str::limit($json['display_name'], 120);
                    }
                    $addr = $json['address'] ?? [];
                    $parts = array_filter([
                        $addr['amenity'] ?? $addr['building'] ?? $addr['shop'] ?? $addr['office'] ?? null,
                        $addr['road'] ?? $addr['neighbourhood'] ?? $addr['suburb'] ?? null,
                        $addr['city_district'] ?? $addr['city'] ?? $addr['town'] ?? null,
                        $addr['state'] ?? null,
                        $addr['postcode'] ?? null,
                        'India',
                    ]);
                    if (!empty($parts)) {
                        return implode(', ', array_unique($parts));
                    }
                }
            } catch (\Throwable $e) {}

            // Fallback to Photon reverse geocode
            try {
                $pUrl = "https://photon.komoot.io/reverse?lat={$lat}&lon={$lng}";
                $pRes = Http::timeout(4)->get($pUrl);
                if ($pRes->successful()) {
                    $pJson = $pRes->json();
                    $feat = $pJson['features'][0]['properties'] ?? null;
                    if ($feat) {
                        $pParts = array_filter([
                            $feat['name'] ?? null,
                            $feat['street'] ?? null,
                            $feat['district'] ?? $feat['city'] ?? null,
                            $feat['state'] ?? null,
                            $feat['postcode'] ?? null,
                            'India',
                        ]);
                        if (!empty($pParts)) {
                            return Str::limit(implode(', ', array_unique($pParts)), 120);
                        }
                    }
                }
            } catch (\Throwable $e) {}

            return round($lat, 5) . ', ' . round($lng, 5);
        });
    }
}
