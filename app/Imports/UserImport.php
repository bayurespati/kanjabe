<?php

namespace App\Imports;

use App\Models\Regional;
use App\Models\Witel;
use App\Models\User;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class UserImport implements ToModel, WithStartRow
{
    /**
     * @return int
     */
    public function startRow(): int
    {
        return 2;
    }

    /**
     * Model 
     */
    public function model(array $row)
    {
        // Add data user  
        // if ($row[1] != null) {
        //     return new User([
        //         'role_id' => 1,
        //         'nik' => $row[1],
        //         'name' => $row[2],
        //         'posisi' => $row[3],
        //         'witel_id' => $this->getWitel($row[4]),
        //         'regional_id' => $this->getRegional($row[6]),
        //         'phone' => "",
        //         'password' => bcrypt('iota2022'),
        //     ]);
        // }

        // Update data user
        if ($row[1] != null && $row[10] != null) {
            $phone = $row[10][0] == "'" ? substr($row[10], 1) : $row[10];
            $phone = substr($phone, 1);
            $user = User::where('nik', $row[1])->first();
            $user->phone = $phone;
            $user->save();
        }
    }

    private function getRegional($data)
    {
        $temp = Regional::where('name', $data)->first();
        return $temp == null ? "" : $temp->id;
    }

    private function getWitel($data)
    {
        $temp = Witel::where('name', 'like', '%' . $data . '%')->first();
        return $temp == null ? null : $temp->id;
    }
}
