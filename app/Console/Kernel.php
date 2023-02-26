<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        commands\Absen::class,
        commands\CheckOut::class,
        commands\CheckOutShift::class,
        commands\Test::class
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('absensi:refresh')->dailyAt('23:00');
        // $schedule->command('checkout:refresh')->dailyAt('23:00');
        // $schedule->command('checkoutshift:refresh')->dailyAt('11:00');
        // $schedule->command('tester:refresh')->dailyAt('15:00');
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
