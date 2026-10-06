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

            $tokens = User::whereNotNull('fcm_token')
                ->where('fcm_token', '!=', '')
                ->pluck('fcm_token')
                ->unique()
                ->toArray();

            // 1. Send to specific registered tokens (Managers, Admins & Employee)
            foreach ($tokens as $token) {
                self::sendFcmV1Message([
                    'token' => $token,
                    'notification' => [
                        'title' => $title,
                        'body' => $body,
                    ],
                    'data' => [
                        'user_id' => (string) $employee->id,
                        'user_name' => $employee->displayName(),
                        'punch_type' => $formattedType,
                        'location' => $body,
                        'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                    ],
                    'android' => [
                        'priority' => 'HIGH',
                        'notification' => [
                            'sound' => 'default',
                            'channel_id' => 'attendance_channel',
                        ],
                    ],
                ]);
            }

            // 2. Broadcast to "attendance" FCM topic
            self::sendFcmV1Message([
                'topic' => 'attendance',
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                ],
                'data' => [
                    'user_id' => (string) $employee->id,
                    'user_name' => $employee->displayName(),
                    'punch_type' => $formattedType,
                    'location' => $body,
                ],
                'android' => [
                    'priority' => 'HIGH',
                    'notification' => [
                        'sound' => 'default',
                        'channel_id' => 'attendance_channel',
                    ],
                ],
            ]);

        } catch (\Throwable $e) {
            Log::error('Firebase Notification Error: ' . $e->getMessage(), [
                'user_id' => $employee->id,
                'punch_type' => $punchType,
            ]);
        }
    }
    /**
     * Send Push Notification for Task Creation & Re-Assignment.
     */
    public static function sendTaskNotification(User $employee, string $title, string $message, ?int $taskId = null): void
    {
        try {
            if (empty($employee->fcm_token)) {
                Log::info("FCM task notification skipped for user {$employee->id}: No fcm_token");
                return;
            }

            self::sendFcmV1Message([
                'token' => $employee->fcm_token,
                'notification' => [
                    'title' => $title,
                    'body' => $message,
                ],
                'data' => [
                    'task_id' => (string) ($taskId ?? ''),
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
            ]);
        } catch (\Throwable $e) {
            Log::error('Task Push Notification Error: ' . $e->getMessage());
        }
    }

    /**
     * Send payload to FCM HTTP v1 API using Google OAuth2 Access Token.
     */
    private static function sendFcmV1Message(array $messagePayload): bool
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
     * Get or cached Google OAuth2 Access Token generated from service account JSON.
     */
    public static function getGoogleAccessToken(): ?string
    {
        return Cache::remember('firebase_oauth_access_token', 3300, function () {
            $credFile = config('services.firebase.credentials');
            $possiblePaths = [
                public_path($credFile),
                base_path($credFile),
                public_path('hrms-2563e-firebase-adminsdk-fbsvc-4b9f324ea3.json'),
                base_path('hrms-2563e-firebase-adminsdk-fbsvc-4b9f324ea3.json'),
            ];

            $jsonPath = null;
            foreach ($possiblePaths as $path) {
                if (file_exists($path)) {
                    $jsonPath = $path;
                    break;
                }
            }

            if (!$jsonPath) {
                Log::error('Firebase Credentials file not found at configured path: ' . $credFile);
                return null;
            }

            $credentials = json_decode(file_get_contents($jsonPath), true);
            if (!$credentials || empty($credentials['private_key']) || empty($credentials['client_email'])) {
                Log::error('Invalid Firebase Credentials JSON file structure.');
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
        });
    }

    private static function base64UrlEncode(string $data): string
    {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($data));
    }
}
