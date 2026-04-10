<?php

namespace App\Utility;

use App\Mail\InvoiceEmailManager;
use App\Models\User;
use App\Models\SmsTemplate;
use App\Http\Controllers\OTPVerificationController;
use App\Models\EmailTemplate;
use App\Models\FirebaseNotification;
use App\Notifications\OrderNotification;
use DateTimeImmutable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Mail;

class NotificationUtility
{
    public static function sendOrderPlacedNotification($order, $request = null)
    {
        // Sends email to Customer, Seller and Admin with the invoice pdf attached
        $adminId = get_admin()->id;
        $userIds = array($order->seller_id);
        if ($order->user->email != null) {
            array_push($userIds, $order->user_id);
        }
        if ($order->seller_id != $adminId) {
            array_push($userIds, $adminId);
        }
        $users = User::findMany($userIds);
        foreach ($users as $user) {
            $emailIdentifier = 'order_placed_email_to_' . $user->user_type;
            $emailTemplate = EmailTemplate::whereIdentifier($emailIdentifier)->first();

            if ($emailTemplate != null && $emailTemplate->status == 1) {
                $emailSubject = $emailTemplate->subject;
                $emailSubject = str_replace('[[order_code]]', $order->code, $emailSubject);

                $array['view']    = 'emails.invoice';
                $array['subject'] = $emailSubject;
                $array['order']   = $order;
                if ($emailTemplate->status == 1) {
                    try {
                        Mail::to($user->email)->queue(new InvoiceEmailManager($array));
                    } catch (\Exception $e) {}
                }
            }
        }

        if (addon_is_activated('otp_system') && SmsTemplate::where('identifier', 'order_placement')->first()->status == 1) {
            try {
                $otpController = new OTPVerificationController;
                $otpController->send_order_code($order);
            } catch (\Exception $e) {}
        }

        // Sends in-app notifications to user/seller/admin
        self::sendNotification($order, 'placed');

        // Sends Firebase push notification to the customer
        if ($request != null && get_setting('google_firebase') == 1 && $order->user->device_token != null) {
            self::sendFirebaseNotification(
                deviceToken : $order->user->device_token,
                title       : translate('Order placed !'),
                body        : translate('An order') . ' ' . $order->code . ' ' . translate('has been placed'),
                type        : 'order',
                typeId      : $order->id,
                userId      : $order->user->id,
            );
        }
    }

    public static function sendNotification($order, $order_status)
    {
        $adminId = get_admin()->id;
        $userIds = array($order->user->id, $order->seller_id);
        if ($order->seller_id != $adminId) {
            array_push($userIds, $adminId);
        }
        $users = User::findMany($userIds);

        $order_notification                    = [];
        $order_notification['order_id']        = $order->id;
        $order_notification['order_code']      = $order->code;
        $order_notification['user_id']         = $order->user_id;
        $order_notification['seller_id']       = $order->seller_id;
        $order_notification['status']          = $order_status;

        foreach ($users as $user) {
            $notificationType = get_notification_type('order_' . $order_status . '_' . $user->user_type, 'type');
            if ($notificationType != null && $notificationType->status == 1) {
                $order_notification['notification_type_id'] = $notificationType->id;
                Notification::send($user, new OrderNotification($order_notification));
            }
        }
    }

    /**
     * Send a push notification via Firebase Cloud Messaging V1 API.
     *
     * Uses OAuth2 service account credentials (not the deprecated server key).
     * Compatible with FCM HTTP v1 API: https://fcm.googleapis.com/v1/projects/{project}/messages:send
     */
    public static function sendFirebaseNotification(
        string $deviceToken,
        string $title,
        string $body,
        string $type,
        int    $typeId,
        int    $userId
    ): void {
        try {
            $accessToken = self::getFcmAccessToken();
            $projectId   = env('FIREBASE_PROJECT_ID');
            $url         = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";

            $payload = [
                'message' => [
                    'token'        => $deviceToken,
                    'notification' => [
                        'title' => $title,
                        'body'  => $body,
                    ],
                    'data' => [
                        'item_type'    => $type,
                        'item_type_id' => (string) $typeId,
                    ],
                    'android' => [
                        'notification' => [
                            'sound'        => 'default',
                            'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                        ],
                    ],
                    'apns' => [
                        'payload' => [
                            'aps' => [
                                'sound' => 'default',
                            ],
                        ],
                    ],
                ],
            ];

            $response = Http::withToken($accessToken)
                ->timeout(10)
                ->post($url, $payload);

            if (!$response->successful()) {
                Log::error('FCM V1 send failed', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('FCM notification error: ' . $e->getMessage());
            return;
        }

        // Persist the notification record regardless of delivery result
        $record               = new FirebaseNotification;
        $record->title        = $title;
        $record->text         = $body;
        $record->item_type    = $type;
        $record->item_type_id = $typeId;
        $record->receiver_id  = $userId;
        $record->save();
    }

    /**
     * Generate a short-lived OAuth2 access token for the FCM V1 API
     * using the service account private key (RS256 JWT → token exchange).
     */
    private static function getFcmAccessToken(): string
    {
        $credentialsPath = base_path(env('FIREBASE_CREDENTIALS'));

        if (!file_exists($credentialsPath)) {
            throw new \RuntimeException("Firebase credentials file not found: {$credentialsPath}");
        }

        $credentials = json_decode(file_get_contents($credentialsPath), true);

        $now = new DateTimeImmutable();

        $config = Configuration::forAsymmetricSigner(
            new Sha256(),
            InMemory::plainText($credentials['private_key']),
            InMemory::plainText($credentials['private_key']), // unused for signing, required by interface
        );

        $jwt = $config->builder()
            ->issuedBy($credentials['client_email'])
            ->permittedFor('https://oauth2.googleapis.com/token')
            ->issuedAt($now)
            ->expiresAt($now->modify('+1 hour'))
            ->withClaim('scope', 'https://www.googleapis.com/auth/firebase.messaging')
            ->getToken($config->signer(), $config->signingKey());

        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion'  => $jwt->toString(),
        ]);

        if (!$response->successful() || empty($response->json('access_token'))) {
            throw new \RuntimeException('Failed to obtain FCM access token: ' . $response->body());
        }

        return $response->json('access_token');
    }
}
