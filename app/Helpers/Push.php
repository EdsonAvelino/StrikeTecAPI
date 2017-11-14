<?php

namespace App\Helpers;

use App\UserAppTokens;
use App\Settings;

use GuzzleHttp\Client;

class Push
{
    private static $notValidTokenErrors = ['InvalidRegistration', 'MismatchSenderId'];

    protected static $userId;
    protected static $typeId;
    protected static $pushMessage;
    protected static $opponentUser;
    protected static $extra;

	/**
     * Sends push notification to users.
     *
     * @param  int  $userId
     * @param  int  $typeId
     * @param  string  $pushMessage
     * @param  array | App\User  $opponentUser
     * @param  array $extra
     * @return void
     */
	public static function send($userId, $typeId, $pushMessage = '', $opponentUser, $extra = [])
	{
        // Get user's notification settings
        $notifSettings = Settings::where('user_id', $userId)->first();

        // TODO Check for settings

        self::$userId = $userId;
        self::$typeId = $typeId;
        self::$pushMessage = $pushMessage;
        self::$opponentUser = $opponentUser;
        self::$extra = $extra;

        // Get user app token
		$tokens = UserAppTokens::where('user_id', $userId)->get();

        // Handle Android/iOS related push notifications
        foreach ($tokens as $token)
            self::{strtolower($token->os)}($token->token);
	}

	/**
     * Sends push notification to android users.
     *
     * @param  string  $token
     * @return boolean
     */
	private static function android($token = '')
	{
        if ( empty($token) ) return false;

        $client = new Client(['base_uri' => 'https://fcm.googleapis.com']);

        $body = ['to' => $token,
                    'data' => [
                        'type' => self::$typeId,
                        'push_message' => self::$pushMessage,
                        'opponent_user' => self::$opponentUser
                    ]
                ];
        // Add extra data if not given
        if (sizeof(self::$extra)) {
            $body['data'] = array_merge($body['data'], self::$extra);
        }
        
        \Log::info("Push: ".json_encode($body));

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

            // For FCM, invalid response token will be removed
            // Since we're overwriting tokens for users, I think no need of it
            // if ( in_array($result->error, self::$notValidTokenErrors) )
            //     UserAppTokens::where('token', $token)->delete();

            return false;
        }
        
        return true;
	}

	/**
     * Sends push notification to ios users.
     *
     * @param  string  $token
     * @return boolean
     */
	private static function ios($token = '')
	{
        if ( empty($token) ) return false;

        // Put your device token here (without spaces):
        $deviceToken = $token;

        $ctx = stream_context_create();
        stream_context_set_option($ctx, 'ssl', 'local_cert', storage_path(env('APNS_CERT')));
        stream_context_set_option($ctx, 'ssl', 'passphrase', env('APNS_CERT_PASSPHRASE'));

        // Open a connection to the APNS server
        $remote_socket = 'ssl://gateway.push.apple.com:2195';
        $remote_socket_sandbox = 'ssl://gateway.sandbox.push.apple.com:2195';
        
        $fp = stream_socket_client($remote_socket_sandbox, $err, $errstr, 60, STREAM_CLIENT_CONNECT|STREAM_CLIENT_PERSISTENT, $ctx);

        if (!$fp) {
            // exit("Failed to connect: $err $errstr" . PHP_EOL);
            \Log::info("Failed to connect: $err.'-'.$errstr");
            
            return false;
        }

        // echo 'Connected to APNS' . PHP_EOL;

        // Create the payload body
        $bodyData = ['to' => $token,
                        'type' => self::$typeId,
                        'push_message' => self::$pushMessage,
                        'data' => [
                            'opponent_user' => self::$opponentUser
                        ]
                    ];
        
        \Log::info("Push: ".json_encode($bodyData));

        $body['aps'] = ['alert' => ['body' => $bodyData, 'action-loc-key' => 'StrikeTec App']];

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