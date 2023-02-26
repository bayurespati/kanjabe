<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\Absensi;
use App\Models\DetailAbsensi;
use App\Models\DetailOvertime;
use App\Models\User;
use App\Models\NotPresent;
use App\Models\Overtime;
use App\Models\Holiday;
use Exception;

class CronjobController extends Controller
{

    /**
     * Checkout User 
     * For Non Shiting, First Shifting and Second Shifthing
     *
     */
    public function checkOutUser()
    {
        $datas = Absensi::where('checkout_status', null)->where('is_shift', 0)->get();

        foreach ($datas as $data) {
            if ($data->is_shift != 3) {
                DB::transaction(function () use ($data) {
                    $data->checkout_status = "System";
                    $data->save();

                    $detail = new DetailAbsensi;
                    $detail->absensi_id = $data->id;
                    $detail->tipe_cek   = "out";
                    $detail->lokasi     = "By system";
                    $detail->long_lat   = "By system";
                    $detail->photo      = "By system";
                    $detail->jam        = Carbon::now();
                    $detail->save();
                });
            }
        }

        return "success";
    }


    /**
     * Checkout User 
     *
     */
    public function checkOutNightShifting()
    {
        $datas = Absensi::whereDate('created_at', Carbon::yesterday())->where('is_shift', 1)->doesnthave('checkOut')->get();

        foreach ($datas as $data) {
            DB::transaction(function () use ($data) {
                $data->checkout_status = "System";
                $data->save();

                $detail = new DetailAbsensi;
                $detail->absensi_id = $data->id;
                $detail->tipe_cek   = "out";
                $detail->lokasi     = "By system";
                $detail->long_lat   = "By system";
                $detail->photo      = "By system";
                $detail->jam        = Carbon::now();
                $detail->save();
            });
        }

        return "success";
    }


    /**
     * User Not Present 
     *
     */
    public function inputNotPresent()
    {
        try {
            $holiday = Holiday::where('tanggal', substr(Carbon::now(), 0, 10))->first();
            if (!$holiday == null)
                return "Its holiday";
            if (date('w') % 6 == 0)
                return "Its weekend";

            $datas = User::where('role_id', '1')->where('is_active', true)->with(['absensi' => function ($absensi) {
                return $absensi->whereDate('created_at', Carbon::now())->get();
            }])->get();

            foreach ($datas as $data) {
                if (count($data->absensi) == 0) {
                    $user = new NotPresent();
                    $user->user_id = $data->id;
                    $user->save();
                }
            }
            return response()->json('Berhasil', 200);
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }


    /**
     * Checkout Overtime 
     *
     */
    public function checkoutOvertime()
    {
        if (date('w') % 6 == 0)
            return "Its weekend";

        $datas = Overtime::whereDate('created_at', Carbon::today())->doesnthave('checkOut')->get();

        foreach ($datas as $data) {
            $detailOvertime = new DetailOvertime();
            $detailOvertime->overtime_id = $data->id;
            $detailOvertime->tipe_cek    = "out";
            $detailOvertime->lokasi      = "By system";
            $detailOvertime->long_lat    = "By system";
            $detailOvertime->jam         = Carbon::now();
            $detailOvertime->save();
        }

        return "success";
    }

    /**
     * Send WA
     *
     */
    public function tester()
    {
        $data       = [
            'phone' => '6281281803746',
            'body' => "Tester",
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

        return [
            'message' => $var['message'],
            'sent' => $var['sent']
        ];
    }
}
