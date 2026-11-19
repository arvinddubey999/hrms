<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FirebaseNotificationService
{
    /**
     * Send Push Notification for Attendance Punch IN / Punch OUT.
     * Notification Format matching Screenshot:
     * Title: Gajanand [ Punch out ]
     * Body:  5RPP+XVJ, Surat, Gujarat, India
     */
    public static function sendPunchNotification(User $employee, string $punchType, ?string $locationText = null): void
    {
        try {
            $formattedType = strtolower($punchType) === 'in' ? 'in' : 'out';
            $title = sprintf('%s [ Punch %s ]', $employee->displayName(), $formattedType);
            $body = $locationText ?: 'Location recorded';

            $basePayload = [
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                ],
                'data' => [
                    'user_id' => (string) $employee->id,
                    'user_name' => (string) $employee->displayName(),
                    'punch_type' => (string) $formattedType,
                    'location' => (string) $body,
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                    'type' => 'attendance',
                ],
                'android' => [
                    'priority' => 'HIGH',
                    'notification' => [
                        'sound' => 'default',
                        'channel_id' => 'attendance_channel',
                    ],
                ],
            ];

            // 1. Direct device token if available
            if (!empty($employee->fcm_token)) {
                self::sendFcmV1Message(array_merge(['token' => $employee->fcm_token], $basePayload));
            }

            // 2. Dedicated user topic so employee's phone receives it reliably
            self::sendFcmV1Message(array_merge(['topic' => 'user_' . $employee->id], $basePayload));

            // 3. Broadcast to "attendance" FCM topic (All subscribed devices)
            self::sendFcmV1Message(array_merge(['topic' => 'attendance'], $basePayload));

            // 4. Send directly to active managers & admins with registered tokens
            $managerTokens = User::whereNotNull('fcm_token')
                ->where('fcm_token', '!=', '')
                ->where('id', '!=', $employee->id)
                ->where(function ($q) {
                    $q->whereIn('role', ['admin', 'super admin', 'superadmin', 'manager'])
                      ->orWhereJsonContains('permissions', 'attendance.view')
                      ->orWhereJsonContains('permissions', 'attendance.mark');
                })
                ->pluck('fcm_token')
                ->unique()
                ->toArray();

            foreach ($managerTokens as $token) {
                self::sendFcmV1Message(array_merge(['token' => $token], $basePayload));
            }

            Log::info("Punch push notification dispatched for User #{$employee->id} ({$formattedType})");
        } catch (\Throwable $e) {
            Log::error('Firebase Notification Error: ' . $e->getMessage(), [
                'user_id' => $employee->id,
                'punch_type' => $punchType,
            ]);
        }
    }

    /**
     * Send Push Notification reminding employee to Punch OUT after shift end.
     */
    public static function sendPunchOutReminderNotification(User $employee, string $shiftEndTime): void
    {
        try {
            $name = $employee->first_name ?: $employee->displayName();
            $title = sprintf('%s, Forgot to Punch Out?', $name);
            $body = sprintf('Your shift ended at %s. Please remember to mark your Punch Out.', $shiftEndTime);

            $basePayload = [
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                ],
                'data' => [
                    'user_id' => (string) $employee->id,
                    'user_name' => (string) $employee->displayName(),
                    'punch_type' => 'out',
                    'action' => 'punch_out_reminder',
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                    'type' => 'attendance',
                ],
                'android' => [
                    'priority' => 'HIGH',
                    'notification' => [
                        'sound' => 'default',
                        'channel_id' => 'attendance_channel',
                    ],
                ],
            ];

            // 1. Direct device token if available
            if (!empty($employee->fcm_token)) {
                self::sendFcmV1Message(array_merge(['token' => $employee->fcm_token], $basePayload));
            }

            // 2. Dedicated user topic
            self::sendFcmV1Message(array_merge(['topic' => 'user_' . $employee->id], $basePayload));

            Log::info("Forgot Punch Out reminder dispatched for User #{$employee->id} ({$shiftEndTime})");
        } catch (\Throwable $e) {
            Log::error('Forgot Punch Out Reminder Error: ' . $e->getMessage(), [
                'user_id' => $employee->id,
            ]);
        }
    }

    /**
     * Send Push Notification for Task Creation, Re-Assignment, Reminders, and Updates.
     */
    public static function sendTaskNotification(User $employee, string $title, string $message, ?int $taskId = null): void
    {
        try {
            $basePayload = [
                'notification' => [
                    'title' => $title,
                    'body' => $message,
                ],
                'data' => [
                    'task_id' => (string) ($taskId ?? ''),
                    'user_id' => (string) $employee->id,
                    'user_name' => (string) $employee->displayName(),
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                    'type' => 'task',
                ],
                'android' => [
                    'priority' => 'HIGH',
                    'notification' => [
                        'sound' => 'default',
                        'channel_id' => 'task_channel',
                    ],
                ],
            ];

            // 1. Direct device token if available
            if (!empty($employee->fcm_token)) {
                self::sendFcmV1Message(array_merge(['token' => $employee->fcm_token], $basePayload));
            }

            // 2. Dedicated user topic so employee's phone receives it reliably
            self::sendFcmV1Message(array_merge(['topic' => 'user_' . $employee->id], $basePayload));

            Log::info("Task push notification dispatched for User #{$employee->id} (Task #{$taskId})");
        } catch (\Throwable $e) {
            Log::error('Task Push Notification Error: ' . $e->getMessage(), [
                'user_id' => $employee->id,
                'task_id' => $taskId,
            ]);
        }
    }

    /**
     * Send payload to FCM HTTP v1 API using Google OAuth2 Access Token.
     */
    public static function sendFcmV1Message(array $messagePayload): bool
    {
        $accessToken = self::getGoogleAccessToken();
        if (!$accessToken) {
            Log::warning('FCM Notification skipped: Failed to obtain Google OAuth2 Access Token.');
            return false;
        }

        $projectId = 'hrms-2563e';
        $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
            'Content-Type' => 'application/json',
        ])->post($url, [
            'message' => $messagePayload,
        ]);

        if ($response->successful()) {
            return true;
        }

        Log::warning('FCM HTTP v1 Error', [
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        return false;
    }

    /**
     * Get or cached Google OAuth2 Access Token generated from service account credentials.
     */
    public static function getGoogleAccessToken(): ?string
    {
        try {
            return Cache::remember('firebase_oauth_access_token', 3300, function () {
                return self::generateOAuthToken();
            });
        } catch (\Throwable $e) {
            Log::warning('FCM Cache access failed, generating token directly: ' . $e->getMessage());
            return self::generateOAuthToken();
        }
    }

    /**
     * Generate fresh Google OAuth2 Access Token by signing JWT with private key.
     */
    public static function generateOAuthToken(): ?string
    {
        $credentials = self::getCredentials();
        if (empty($credentials['private_key']) || empty($credentials['client_email'])) {
            Log::error('Invalid Firebase Credentials structure.');
            return null;
        }

        $now = time();
        $header = self::base64UrlEncode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $payload = self::base64UrlEncode(json_encode([
            'iss' => $credentials['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => $credentials['token_uri'] ?? 'https://oauth2.googleapis.com/token',
            'exp' => $now + 3600,
            'iat' => $now,
        ]));

        $signatureInput = $header . '.' . $payload;
        $signature = '';

        $success = openssl_sign(
            $signatureInput,
            $signature,
            $credentials['private_key'],
            'SHA256'
        );

        if (!$success) {
            Log::error('OpenSSL failed to sign JWT for Firebase OAuth token.');
            return null;
        }

        $jwt = $signatureInput . '.' . self::base64UrlEncode($signature);

        $response = Http::asForm()->post($credentials['token_uri'] ?? 'https://oauth2.googleapis.com/token', [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt,
        ]);

        if ($response->successful()) {
            return $response->json('access_token');
        }

        Log::error('Failed to exchange JWT for Google OAuth access token', [
            'body' => $response->body(),
        ]);

        return null;
    }

    /**
     * Retrieve credentials from file or embedded fallback so it ALWAYS works in any environment.
     */
    public static function getCredentials(): array
    {
        $credFile = config('services.firebase.credentials');
        $possiblePaths = [
            $credFile ? public_path($credFile) : null,
            $credFile ? base_path($credFile) : null,
            public_path('hrms-2563e-firebase-adminsdk-fbsvc-4b9f324ea3.json'),
            base_path('hrms-2563e-firebase-adminsdk-fbsvc-4b9f324ea3.json'),
        ];

        foreach (array_filter($possiblePaths) as $path) {
            if (file_exists($path)) {
                $decoded = json_decode(file_get_contents($path), true);
                if (!empty($decoded['private_key']) && !empty($decoded['client_email'])) {
                    return $decoded;
                }
            }
        }

        // Embedded fallback so FCM push notifications work seamlessly on ANY host/server even if JSON file is missing
        $embedded = 'ewogICJ0eXBlIjogInNlcnZpY2VfYWNjb3VudCIsCiAgInByb2plY3RfaWQiOiAiaHJtcy0yNTYzZSIsCiAgInByaXZhdGVfa2V5X2lkIjogIjRiOWYzMjRlYTNjYTdjZTNmNTFhODRiMjU3YWQxNzc0NWQ5ZWJmNTEiLAogICJwcml2YXRlX2tleSI6ICItLS0tLUJFR0lOIFBSSVZBVEUgS0VZLS0tLS1cbk1JSUV2Z0lCQURBTkJna3Foa2lHOXcwQkFRRUZBQVNDQktnd2dnU2tBZ0VBQW9JQkFRRFVPeDFOUG9jMzNRL2tcbjY3MVo0MlJkUlQ2bnZQVzl3OHl4ZUFTNnJEZVY0VllRQXV4ZitVMkNMWkxBMC9wMFdKL3VwWDZGMk03VEQrdU1cbm5kNGlFMGFqbkVaT3NqWHpGK3NUdGlhcUYxaDRiQTB3bzlNR2EwSU05eGF2b3lKN0JDWlFodjExQ2diSUE5WlpcbnF4cmYrZEZBYXRtWWplcEU5MGR4Mm1TTEZEZ0pVTXN2WDVqZ2hqN3VTdDI3WXJFNTFpSk56S1ZEY1hITkhlTEVcbm5rMHRZUU1lRWlmUDFseGR2Sy9WVm5tZTBCdmI2TEgrSExBZEwrYy9aWUIrTUNuSEVDUlQ5azIzVS9ybng1dmxcbjNnMVhtdnQvSW9OOG9hKzJrNEk0bzFYRitmM05sV2t2QmxuWTJVcjhnMWtrZnBodVRsTEh0VHNPM1d4N0NOdHVcbkU1c3hSOGRqQWdNQkFBRUNnZ0VBSW9EZktWTzZhckZITXVjYk12dERzZURldlg1OFQrR3BRYUQxZzk1dWNGdUxcbmE3R2pUdWNhWEI1Sjl1SXFSd2dHMkZHU0x2TXA0aWtsdzdLc0RuWW1keXIzbmVFSWpGWkllRDFBMlVqNldubVdcbm0yQnhuN1dJV05DWVM1aGJYcmMwcU1XSmZLR29xejZVZHFJdXVGaHhBSkZlZ1pnYmpNMTI1cmVrRTUyeDQ5d3hcbmpPTW5vS2JOM3ZTVFlSWlRwRFVsLzFudVhhK3BBNUZKOEd3OVRvbkpaTFpuQ0FGSUpVTDgwRUxnQlVMb0NWNDdcbm40VWFScHZlV01IdDFrZUJxTmZ6VThhZHdaZmtTd2syTEMwNTFwVUdRbWlaTG4rUUtHNDJrbmZPWFloczlOSEtcbkpjbTlQdmF6NTVWL0wvZW51UkprK1QrTFp6TTkwK3BGMXByUTBqMmd3UUtCZ1FEME4wVytTVkNIY0p1dTBDRDhcbjBMSUg1TmhpNi9nSWpGaCtuY3FyM1RYR29Na1R1QUhHNWdOQWcwR3BzeDRjcWp3cnRsS1dhQVMrdmw3bkZaNVVcblgzbUVGeExEb0JlQ1FpdDNjQVhzbUpEMU1TMFVFOHZ1RW5sZXVGNmYyWWF2VW9DblY0S21RN1Z2S3cwTURTVy9cbjFkSFdJbDBkaHZZbFc2MjQxU0Q3SGRCUm13S0JnUURlZUwya1MxSjA0TGFIQ3MyZTRyc1pvUTBMdXJ6QUJuRXlcblF6QzNvNzJjVUM4QjN1MUhyK2VFVW9GWDhVTm94enBLejd1a0hEMjNWbkY1SktjcnpDU1Mwa0lhNDMyT0l3YmpcbnR0UHFNWWdBalRtdWlzNnRPL1hGQ3R6OWJZMTcrVjJBK3FEeEN2aEk2UG1WRXJLV0lxdkJScEd6VkZ1ajJ1UitcbjMwaCs1TE1CMlFLQmdRQ1lhcDZtZ2c4MldIdHhnUWYxZ1RKN1pzSCtDeE9FRWtOdzBuNFpBVGg4dlFDQnhBZW5cbjRRb0tpMkRCenI1dVdYaXdOMXdYN3RkM1RFemlvRVZ1bHgrNTVzU1pQeU1zOHFzNklib3d6WTg2MS9Rd1lhS1dcbjlRUzJydzZ2YVExOEFsdTFGUksvUGViUWVPZnRxNDd4eVRyckVBdFpVRS96Uk9LSlJzc01yckRESFFLQmdGRHdcbjFPNkZpUWFGQzJPdWhva2NMTDV5WGZDNmpCbkJQa1h2TkRaY0dJeGFqLys2UDlJcEthNTI1aUE4NHkybjdvbHhcbmN1K09adFhoV1grZDJrU1JEVzF0bWlkMlJTUUZrbWdmQ0pMWVdvYjQydGVxSU8vZU5OanZ6dDlWa0hoZWphcHpcbmhVS3Rudy9IMDZadEd4U01vTGk5R0IyS3Y1eXA4eUJGMUhaMmRycGhBb0dCQUkxSTFrdXRHS0loWmlUU2NGZXpcblBwZHdFV01PbUNqQ0haakxhcnc1cEhmZE01UVRpRmU2MW4yVzlIR210WWo1RCtVekFVVlJlem5kZDcyVDZ2Qytcbnc1WDV3b2poM0lraTR4YXFSaVdRU3hKamYySUFzSVdpc2ZSTkd3VEpJeGk1SEd6NUdobnVjWUlMSzJqS0RqZDNcbkJCSlR3NFFsRDZHSE5FRzdIOE04b2RqUFxuLS0tLS1FTkQgUFJJVkFURSBLRVktLS0tLVxuIiwKICAiY2xpZW50X2VtYWlsIjogImZpcmViYXNlLWFkbWluc2RrLWZic3ZjQGhybXMtMjU2M2UuaWFtLmdzZXJ2aWNlYWNjb3VudC5jb20iLAogICJjbGllbnRfaWQiOiAiMTE0NTI4MTcyNDU1NDc0ODQxMDQyIiwKICAiYXV0aF91cmkiOiAiaHR0cHM6Ly9hY2NvdW50cy5nb29nbGUuY29tL28vb2F1dGgyL2F1dGgiLAogICJ0b2tlbl91cmkiOiAiaHR0cHM6Ly9vYXV0aDIuZ29vZ2xlYXBpcy5jb20vdG9rZW4iLAogICJhdXRoX3Byb3ZpZGVyX3g1MDlfY2VydF91cmwiOiAiaHR0cHM6Ly93d3cuZ29vZ2xlYXBpcy5jb20vb2F1dGgyL3YxL2NlcnRzIiwKICAiY2xpZW50X3g1MDlfY2VydF91cmwiOiAiaHR0cHM6Ly93d3cuZ29vZ2xlYXBpcy5jb20vcm9ib3QvdjEvbWV0YWRhdGEveDUwOS9maXJlYmFzZS1hZG1pbnNkay1mYnN2YyU0MGhybXMtMjU2M2UuaWFtLmdzZXJ2aWNlYWNjb3VudC5jb20iLAogICJ1bml2ZXJzZV9kb21haW4iOiAiZ29vZ2xlYXBpcy5jb20iCn0K';
        return json_decode(base64_decode($embedded), true) ?: [];
    }

    private static function base64UrlEncode(string $data): string
    {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($data));
    }
}
