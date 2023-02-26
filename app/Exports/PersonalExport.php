<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use App\Models\Absensi;
use Carbon\Carbon;

class PersonalExport implements FromCollection, WithHeadings, WithMapping
{
    protected $user_id = null;
    protected $name = null;

    public function __construct($request, $year, $month)
    {
        $this->year = $year;
        $this->month = $month;
        $this->name = $request->name;
        $this->user_id = $request->user_id;
    }

    /** 
     * @return \Illuminate\Support\Collection 
     */
    public function collection()
    {
        $data = Absensi::where('user_id', $this->user_id)
            ->whereYear('created_at', $this->year)
            ->with(['checkIn', 'checkOut', 'user.regional', 'user.witel', 'user.mitra']);

        if ($this->month != "all")
            $data->whereMonth('created_at', $this->month);

        return $data->get();
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
            'WFH/WFO',
            'Kondisi',
            'Check in',
            'Check out',
            'Total Jam',
            'Lokasi Check In',
            'Lokasi Check Out',
            'Keterangan',
        ];
    }

    public function map($row): array
    {
        $checkOut = '0000-00-00 00:00:00';
        $lokasiCheckOut = '';
        $jam = 0;

        $checkIn = Carbon::parse($row->checkIn[0]['jam']);
        $lokasiCheckIn = $row->checkIn[0]['lokasi'];

        if (sizeOf($row->checkOut) > 0) {
            $checkOut = Carbon::parse($row->checkOut[0]['jam']);
            $lokasiCheckOut = $row->checkOut[0]['lokasi'];
            $jam = $checkIn->diff($checkOut)->format('%H:%I:%S');
        }

        $fields = [
            $row['user']['name'],
            $row['user']['nik'],
            $row['user']['posisi'],
            $row['user']['regional']['alias'],
            $row['user']['witel']['name'],
            $row['user']['mitra']['name'],
            $row['kehadiran'],
            $row['kondisi'],
            $checkIn,
            $checkOut,
            $jam,
            $lokasiCheckIn,
            $lokasiCheckOut,
            $row['keterangan']
        ];
        return $fields;
    }
}
