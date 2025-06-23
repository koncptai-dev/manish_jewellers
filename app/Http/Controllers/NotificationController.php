<?php

namespace App\Http\Controllers;

use App\Http\Resources\NotificationResource;
use Illuminate\Http\Request;
use App\Models\Notification;
use App\Models\NotificationSeen;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use App\Services\FirebaseNotificationService;
use Illuminate\Support\Facades\Http;


class NotificationController extends Controller
{
    protected $firebase;

    public function __construct(FirebaseNotificationService $firebase)
    {
        $this->firebase = $firebase;
    }

    public function sendToDevice(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'title' => 'required|string',
            'body' => 'required|string',
        ]);

        $data = $request->input('data', []);

        $result = $this->firebase->sendNotification(
            $request->token,
            $request->title,
            $request->body,
            $data
        );

        return response()->json($result);
    }

    public function list(Request $request)
    {

        $notification_data = Notification::active()->where(['sent_to' => 'customer']);

        $notification = $notification_data->with('notificationSeenBy')
            ->latest()->paginate($request['limit'], ['*'], 'page', $request['offset']);

        return [
            'total_size' => $notification->total(),
            'limit' => (int)$request['limit'],
            'offset' => (int)$request['offset'],
            'new_notification' => $notification_data->whereDoesntHave('notificationSeenBy')->count(),
            'notification' => $notification->items()
        ];
    }

    public function notification_seen(Request $request)
    {
        $user = $request->user();
        NotificationSeen::updateOrInsert(['user_id' => $user->id, 'notification_id' => $request->id], [
            'created_at' => Carbon::now(),
        ]);

        $notification_count = Notification::active()
            ->where('sent_to', 'customer')
            ->whereDoesntHave('notificationSeenBy')
            ->count();

        return [
            'notification_count' => $notification_count,
        ];
    }

    /**
     * Get logged-in user's notifications.
     */
    public function index()
    {
        $notifications = Auth::user()->notifications; // Fetch all notifications
        return NotificationResource::collection($notifications);
    }

    /**
     * Mark all notifications as read for logged-in user.
     */
    public function markAllAsRead()
    {
        Auth::user()->unreadNotifications->markAsRead();
        return response()->json(['message' => 'All notifications marked as read']);
    }

    /**
     * Store a new notification manually (Optional).
     */
    public function store(Request $request)
    {
        $request->validate([
            'message' => 'required|string',
            'type'    => 'required|string',
        ]);

        $user = Auth::user(); // Get the authenticated user

        $user->notify(new Notification($request->message, $request->type));

        return response()->json(['message' => 'Notification sent successfully']);
    }

    public function sendNotificationUsingToken(Request $request)
    {
        // Validate the incoming request
        $request->validate([
            'device_token' => 'required|string',
            'title' => 'required|string',
            'body' => 'required|string',
        ]);

        $deviceToken = $request->device_token;
        $credentialsPath = storage_path('app/firebase/firebase_credentials.json');

        // Check if Firebase credentials file exists
        if (!file_exists($credentialsPath)) {
            return response()->json(['status' => false, 'error' => 'Credentials file not found.'], 500);
        }

        // Decode the Firebase credentials JSON file
        $json = json_decode(file_get_contents($credentialsPath), true);
        $projectId = $json['project_id'];
        $accessToken = $this->getAccessToken($credentialsPath);

        // Prepare the message to send to the FCM endpoint
        $message = [
            'message' => [
                'token' => $deviceToken,
                'notification' => [
                    'title' => $request->title,
                    'body' => $request->body,
                ],
            ],
        ];

        // Send the notification via HTTP request to Firebase FCM API
        $response = Http::withToken($accessToken)
            ->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", $message);

        // Check if the response is successful
        if ($response->successful()) {
            return response()->json(['status' => true, 'message' => 'Notification sent successfully!'], 200);
        } else {
            $errorResponse = $response->json();

            // Check for specific error codes like "UNREGISTERED"
            if (isset($errorResponse['error']['code']) && $errorResponse['error']['code'] == 404) {
                if ($errorResponse['error']['details'][0]['errorCode'] == 'UNREGISTERED') {
                    // Handle invalid or unregistered token
                    return response()->json(['status' => false, 'error' => 'The provided device token is unregistered.'], 400);
                }
            }

            // If the response is not successful, return a generic error message
            return response()->json(['status' => false, 'error' => 'Failed to send notification.'], 500);
        }
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
}
