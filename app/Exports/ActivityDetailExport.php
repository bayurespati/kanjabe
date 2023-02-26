<?php

namespace App\Exports;

use App\Models\Activity;
use App\Models\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ActivityDetailExport implements FromCollection, WithHeadings, WithMapping
{
    protected $date = null;

    public function __construct($request)
    {
        $this->date = $request->date;
    }

    public function collection()
    {
        return Activity::where('date', 'LIKE', '%' . $this->date . '%')
            ->with([
                'user' => function ($user) {
                    return $user->with(['regional', 'witel', 'mitra']);
                }
            ])
            ->orderBy('user_id')
            ->orderBy('date')->get();
    }

    public function headings(): array
    {
        return [
            'Nama',
            'NIK',
            'Posisi',
            'Regional',
            'Witel',
            'Tanggal',
            'Aktifitas',
            'Deskripsi',
            'Status',
        ];
    }

    public function map($row): array
    {
        return [
            $row->user['name'],
            $row->user['nik'],
            $row->user['posisi'],
            $row->user['regional']['alias'] ?? "-",
            $row->user['witel']['name'] ?? "-",
            substr($row->date, 0, 10),
            $row->title,
            $row->description,
            $row->progress == 100 ? 'Completed' : 'To do'
        ];
    }
}
