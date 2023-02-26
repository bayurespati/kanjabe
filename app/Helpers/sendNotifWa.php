<?php

/**
 * Send WA
 *
 */
function sendNotifToWa($hp = null, $body = null)
{
    if ($hp == null || $body == null)
        return false;

    try {
        $data       = [
            'phone' => '62' . substr($hp, 1), // Receivers phone
            'body' => $body,
        ];

        $json       = json_encode($data); // Encode data to JSON
        // URL for request POST /message
        $url        = 'https://eu43.chat-api.com/instance59109/sendMessage?token=bn0w07mp9572ei4t';
        // Make a POST request
        $options    = stream_context_create([
            'http' => [
                'method'  => 'POST',
                'header'  => 'Content-type: application/json',
                'content' => $json
            ]
        ]);
        // Send a request
        $result     = file_get_contents($url, false, $options);
        $var        = json_decode($result, true);

        return true;
    } catch (\Throwable $th) {
        return false;
    }
}
