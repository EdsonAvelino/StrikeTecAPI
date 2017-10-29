<?php

namespace App\Helpers;

use App\UserAppTokens;

class Push
{
	/**
     * Sends push notification to users.
     *
     * @param  int  $user_idd
     * @return void
     */
	public static function send($userId)
	{
		$tokens = UserAppTokens::where('user_id', $userId)->get();

        // Handle Android/iOS related push notifications
        foreach ($tokens as $token)
            $this->{strtolower($token->os)}($token->token);
	}

	/**
     * Sends push notification to android users.
     *
     * @param  string  $token
     * @return boolean
     */
	private function android($token = '')
	{
        if ( empty($token) ) return false;

        $client = new Client(['base_uri' => 'https://fcm.googleapis.com']);

        $body = ['to' => $token,
                    'data' => [
                        'message' => 'Hey there! This is push notification test.'
                    ]
                ];

        $response = $client->request('post', '/fcm/send', [
                    'headers' => [
                        'Authorization' => 'key=' . env('GOOGLE_FCM_SERVER_KEY'),
                        'Content-Type' => 'application/json'
                    ],
                    'body' => json_encode($body)
                ]);

        $respContent = json_decode($response->getBody()->getContents());

        if (($response->getStatusCode() == 200) && ($respContent->failure == 1)) {
            
            $result = current($respContent->results);

            return $result->error;

        }
        
        return true;
	}

	/**
     * Sends push notification to ios users.
     *
     * @param  string  $token
     * @return boolean
     */
	private function ios($token = '')
	{
        // Put your device token here (without spaces):
        $deviceToken = $token;

        // Put your alert message here:
        $message = 'Hey there! This is iOS push notification test.';

        ////////////////////////////////////////////////////////////////////////////////

        $ctx = stream_context_create();
        stream_context_set_option($ctx, 'ssl', 'local_cert', storage_path(env('APNS_CERT')));
        stream_context_set_option($ctx, 'ssl', 'passphrase', env('APNS_CERT_PASSPHRASE'));

        // Open a connection to the APNS server
        $fp = stream_socket_client('ssl://gateway.push.apple.com:2195', $err, $errstr, 60, STREAM_CLIENT_CONNECT|STREAM_CLIENT_PERSISTENT, $ctx);

        if (!$fp)
            exit("Failed to connect: $err $errstr" . PHP_EOL);

        // echo 'Connected to APNS' . PHP_EOL;

        // Create the payload body
        $body['aps'] = array(
            'alert' => array(
                'body' => $message,
                'action-loc-key' => 'StrikeTec App',
            ),
            'badge' => 2,
            'sound' => 'oven.caf',
            );

        // Encode the payload as JSON
        $payload = json_encode($body);

        // Build the binary notification
        $msg = chr(0) . pack('n', 32) . pack('H*', $deviceToken) . pack('n', strlen($payload)) . $payload;

        // Send it to the server
        $result = fwrite($fp, $msg, strlen($msg));

        // Close the connection to the server
        fclose($fp);
        
        if (!$result) {
            \Log::info("iOSFailure|token:" . $token);

            return false;
        }

		return true;
	}
}