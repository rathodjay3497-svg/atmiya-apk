<?php

class FirebaseNotifier
{
    private $serviceAccountFilePath;

    public function __construct($serviceAccountFilePath)
    {
        $this->serviceAccountFilePath = $serviceAccountFilePath;
    }

    private function getAccessToken()
    {
        $serviceAccount = json_decode(file_get_contents($this->serviceAccountFilePath), true);

        $tokenUrl = 'https://oauth2.googleapis.com/token';
        $clientEmail = $serviceAccount['client_email'];
        $privateKey = str_replace('\\n', "\n", $serviceAccount['private_key']);

        $jwtHeader = json_encode(['alg' => 'RS256', 'typ' => 'JWT']);
        $jwtClaim = json_encode([
            'iss' => $clientEmail,
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => 'https://oauth2.googleapis.com/token',
            'exp' => time() + 3600,
            'iat' => time(),
        ]);

        $headerEncoded = base64_encode($jwtHeader);
        $claimEncoded = base64_encode($jwtClaim);

        $signature = '';
        openssl_sign("$headerEncoded.$claimEncoded", $signature, $privateKey, OPENSSL_ALGO_SHA256);
        $signatureEncoded = base64_encode($signature);

        $jwt = "$headerEncoded.$claimEncoded.$signatureEncoded";

        $postFields = [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt,
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $tokenUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postFields));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        curl_close($ch);

        $responseData = json_decode($response, true);
        if (isset($responseData['access_token'])) {
            return $responseData['access_token'];
        } else {
            throw new Exception("Failed to obtain access token: " . json_encode($responseData));
        }
    }

    public function sendNotification($topic, $title, $body)
    {
        $accessToken = $this->getAccessToken();
        $apiUrl = 'https://fcm.googleapis.com/v1/projects/avdyuva-dadb4/messages:send';

        $data = [
            'message' => [
                'topic' => $topic,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                ],
            ],
        ];

        $headers = [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json',
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        $result = curl_exec($ch);
        if ($result === false) {
            throw new Exception('Curl Error: ' . curl_error($ch));
        }

        curl_close($ch);
        return json_decode($result, true);
    }
}
