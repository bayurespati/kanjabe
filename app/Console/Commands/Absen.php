<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\CronjobController;

class Absen extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'absensi:refresh';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Record user not checkin';
    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $absensiControlle = (new CronjobController());
        $absensiControlle->inputNotPresent();
    }
}
