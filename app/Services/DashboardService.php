<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use App\Models\NotPresent;
use App\Models\Absensi;
use Carbon\Carbon;

class DashboardService
{
    /**
     * Present all User
     *
     */
    public function dashbordPresentAllUser($request)
    {
        $month = $request->month == null ? Carbon::now()->month : $request->month;
        $year = $request->year == null ? Carbon::now()->year : $request->year;

        return Absensi::whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->groupBy('kehadiran')
            ->select(DB::raw('kehadiran, COUNT(*) as value'))
            ->orderBy('kehadiran')
            ->get();
    }


    /**
     * Status all User
     *
     */
    public function dashbordStatusAllUser($request)
    {
        $month = $request->month == null ? Carbon::now()->month : $request->month;
        $year = $request->year == null ? Carbon::now()->year : $request->year;

        $temp = Absensi::whereYear('created_at', $year)
            ->with('detailAbsensi')
            ->orderBy('created_at');

        if ($month != "all")
            $temp->whereMonth('created_at', $month);

        $datas = $temp->get();

        $telat = $izin = $sakit = 0;

        foreach ($datas as $data) {
            if ($data->kondisi == 'Sakit')
                $sakit++;

            if ($data->kondisi == 'izin')
                $izin++;

            $jam = strtotime(substr($data->detailAbsensi[0]['jam'], -8));
            $tipe_shift = $data->is_shift;

            if ($this->isLate($jam, $tipe_shift))
                $telat++;
        }

        $temp_present = NotPresent::whereYear('created_at', $year);
        if ($month != "all")
            $temp_present->whereMonth('created_at', $month);

        $not_present = $temp_present->get();

        return [
            ["name" => "hadir", "value" => count($datas) - $sakit - $izin],
            ["name" => "tidak hadir", "value" => count($not_present)],
            ["name" => "telat", "value" => $telat],
        ];
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
}
