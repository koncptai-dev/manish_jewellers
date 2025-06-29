<?php
namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PushNotificationController extends Controller
{
    public function sendNotificationPendingInstallments(Request $request)
    {
        $users = User::whereNotNull('cm_firebase_token')
            ->where('is_active', 1)
            ->whereHas('installmentPayments')
            ->get();

        if ($users->isEmpty()) {
            Log::warning('No active users found for push notifications.');
            return response()->json(['status' => true, 'message' => 'No users found.']);
        }

        $title           = 'Manish Jwellers';
        $body            = 'You have pending installments. Please pay soon.';
        $credentialsPath = storage_path('app/firebase/firebase_credentials.json');

        if (! file_exists($credentialsPath)) {
            Log::error('Firebase credentials file not found.');
            return response()->json(['status' => false, 'message' => 'Firebase credentials file not found.']);
        }

        $json        = json_decode(file_get_contents($credentialsPath), true);
        $projectId   = $json['project_id'];
        $accessToken = $this->getAccessToken($credentialsPath);

        $results = [];

        foreach ($users as $user) {
            if ($user->cm_firebase_token) {
                $message = [
                    'message' => [
                        'token'        => $user->cm_firebase_token,
                        'notification' => [
                            'title' => $title,
                            'body'  => $body,
                        ],
                    ],
                ];

                $response = Http::withToken($accessToken)
                    ->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", $message);

                if ($response->successful()) {
                    Log::info("Notification sent to user ID {$user->id} successfully!");
                    $results[] = [
                        'user_id' => $user->id,
                        'status'  => true,
                        'message' => 'Notification sent successfully',
                    ];
                } else {
                    Log::error("Failed to send notification to user ID {$user->id}: " . json_encode($response->json()));
                    $results[] = [
                        'user_id' => $user->id,
                        'status'  => false,
                        'message' => 'Failed to send notification',
                    ];
                }
            } else {
                Log::info("No Firebase token found for user ID {$user->id}");
                $results[] = [
                    'user_id' => $user->id,
                    'status'  => false,
                    'message' => 'No Firebase token',
                ];
            }
        }

        return response()->json([
            'status'  => true,
            'message' => 'Push notifications processed.',
            'results' => $results,
        ]);
    }

    protected function getAccessToken($credentialsPath): string
    {
        $scopes = ['https://www.googleapis.com/auth/firebase.messaging'];

        $credentials = new \Google\Auth\Credentials\ServiceAccountCredentials(
            $scopes,
            $credentialsPath
        );

        $token = $credentials->fetchAuthToken();

        return $token['access_token'];
    }

    public function sendNotificationphonepeSubscription()
    {
        $users = User::whereNotNull('cm_firebase_token')
            ->where('is_active', 1)
            ->whereHas('subscriptionMandates', function ($query) {
                $query->where('status', 'ACTIVE')
                    ->where(function ($q) {
                        $q->where('frequency', 'MONTHLY')
                            ->whereRaw('DATE(DATE_ADD(last_deduction_at, INTERVAL 1 MONTH)) = ?', [now()->format('Y-m-d')])
                            ->orWhere(function ($q) {
                                $q->where('frequency', 'WEEKLY')
                                    ->whereRaw('DATE(DATE_ADD(last_deduction_at, INTERVAL 1 WEEK)) = ?', [now()->format('Y-m-d')]);
                            })
                            ->orWhere(function ($q) {
                                $q->where('frequency', 'DAILY')
                                    ->whereRaw('DATE(DATE_ADD(last_deduction_at, INTERVAL 1 DAY)) = ?', [now()->format('Y-m-d')]);
                            });
                    });
            })
            ->with('subscriptionMandates') // Eager load subscription mandates
            ->get();

        if ($users->isEmpty()) {
            return response()->json(['status' => true, 'message' => 'No users found.']);
        }
        $credentialsPath = storage_path('app/firebase/firebase_credentials.json');

        if (! file_exists($credentialsPath)) {
            Log::error('Firebase credentials file not found.');
            return response()->json(['status' => false, 'message' => 'Firebase credentials file not found.']);
        }

        $json        = json_decode(file_get_contents($credentialsPath), true);
        $projectId   = $json['project_id'];
        $accessToken = $this->getAccessToken($credentialsPath);

        $results = [];

        foreach ($users as $user) {
            foreach ($user->subscriptionMandates as $subscription) {
                if ($subscription->status == 'ACTIVE') {
                    // Calculate next deduction date
                    $nextDeductionDate = match ($subscription->frequency) {
                        'DAILY' => now(),
                        'WEEKLY' => now(),
                        'MONTHLY' => now(),
                        default => now(),
                    };

                    $amount = number_format($subscription->amount / 100, 2); // Format the amount (optional)
                    $body   = "Your subscription payment of ₹{$amount} is scheduled for " .
                    $nextDeductionDate->format('d M Y') . ".";
                   
                    // Send notification for this subscription
                    $message = [
                        'message' => [
                            'token'        => $user->cm_firebase_token,
                            'notification' => [
                                'title' => 'Manish Jewellers - Payment Reminder',
                                'body'  => $body,
                            ],
                        ],
                    ];

                    $response = Http::withToken($accessToken)
                        ->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", $message);

                    if ($response->successful()) {
                        Log::info("Notification sent to user ID {$user->id} for subscription ID {$subscription->id} successfully!");
                        $results[] = [
                            'user_id' => $user->id,
                            'status'  => true,
                            'message' => 'Notification sent successfully',
                        ];
                    } else {
                        Log::error("Failed to send notification to user ID {$user->id} for subscription ID {$subscription->id}: " . json_encode($response->json()));
                        $results[] = [
                            'user_id' => $user->id,
                            'status'  => false,
                            'message' => 'Failed to send notification',
                        ];
                    }
                }
            }
        }

        return response()->json([
            'status'  => true,
            'message' => 'Push notifications processed.',
            'results' => $results,
        ]);
    }
}
