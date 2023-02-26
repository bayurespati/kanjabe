<?php

namespace App\Exports;

use App\Models\Activity;
use App\Models\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;


class AcitivityExport implements FromCollection, WithHeadings, WithMapping
{
    protected $user_id = null;
    protected $name = null;
    protected $month = null;
    protected $year = null;
    protected $regional_id = null;
    protected $mitra_id = null;

    public function __construct($request, $year, $month)
    {
        $this->year = $year;
        $this->month = $month;
        $this->name = $request->name;
        $this->user_id = $request->user_id;
        $this->regional_id = $request->regional_id;
        $this->mitra_id = $request->mitra_id;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $users = User::where('is_active', true)->where('role_id', 1);

        if ($this->regional_id != null)
            $users->where('regional_id', $this->regional_id);

        if ($this->mitra_id != null)
            $users->where('mitra_id', $this->mitra_id);

        $users->with(['activity' => function ($activity) {
            $activity->whereYear('created_at', $this->year);
            if ($this->month != "all")
                $activity->whereMonth('created_at', $this->month);
        }, 'regional', 'witel', 'mitra']);

        return $users->get();
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
            'Total activity',
            'Complete',
            'To do',
        ];
    }

    public function map($row): array
    {
        $activity =  $this->getStatus($row->activity);
        $total = sizeOf($row->activity);
        $done = $activity[0];
        $progress = $activity[1];

        return [
            $row->name,
            $row->nik,
            $row->posisi,
            $row['regional']['alias'] ?? "-",
            $row['witel']['name'] ?? "-",
            $row['mitra']['name'] ?? "-",
            $total == 0 ? "0" : $total,
            $done == 0 ? "0" : $done,
            $progress == 0 ? "0" : $progress,
        ];
    }

    private function getStatus($datas)
    {
        $done = 0;
        $onProgress = 0;

        foreach ($datas as $data) {
            if ($data->progress == 100)
                $done += 1;
            else
                $onProgress += 1;
        }

        return [$done, $onProgress];
    }
}
