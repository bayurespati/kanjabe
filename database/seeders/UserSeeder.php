<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('users')->insert([
            [
                'name'       => 'Divine',
                'nik'        => '0000',
                'posisi'     => 'ADMIN',
                'role_id'    => 3,
                'is_admin'   => 1,
                'phone'      => "085157525530",
                'password'   => bcrypt('adminadmin'),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
        ]);
    }
}
