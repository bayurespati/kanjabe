<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Master\User;

class NotifController extends Controller
{

    /**
     * Send Notif WA To Subordinate
     *
     */
    public function sendNotifToSubordinate(Request $request)
    {
        $user = User::where('id', $request->id)->first();
        $atasan = $request->nama_atasan;
        $body = $this->setBody($user, $atasan);
        $hp = $user->phone;
        $hp = "081280295238";

        try {
            if (!$this->sendNotifToWa($hp, $body))
                throw new \Exception('{"message":["Gagal mengirim notif"]}');

            return response()->json(['message' => 'Notif behasil terkirim'], 200);
        } catch (\Exception $e) {

            $bagError = [];
            foreach (json_decode($e->getMessage()) as $message) {
                array_push($bagError, $message[0]);
            }
            return response()->json(['message' => $bagError], 400);
        }
    }


    /**
     * Set Body 
     *
     */
    private function setBody($user, $atasan)
    {
        $body   = 'Halo Bpk/Ibu *' . $user->name . '*'
            . "\xA"
            . "\xA" . 'Segera laksanakan kewajiban presensi checkin'
            . "\xA" . 'Via aplikasi absensi Squad IOTA'
            . "\xA" . 'iota.pins.co.id'
            . "\xA"
            . "\xA" . 'ttd *' . $atasan . '*';

        return $body;
    }


    /**
     * Send Message 
     *
     */
    private function sendNotifToWa($hp = null, $body = null)
    {
        if ($hp == null || $body == null)
            return false;
        try {
            $data = [
                'phone' => '62' . substr($hp, 1),
                'body' => $body,
            ];
            $json = json_encode($data); // Encode data to JSON
            // URL for request POST /message
            $url = 'https://eu43.chat-api.com/instance59109/sendMessage?token=bn0w07mp9572ei4t';
            // Make a POST request
            $options = stream_context_create([
                'http' => [
                    'method'  => 'POST',
                    'header'  => 'Content-type: application/json',
                    'content' => $json
                ]
            ]);
            // Send a request
            $result = file_get_contents($url, false, $options);
            return true;
        } catch (\Throwable $th) {
            return false;
        }
    }
}
