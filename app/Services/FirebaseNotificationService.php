<?php

namespace App\Services;

use Google_Client;
use Illuminate\Support\Facades\Http;

class FirebaseNotificationService
{
    protected $client;
    protected $projectId;

    public function __construct()
    {
        $credentialsPath = storage_path('app/firebase/firebase_credentials.json');

        $this->client = new Google_Client();
        $this->client->setAuthConfig($credentialsPath);
        $this->client->addScope('https://www.googleapis.com/auth/firebase.messaging');
        $this->client->fetchAccessTokenWithAssertion();

        $json = json_decode(file_get_contents($credentialsPath), true);
        $this->projectId = $json['project_id'];
    }

    public function sendNotification($deviceToken, $title, $body, $data = [])
    {
        $url = "https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send";

        $message = [
            'message' => [
                'token' => $deviceToken,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                ],
                'data' => $data,
            ]
        ];

        $accessToken = $this->client->getAccessToken()['access_token'];

        $response = Http::withToken($accessToken)
            ->withHeaders([
                'Content-Type' => 'application/json',
            ])
            ->post($url, $message);

        return $response->json();
    }
}
