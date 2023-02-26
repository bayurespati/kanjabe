<?php

namespace App\Services;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\DetailAbsensi;
use App\Models\NotPresent;
use App\Models\Absensi;
use App\Models\Mitra;
use App\Models\Regional;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class AbsensiService
{

    /**
     * check in
     */
    public function checkIn($request)
    {

        try {
            $id = DB::transaction(function () use ($request) {
                $absensi = new Absensi();
                $absensi->user_id       = $request->user_id;
                $absensi->kehadiran     = $request->kehadiran;
                $absensi->kondisi       = $request->kondisi;
                $absensi->keterangan    = $request->keterangan;
                $absensi->is_shift      = $request->is_shift;
                $absensi->save();

                if ($request->photo == null || $request->photo == "null") {
                    $photo  = null;
                } else {
                    $photo = importImage($request->user_id, $request, 'in');
                }

                $detail_absensi = new DetailAbsensi();
                $detail_absensi->absensi_id = $absensi->id;
                $detail_absensi->tipe_cek   = 'in';
                $detail_absensi->lokasi     = $request->lokasi;
                $detail_absensi->long_lat   = $request->long_lat['lat'] . ',' . $request->long_lat['lng'];
                $detail_absensi->photo      = $photo;
                $detail_absensi->jam        = $request->jam;

                $detail_absensi->save();

                if ($absensi->kondisi == 'cuti' || $absensi->kondisi == 'sakit' || $absensi->kondisi == 'sppd' || $absensi->kondisi == 'izin') {
                    $checkout = $this->checkOut($request, $absensi);

                    if ($checkout == false)
                        return false;
                }

                return $absensi->id;
            });
            return Absensi::where('id', $id)->with('detailAbsensi')->first();
        } catch (\Exception $e) {
            return false;
        }
    }


    /**
     * check out
     */
    public function checkOut($request, $absensi)
    {
        try {
            $id = DB::transaction(function () use ($request, $absensi) {

                if ($request->photo == null || $request->photo == "null") {
                    $photo  = null;
                } else {
                    $photo = importImage($absensi->id, $request, 'in');
                }

                $detail_absensi = new DetailAbsensi();
                $detail_absensi->absensi_id = $absensi->id;
                $detail_absensi->tipe_cek   = 'out';
                $detail_absensi->lokasi     = $request->lokasi;
                $detail_absensi->long_lat   = $request->long_lat['lat'] . ',' . $request->long_lat['lng'];
                $detail_absensi->photo      = $photo;
                $detail_absensi->jam        = $request->jam;
                $detail_absensi->save();

                $absensi->checkout_status = "Normal";
                $absensi->save();

                return $absensi->id;
            });

            return Absensi::where('id', $id)->with('detailAbsensi')->first();
        } catch (\Exception $e) {
            return false;
        }
    }


    /**
     * Get daily personal
     *
     */
    public function dailyPersonal($request)
    {
        $data = Absensi::where('user_id', $request->user_id)
            ->with('detailAbsensi')
            ->latest()
            ->first();

        return $data;
    }


    /**
     * Get daily personal
     *
     */
    public function weeklyPersonal($request)
    {
        $data = Absensi::where('user_id', $request->user_id)
            ->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])
            ->with('detailAbsensi')
            ->orderBy('created_at')
            ->get();

        return $data;
    }


    /**
     * Get Report personal
     *
     */
    public function reportPersonal($request)
    {
        $month = $request->month == null ? Carbon::now()->month : $request->month;
        $year = $request->year == null ? Carbon::now()->year : $request->year;

        $absensi = Absensi::where('user_id', $request->user_id)
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->with('detailAbsensi')
            ->orderBy('created_at', 'ASC')->get();

        $notPresent = NotPresent::where('user_id', $request->user_id)
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->orderBy('created_at', 'ASC')->get();

        return $absensi->merge($notPresent)->sortBy('created_at')->toArray();
    }


    /**
     * Report personal
     *
     */
    public function summary($request)
    {
        $month = $request->month == null ? Carbon::now()->month : $request->month;
        $year = $request->year == null ? Carbon::now()->year : $request->year;

        $temp = Absensi::where('user_id', $request->user_id)
            ->whereYear('created_at', $year)
            ->with('detailAbsensi')
            ->orderBy('created_at');

        if ($month != "all")
            $temp->whereMonth('created_at', $month);

        $datas = $temp->get();

        $wfh = $wfo = 0;
        $telat = $izin = $sakit = $cuti = $sppd = 0;

        foreach ($datas as $data) {

            $jam = strtotime(substr($data->detailAbsensi[0]['jam'], -8));
            $tipe_shift = $data->is_shift;

            if ($this->isLate($jam, $tipe_shift))
                $telat++;

            if ($data->kondisi == 'Sakit')
                $sakit++;

            if ($data->kondisi == 'izin')
                $izin++;

            if ($data->kondisi == 'cuti')
                $cuti++;

            if ($data->kondisi == 'sppd')
                $sppd++;

            if ($data->kehadiran == 'WFH')
                $wfh++;

            if ($data->kehadiran == 'WFO')
                $wfo++;
        }

        return [
            "presence" => [
                ["name" => "hadir", "value" => count($datas) - $sakit - $izin],
                ["name" => "sppd", "value" => $sppd],
                ["name" => "izin", "value" => $izin],
                ["name" => "cuti", "value" => $cuti],
                ["name" => "sakit", "value" => $sakit],
                ["name" => "telat", "value" => $telat],
                ["name" => "absent", "value" => $this->getNotPresent($request, $request->user_id)],
            ],
            "work" => [
                ["name" => "WFH", "value" => $wfh],
                ["name" => "WFO", "value" => $wfo],
            ],
        ];
    }


    /**
     * Get Employe
     */
    public function getEmploye($request)
    {

        try {
            $month = $request->month == null ? Carbon::now()->month : $request->month;
            $year = $request->year == null ? Carbon::now()->year : $request->year;

            $users = User::where('is_active', true);

            if ($request->regional_id !== null)
                $users->where('regional_id', $request->regional_id);

            if ($request->mitra_id !== null)
                $users->where('mitra_id', $request->mitra_id);

            return $users->where('role_id', 1)
                ->with([
                    'absensi' => function ($absensi) use ($month, $year) {
                        $absensi->whereYear('created_at', $year)->with('detailAbsensi');
                        if ($month != "all")
                            $absensi->whereMonth('created_at', $month);
                    },
                    'notPresent' => function ($notPresent) use ($month, $year) {
                        $notPresent->whereYear('created_at', $year);
                        if ($month != "all")
                            $notPresent->whereMonth('created_at', $month);
                    },
                    'regional'
                ])->get()
                ->map(function ($user) {
                    $wfh = $wfo = $telat = $cuti = $sppd = $sakit = $izin = 0;

                    foreach ($user->absensi as $absen) {
                        $jam        = strtotime(substr($absen->detailAbsensi[0]['jam'], -8));
                        $kehadiran  = $absen->kehadiran;
                        $kondisi    = $absen->kondisi;
                        $tipe_shift = $absen->is_shift;

                        if ($kondisi == 'Sehat') {
                            if ($this->isLate($jam, $tipe_shift))
                                $telat++;
                            if ($kehadiran == 'WFH')
                                $wfh++;
                            if ($kehadiran == 'WFO')
                                $wfo++;
                        } else {
                            if ($kondisi == 'Sakit')
                                $sakit++;
                            if ($kondisi == 'cuti')
                                $cuti++;
                            if ($kondisi == 'sppd')
                                $sppd++;
                            if ($kondisi == 'izin')
                                $izin++;
                        }
                    }

                    return [
                        "id"          => $user['id'],
                        "name"        => $user['name'],
                        "witel"       => $user['witel'],
                        "posisi"      => $user['posisi'],
                        "image_url"   => $user->photo,
                        "wfh"         => $wfh,
                        "wfo"         => $wfo,
                        "telat"       => $telat,
                        "sakit"       => $sakit,
                        "tidak_hadir" => count($user->notPresent),
                        "cuti"        => $cuti,
                        "sppd"        => $sppd,
                        "izin"        => $izin,
                    ];
                });
        } catch (\Exception $e) {
            return false;
        }
    }


    /**
     * Daily all User Daily
     *
     */
    public function usersDaily($request)
    {
        $regional = $this->getRegional();
        $mitra = $this->getMitra();
        $telat = $wfh = $wfo = $tidak_hadir = $cuti = $sppd = $sakit = $izin = [];
        $indexMitra = null;
        $indexRegional = null;

        $users = User::where('is_active', true)->where('role_id', 1);

        if ($request->regional_id != null) {
            $users->where('regional_id', $request->regional_id);
        }
        if ($request->mitra_id != null) {
            $users->where('mitra_id', $request->mitra_id);
        }

        $users = $users->with(['absensi' => function ($absensi) {
            $absensi->whereDate('created_at', Carbon::today())->with('detailAbsensi');
        }, 'regional', 'witel', 'mitra'])->get();

        foreach ($users as $user) {
            if ($user->mitra_id != null) {
                $indexMitra = $this->getIndex($mitra, $user->mitra_id);
                $mitra[$indexMitra]['total_karyawan'] += 1;
            }

            if ($user->regional_id != null) {
                $indexRegional = $this->getIndex($regional, $user->regional_id);
                $regional[$indexRegional]['total_karyawan'] += 1;
            }

            if (sizeOf($user->absensi) > 0) {
                $jam        = strtotime(substr($user->absensi[0]->detailAbsensi[0]['jam'], -8));
                $tipe_shift = $user->absensi[0]->is_shift;
                $kehadiran  = $user->absensi[0]->kehadiran;
                $kondisi    = $user->absensi[0]->kondisi;

                if ($this->isLate($jam, $tipe_shift)) {
                    array_push($telat, $user);
                }

                if ($kondisi == 'Sehat') {
                    if ($user->mitra_id != null)
                        $mitra[$indexMitra]['hadir'] += 1;
                    if ($user->regional_id != null)
                        $regional[$indexRegional]['hadir'] += 1;

                    if ($kehadiran == 'WFH') {
                        array_push($wfh, $user);
                    }
                    if ($kehadiran == 'WFO') {
                        array_push($wfo, $user);
                    }
                } else {
                    if ($kondisi == 'Sakit') {
                        array_push($sakit, $user);
                        if ($user->mitra_id != null)
                            $mitra[$indexMitra]['sakit'] += 1;
                        if ($user->regional_id != null)
                            $regional[$indexRegional]['sakit'] += 1;
                    }
                    if ($kondisi == 'izin') {
                        array_push($izin, $user);
                        if ($user->mitra_id != null)
                            $mitra[$indexMitra]['izin'] += 1;
                        if ($user->regional_id != null)
                            $regional[$indexRegional]['izin'] += 1;
                    }
                    if ($kondisi == 'cuti') {
                        array_push($cuti, $user);
                        if ($user->mitra_id != null)
                            $mitra[$indexMitra]['sakit'] += 1;

                        if ($user->regional_id != null)
                            $regional[$indexRegional]['sakit'] += 1;
                    }
                    if ($kondisi == 'sppd') {
                        array_push($sppd, $user);
                        if ($user->mitra_id != null) {
                            $mitra[$indexMitra]['sppd'] += 1;
                            $mitra[$indexMitra]['hadir'] += 1;
                        }

                        if ($user->regional_id != null) {
                            $regional[$indexRegional]['sppd'] += 1;
                            $regional[$indexRegional]['hadir'] += 1;
                        }
                    }
                }
            } else {
                array_push($tidak_hadir, $user);
            }
        }

        return [
            "kehadiran" => [
                "wfh"         => ["value" => count($wfh), "users" => $wfh],
                "wfo"         => ["value" => count($wfo), "users" => $wfo],
                "sppd"        => ["value" => count($sppd), "users" => $sppd],
                "izin"        => ["value" => count($izin), "users" => $izin],
                "cuti"        => ["value" => count($cuti), "users" => $cuti],
                "sakit"       => ["value" => count($sakit), "users" => $sakit],
                "telat"       => ["value" => count($telat), "users" => $telat],
                "tidak_hadir" => ["value" => count($tidak_hadir), "users" => $tidak_hadir],
            ],

            "kemarin" => [
                "tidak_checkin"  => $this->getNotPresentYesterday($request),
                "tidak_checkout" => $this->getNotCheckoutYesterday($request)
            ],

            "regional" => $regional,
            "mitra" => $mitra
        ];
    }


    /**
     * Daily all User Monthly 
     *
     */
    public function usersMonthly($request)
    {
        $telat = $wfh = $wfo = $cuti = $sppd = $sakit = $izin = 0;
        $month = $request->month == null ? Carbon::now()->month : $request->month;
        $year = $request->year == null ? Carbon::now()->year : $request->year;

        $users = User::where('is_active', true)->where('role_id', 1);

        if ($request->regional_id != null)
            $users->where('regional_id', $request->regional_id);
        if ($request->mitra_id != null)
            $users->where('mitra_id', $request->mitra_id);

        $users = $users->with(['absensi' => function ($absensi) use ($year, $month) {
            $absensi->whereYear('created_at', $year)->with('detailAbsensi');
            if ($month != "all")
                $absensi->whereMonth('created_at', $month);
        }, 'regional', 'witel', 'mitra'])->get();

        foreach ($users as $user) {
            foreach ($user->absensi as $absensi) {
                $jam        = strtotime(substr($absensi->detailAbsensi[0]['jam'], -8));
                $tipe_shift = $absensi->is_shift;
                $kehadiran  = $absensi->kehadiran;
                $kondisi    = $absensi->kondisi;
                $is_presence = true;

                if ($kehadiran == 'WFH') {
                    $wfh++;
                }
                if ($kehadiran == 'WFO') {
                    $wfo++;
                }
                if ($kondisi == 'sppd') {
                    $sppd++;
                }

                if ($kondisi == 'Sakit') {
                    $sakit++;
                    $is_presence = false;
                }
                if ($kondisi == 'izin') {
                    $izin++;
                    $is_presence = false;
                }
                if ($kondisi == 'cuti') {
                    $cuti++;
                    $is_presence = false;
                }

                if ($this->isLate($jam, $tipe_shift) && $is_presence) {
                    $telat++;
                }
            }
        }

        return [
            "wfh"         => $wfh,
            "wfo"         => $wfo,
            "sppd"        => $sppd,
            "izin"        => $izin,
            "cuti"        => $cuti,
            "sakit"       => $sakit,
            "telat"       => $telat,
            "tidak_hadir" => $this->getNotPresent($request),
        ];
    }


    /**
     * Check if already check in
     */
    public function isCheckIn($request)
    {
        $absen = Absensi::where('user_id', $request->user_id)->whereDate('created_at', Carbon::today())->with('checkIn')->get();
        if (sizeOf($absen) == 0)
            return false;

        return true;
    }


    /**
     * Check if already check out
     */
    public function isCheckOut($absensi)
    {
        if (sizeOf($absensi->checkOut) == 0)
            return false;

        return true;
    }


    /**
     * Validate input for check in
     */
    public function validateCheckIn($request, $model = null)
    {

        $validate = [
            'user_id'   => 'required',
            'kondisi'   => 'required|in:sehat,izin,sakit,cuti,sppd',
            'lokasi'    => 'required',
            'long_lat'  => 'required',
            'jam'       => 'required',
            'is_shift'  => 'required'
        ];

        $messages = [
            'user_id.required'      => 'User tidak boleh kosong',
            'kondisi.required'      => 'Kondisi tidak boleh kosong',
            'kondisi.in'            => 'Kondisi tidak sesuai dangan pilihan',
            'lokasi.required'       => 'Lokasi tidak boleh kosong',
            'long_lat.required'     => 'Koordinat tidak boleh kosong',
            'jam.required'          => 'Jam masuk tidak boleh kosong',
            'is_shift.required'     => 'Tipe shift tidak boleh kosong'
        ];

        if ($request->kondisi == 'sehat') {
            $validate['kehadiran'] = 'required|in:WFH,WFO';
            $messages['kehadiran.in'] = 'Tipe kehadiran tidak sesuai dangan pilihan';
            $messages['kehadiran.required'] = 'Keharidan harus diisi';
        }

        //if keterangan is required
        if ($this->isHaveKeterangan($request)) {
            $validate['keterangan'] = 'required';
            $messages['keterangan.required'] = 'Keterangan tidak boleh kosong';
        }

        $validator = Validator::make($request->all(), $validate, $messages);

        if ($validator->fails())
            return $validator->errors();

        return true;
    }


    /**
     * Validate input for check out
     */
    public function validateCheckOut($request)
    {
        $validate = [
            'lokasi'    => 'required',
            'long_lat'  => 'required',
            'jam'       => 'required',
        ];

        $messages = [
            'lokasi.required'       => 'Lokasi tidak boleh kosong',
            'long_lat.required'     => 'Koordinat tidak boleh kosong',
            'jam.required'          => 'Jam masuk tidak boleh kosong',
        ];

        $validator = Validator::make($request->all(), $validate, $messages);

        if ($validator->fails())
            return $validator->errors();

        return true;
    }


    /**
     * Not Present 
     *
     */
    private function getNotPresent($request, $user_id = null)
    {
        $month = $request->month == null ? Carbon::now()->month : $request->month;
        $year = $request->year == null ? Carbon::now()->year : $request->year;

        $temp = NotPresent::whereYear('created_at', $year)->orderBy('created_at');

        if ($month != "all")
            $temp->whereMonth('created_at', $month);

        if ($user_id != null)
            $temp->where('user_id', $user_id);

        $users = User::where('is_active', true)->where('role_id', 1);

        if ($request->regional_id != null)
            $users->where('regional_id', $request->regional_id);

        if ($request->mitra_id != null)
            $users->where('mitra_id', $request->mitra_id);

        $users = $users->pluck('id');
        $temp->whereIn('user_id', $users);
        return $temp->count();
    }


    /**
     * Not Present Yesterday
     *
     */
    private function getNotPresentYesterday($request)
    {
        $data =  NotPresent::whereDate('created_at', Carbon::yesterday())->pluck('user_id');

        $users = User::whereIn('id', $data)->where('role_id', 1)->where('is_active', true);

        if ($request->regional_id != null)
            $users->where('regional_id', $request->regional_id);

        if ($request->mitra_id != null)
            $users->where('mitra_id', $request->mitra_id);

        $users = $users->get();

        return $users;
    }


    /**
     * Not Checkout Yesterday
     *
     */
    private function getNotCheckoutYesterday($request)
    {
        return Absensi::whereDate('created_at', Carbon::yesterday())
            ->where('checkout_status', 'System')
            ->with(['user' => function ($user) use ($request) {
                if ($request->regional_id != null)
                    $user->where('regional_id', $request->regional_id);
                if ($request->mitra_id != null)
                    $user->where('mitra_id', $request->mitra_id);
            }])
            ->get();
    }


    /**
     * Check if ketetrangan is required
     */
    private function isHaveKeterangan($request)
    {
        $is_has = $is_late = false;

        if ($request->is_shift == 1 || $request->is_shift == 0)
            $is_late  = strtotime(substr($request->jam, -8)) > strtotime('08:15:00');
        if ($request->is_shift == 2)
            $is_late  = strtotime(substr($request->jam, -8)) > strtotime('13:15:00');
        if ($request->is_shift == 3)
            $is_late  = strtotime(substr($request->jam, -8)) > strtotime('21:15:00');

        $is_has = $request->kondisi == 'sakit' || $request->kondisi == 'izin' || $request->kondisi == 'cuti' || $request->kondisi == 'sppd';

        return $is_late || $is_has;
    }


    /**
     * Is late
     *
     */
    private function isLate($jam, $tipe_shift)
    {
        if (($tipe_shift == 0 || $tipe_shift == 1) && $jam > strtotime('08:15:00'))
            return true;

        if ($tipe_shift == 2 && $jam > strtotime('13:15:00'))
            return true;

        if ($tipe_shift == 3 && $jam > strtotime('21:15:00'))
            return true;

        return false;
    }


    /**
     * Mapping Regional
     *
     */
    private function getRegional()
    {
        return Regional::all()->map(function ($regional) {
            $regional->hadir = 0;
            $regional->sakit = 0;
            $regional->izin = 0;
            $regional->cuti = 0;
            $regional->sppd = 0;
            $regional->total_karyawan = 0;
            return $regional;
        });
    }

    /**
     * Mapping Mitra 
     *
     */
    private function getMitra()
    {
        return Mitra::all()->map(function ($mitra) {
            $mitra->hadir = 0;
            $mitra->sakit = 0;
            $mitra->izin = 0;
            $mitra->cuti = 0;
            $mitra->sppd = 0;
            $mitra->total_karyawan = 0;
            return $mitra;
        });
    }


    /**
     * Is late
     *
     */
    private function getIndex($array, $id)
    {
        foreach ($array as $key => $data) {
            if ($data['id'] == $id)
                return $key;
        }
    }
}
