<?php

namespace App\Services;

use App\Models\Absensi;
use Illuminate\Support\Facades\Validator;
use App\Models\LogApprovalOvertime;
use Illuminate\Support\Facades\DB;
use App\Models\DetailOvertime;
use App\Models\Holiday;
use App\Models\Master\User;
use App\Models\Overtime;
use Carbon\Carbon;


class OvertimeService
{
    /**
     * Cehck in Overtime
     */
    public function checkIn($request)
    {
        try {
            DB::transaction(function () use ($request) {

                $overtime = $request->only(
                    'user_id',
                    'subject',
                    'absensi_id',
                    'current',
                );

                $detail = $request->only(
                    'lokasi',
                    'jam',
                );

                $overtime           = Overtime::make($overtime);
                $overtime->status   = 'progress';
                $overtime->approval = json_encode($request->approval);
                $overtime->save();

                $detail              = DetailOvertime::make($detail);
                $detail->long_lat    = $request->long_lat['lat'] . ',' . $request->long_lat['lng'];
                $detail->overtime_id = $overtime->id;
                $detail->tipe_cek    = 'in';
                $detail->save();
            });
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }


    /**
     * Cehck out Overtime
     */
    public function checkOut($request, $overtime)
    {
        try {
            DB::transaction(function () use ($request, $overtime) {
                $payload = $request->only(
                    'lokasi',
                    'jam',
                );

                $overtime->detail = $request->detail;
                $overtime->save();

                $detail              = DetailOvertime::make($payload);
                $detail->long_lat    = $request->long_lat['lat'] . ',' . $request->long_lat['lng'];
                $detail->overtime_id = $overtime->id;
                $detail->tipe_cek    = 'out';
                $detail->save();

                $body = $this->setBodyAtasan($overtime);
                $hp = "081280295238";
                if (!sendNotifToWa($hp, $body))
                    throw new \Exception('{"message":["Gagal mengirim notif"]}');
            });
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }


    /**
     * Approval 
     */
    public function approved($request, $overtime)
    {
        try {
            DB::transaction(function () use ($request, $overtime) {
                $payload = $request->only(
                    'status',
                    'keterangan',
                    'username'
                );
                $log = LogApprovalOvertime::make($payload);
                $log->overtime_id = $overtime->id;
                $log->save();
                $body = null;
                if ($log->status == 'approve') {
                    if (json_decode($overtime->approval)[0] == $request->username) {
                        $overtime->current = json_decode($overtime->approval)[1];
                        $body = $this->setBodyHr($overtime);
                    } else {
                        $overtime->status = 'done';
                        $body = $this->setBodyDone($overtime);
                    }
                    $overtime->save();
                } else {
                    $body = $this->setBodyReject($overtime);
                    $overtime->status = 'reject';
                    $overtime->save();
                }
                if (!sendNotifToWa("081280295238", $body))
                    throw new \Exception('{"message":["Gagal mengirim notif"]}');
            });
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }


    /**
     * Get Report personal
     *
     */
    public function reportPersonal($request)
    {
        $month = $request->month == null ? Carbon::now()->month : $request->month;
        $year = $request->year == null ? Carbon::now()->year : $request->year;
        $size = $request->size == null ? 10 : $request->size;

        $data = Overtime::where('user_id', $request->user_id)
            ->whereYear('created_at', $year)
            ->with(['user', 'detailOvertime', 'logApproval' => function ($log) {
                return $log->with('user');
            }]);

        if ($month != "all")
            $data->whereMonth('created_at', $month);

        return $data->paginate($size);
    }


    /**
     * Get List Approval
     * 
     */
    public function listApprove($request)
    {
        $month = $request->month == null ? Carbon::now()->month : $request->month;
        $year = $request->year == null ? Carbon::now()->year : $request->year;
        $size = $request->size == null ? 10 : $request->size;

        $data = Overtime::where('current', $request->username)
            ->where('status', 'progress')
            ->whereYear('created_at', $year)
            ->with(['user', 'detailOvertime', 'logApproval' => function ($log) {
                return $log->with('user');
            }])
            ->has('checkOut');

        if ($month != "all")
            $data->whereMonth('created_at', $month);

        return $data->paginate($size);
    }


    /**
     * Set Message 
     *
     */
    public function setBodyAtasan($overtime)
    {
        $user = User::where('id', $overtime->user_id)->first();
        $approval = User::where('username', $overtime->current)->first();
        $body   = 'Halo Bpk/Ibu *' . $approval->name . '*'
            . "\xA"
            . "\xA" . 'Test lembur'
            . "\xA" . 'Atas nama ' . $user->name
            . "\xA"
            . "\xA"
            . "\xA" . '#SaatnyaKerja'
            . "\xA" . '#GakNyerah'
            . "\xA" . '#BringITOn'
            . "\xA" . 'GAS GAS GAS'
            . "\xA" . 'PINS ON FIRE 🔥 🔥 🔥';

        return $body;
    }


    /**
     * Set Message 
     *
     */
    public function setBodyReject($overtime)
    {
        $user = User::where('id', $overtime->user_id)->first();
        $approval = User::where('username', $overtime->current)->first();
        $body   = 'Halo Bpk/Ibu *' . $user->name . '*'
            . "\xA"
            . "\xA" . 'Test Reject By' . $approval->name
            . "\xA"
            . "\xA"
            . "\xA" . '#SaatnyaKerja'
            . "\xA" . '#GakNyerah'
            . "\xA" . '#BringITOn'
            . "\xA" . 'GAS GAS GAS'
            . "\xA" . 'PINS ON FIRE 🔥 🔥 🔥';

        return $body;
    }


    /**
     * Set Message 
     *
     */
    public function setBodyHr($overtime)
    {
        $user = User::where('id', $overtime->user_id)->first();
        $approval = User::where('username', $overtime->current)->first();
        $body = 'Halo Bpk/Ibu HR *' . $approval->name . '*'
            . "\xA"
            . "\xA" . 'Test lembur Unutk HR'
            . "\xA" . 'Atas nama ' . $user->name
            . "\xA"
            . "\xA"
            . "\xA" . '#SaatnyaKerja'
            . "\xA" . '#GakNyerah'
            . "\xA" . '#BringITOn'
            . "\xA" . 'GAS GAS GAS'
            . "\xA" . 'PINS ON FIRE 🔥 🔥 🔥';

        return $body;
    }


    /**
     * Set Message 
     *
     */
    public function setBodyDone($overtime)
    {
        $user = User::where('id', $overtime->user_id)->first();
        $body = 'Halo Bpk/Ibu HR *' . $user->name . '*'
            . "\xA"
            . "\xA" . 'Test Done'
            . "\xA"
            . "\xA"
            . "\xA" . '#SaatnyaKerja'
            . "\xA" . '#GakNyerah'
            . "\xA" . '#BringITOn'
            . "\xA" . 'GAS GAS GAS'
            . "\xA" . 'PINS ON FIRE 🔥 🔥 🔥';

        return $body;
    }


    /**
     * Check if already check in
     */
    public function isCheckIn($request)
    {
        $overtime = Overtime::where('user_id', $request->user_id)->whereDate('created_at', Carbon::today())->get();

        return sizeOf($overtime) > 0;
    }



    /**
     * Check if already check out
     */
    public function isCheckOut($overtime)
    {
        if (sizeOf($overtime->checkOut) == 0)
            return false;

        return true;
    }


    /**
     * Check if holiday 
     */
    public function isHoliday()
    {
        $holiday = Holiday::whereDate('tanggal', Carbon::today())->get();

        return sizeOf($holiday) > 0;
    }


    /**
     * Check if 
     */
    public function isAbsen($request)
    {
        $absensi = Absensi::where('user_id', $request->user_id)->whereDate('created_at', Carbon::today())->get();
        return sizeOf($absensi) > 0;
    }


    /**
     * Check if wfh 
     */
    public function isWfh($request)
    {
        if (date('w') % 6 == 0) return false;

        $overtime = Absensi::where('user_id', $request->user_id)->where('kehadiran', 'WFH')->whereDate('created_at', Carbon::today())->get();

        return sizeOf($overtime) > 0;
    }


    /**
     * Validate input for check in
     */
    public function validateCheckIn($request, $model = null)
    {
        $data = [
            'user_id'   => 'required',
            'subject'   => 'required',
            'approval'  => 'required',
            'current'   => 'required',
            'lokasi'    => 'required',
            'long_lat'  => 'required',
            'jam'       => 'required',
        ];

        $messages = [
            'user_id.required'  => 'User tidak boleh kosong',
            'subject.required'  => 'Subject tidak boleh kosong',
            'approval.required' => 'Approval tidak boleh kosong',
            'current'           => 'Current tidak boleh kosong',
            'lokasi'            => 'Lokasi tidak boleh kosong',
            'long_lat'          => 'Coordinat tidak boleh kosong',
            'jam'               => 'Jam tidak boleh kosong',
        ];

        $validator = Validator::make($request->all(), $data, $messages);

        if ($validator->fails())
            return $validator->errors();

        return true;
    }


    /**
     * Validate input for check Out
     */
    public function validateCheckOut($request, $model = null)
    {
        $data = [
            'lokasi'    => 'required',
            'long_lat'  => 'required',
            'jam'       => 'required',
            'detail'    => 'required',
        ];

        $messages = [
            'lokasi.required'   => 'Lokasi tidak boleh kosong',
            'long_lat.required' => 'Coordinat tidak boleh kosong',
            'jam.required'      => 'Jam tidak boleh kosong',
            'detail.required'   => 'Detail tidak boleh kosong',
        ];

        $validator = Validator::make($request->all(), $data, $messages);

        if ($validator->fails())
            return $validator->errors();

        return true;
    }
}
