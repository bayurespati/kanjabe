<?php

namespace App\Exports;

use App\Models\Holiday;
use App\Models\NotPresent;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use App\Models\User;
use Carbon\Carbon;

class PersonalByRegionalExport implements FromCollection, WithHeadings, WithMapping
{
    protected $date = null;
    protected $regional_id = null;
    protected $mitra_id = null;
    protected $year = null;
    protected $month = null;

    public function __construct($request, $year, $month)
    {
        $this->year = $year;
        $this->month = $month;
        $this->regional_id = $request->regional_id;
        $this->mitra_id = $request->mitra_id;
    }

    /** 
     * @return \Illuminate\Support\Collection 
     */
    public function collection()
    {
        $users =  User::where('is_active', true)->where('role_id', 1);

        if ($this->regional_id != null)
            $users->where('regional_id', $this->regional_id);

        if ($this->mitra_id != null)
            $users->where('mitra_id', $this->mitra_id);

        return $users->with(['absensi' => function ($absensi) {
            $absensi->whereYear('created_at', $this->year)
                ->with('detailAbsensi');
            if ($this->month != "all")
                $absensi->whereMonth('created_at', $this->month);
        }, 'notPresent' => function ($not_present) {
            $not_present->whereYear('created_at', $this->year);
            if ($this->month != "all")
                $not_present->whereMonth('created_at', $this->month);
        }, 'regional', 'witel', 'mitra'])->get();
    }

    public function headings(): array
    {
        return [
            'Nama',
            'NIK',
            'Posisi',
            'Regional',
            'Witel',
            'Mitra',
            'Tahun',
            'Bulan',
            'Total hari kerja',
            'Hadir',
            'Keterangan',
            'Tidak hadir',
            'WFO',
            'WFH',
            'SPPD',
            'Izin',
            'Sakit',
            'Cuti',
            'Telat',
            'Jumlah jam'
        ];
    }

    public function map($user): array
    {
        $izin = $sakit = $sppd = $cuti = $telat = $jumlahjam = $wfh = $wfo = "0";
        $totlaDay = $this->getDayWorkInOneMonth();

        foreach ($user->absensi as $absensi) {
            $jam = 0;
            $is_presence = true;

            if ($absensi->kondisi == 'Sakit') {
                $sakit = $sakit + 1;
                $is_presence = false;
            }

            if ($absensi->kondisi == 'izin') {
                $izin = $izin + 1;
                $is_presence = false;
            }

            if ($absensi->kondisi == 'cuti') {
                $cuti = $cuti + 1;
                $is_presence = false;
            }

            if ($absensi->kondisi == 'sppd') {
                $sppd = $sppd + 1;
            }

            if ($absensi->kehadiran == 'WFO') {
                $wfo = $wfo + 1;
            }

            if ($absensi->kehadiran == 'WFH') {
                $wfh = $wfh + 1;
            }

            $time = strtotime(substr($absensi->detailAbsensi[0]['jam'], -8));
            $tipe_shift = $absensi->is_shift;

            if ($this->isLate($time, $tipe_shift) && $is_presence) {
                $telat = $telat + 1;
            }

            if (count($absensi->detailAbsensi) > 1) {
                $checkIn = Carbon::parse($absensi->detailAbsensi[0]['jam']);
                $checkOut = Carbon::parse($absensi->detailAbsensi[1]['jam']);
                $jam = (int) $checkIn->diff($checkOut)->format('%H');
            }

            $jumlahjam += $jam;
        }

        $fields = [
            $user['name'],
            $user['nik'],
            $user['posisi'] ?? "",
            $user['regional']['alias'] ?? "",
            $user['witel']['name'] ?? "",
            $user['mitra']['name'] ?? "",
            $this->year,
            $this->month,
            (int)$totlaDay,
            (int)$wfh + $wfo + $sppd,
            $izin + $cuti + $sakit,
            sizeOf($user->notPresent),
            $wfo,
            $wfh,
            $sppd,
            $izin,
            $sakit,
            $cuti,
            $telat,
            $jumlahjam == 0 ? "0" :  $jumlahjam
        ];

        return $fields;
    }


    /** 
     * Is late 
     * 
     */
    private function isLate($time, $tipe_shift)
    {
        if (($tipe_shift == 0 || $tipe_shift == 1) && $time > strtotime('08:15:00')) return true;

        if ($tipe_shift == 2 && $time > strtotime('13:15:00')) return true;

        if ($tipe_shift == 3 && $time > strtotime('21:15:00')) return true;

        return false;
    }

    private function getDayWorkInOneMonth()
    {
        $workdays = array();
        $month = $this->month;
        $year = $this->year;
        $day_count = cal_days_in_month(0, $month, $year); // Get the amount of days

        //loop through all days
        for ($i = 1; $i <= $day_count; $i++) {

            $date = $year . '/' . $month . '/' . $i; //format date
            $get_name = date('l', strtotime($date)); //get week day
            $day_name = substr($get_name, 0, 3); // Trim day name to 3 chars

            //if not a weekend add day to array
            if ($day_name != 'Sun' && $day_name != 'Sat') {
                $workdays[] = $i;
            }
        }

        $holidays = Holiday::where('tanggal', 'like', '%' . $this->year . '-' . $this->month . '%')->get();
        $count_day = 0;
        foreach ($holidays as $holiday) {
            $day = date("D", strtotime($holiday->tanggal));
            //Check to see if it is equal to Sat or Sun.
            if ($day == 'Sat' || $day == 'Sun') {
                $weekendDay = true;
            } else {
                $count_day++;
            }
        }

        return count($workdays) - $count_day;
    }
}
