<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Laravel\Lumen\Console\Kernel as ConsoleKernel;
use App\User;
use App\UserAchievements;

class Kernel extends ConsoleKernel
{

    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
            //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->call(function () {
            UserAchievements::achievementsSchedulerRun();
        });

        // Clear game-leaderboard every monday at 00:01 AM
        $schedule->call(function () {
            \App\GameLeaderboard::query()->truncate();
        })->weekly()->mondays()->at('00:01');
    }

}
