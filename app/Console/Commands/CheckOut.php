<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\CronjobController;

class CheckOut extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'checkout:refresh';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Checkout user';

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
        $absensiControlle->checkOutUser();
    }
}
