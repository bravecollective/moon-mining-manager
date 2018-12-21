<?php

namespace App\Console;

use App\Classes\EsiConnection;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Jobs\PollWallet;
use App\Jobs\UpdateReprocessedMaterials;
use App\Jobs\PollRefineries;
use App\Jobs\UpdateMaterialValues;
use App\Jobs\PollMiningObservers;
use App\Jobs\GenerateInvoices;
use App\Jobs\PollStructures;
use App\Jobs\ArchiveReprocessedMaterialsHistory;
use App\Jobs\PollExtractions;
use App\Jobs\ProcessMiningActivity;
use App\Jobs\GenerateRentalInvoices;
use App\Jobs\CorporationChecks;
use App\Jobs\CalculateRent;
use App\Jobs\GenerateRentNotifications;
use App\Jobs\GenerateRentReminders;
use App\Jobs\SendRenterDelinquencyList;
use Illuminate\Support\Facades\Log;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        'App\Console\Commands\RunJob'
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $esi = new EsiConnection();

        $minutes1 = 0;
        $minutes2 = 10;
        $minutes3 = 20;
        $minutes4 = 30;
        foreach ($esi->getPrimeUserIds() as $userId) {
            if ($minutes1 > 9) {
                Log::error('Too many prime users.');
                break;
            }

            // Poll all corporation structures to look for refineries.
            $schedule->job(new PollStructures($userId))->dailyAt('00:0'.$minutes1);
            $minutes1 ++;

            // Poll all refineries for information about upcoming extraction cycles.
            $schedule->job(new PollExtractions($userId))->dailyAt('00:'.$minutes2);
            $minutes2 ++;

            // Check for any newly active refineries.
            $schedule->job(new PollRefineries($userId))->dailyAt('00:'.$minutes3);
            $minutes3 ++;

            // Check for miners making payments to the corporation wallet.
            $schedule->job(new PollWallet($userId))->hourlyAt($minutes4);
            $minutes4 ++;
        }

        // Pull the mining activity for the day and store it.
        $schedule->job(new PollMiningObservers)->dailyAt('12:00');

        // Check for any new ores that have been mined where we don't have details of their component materials.
        $schedule->job(new UpdateReprocessedMaterials)->twiceDaily(4, 16);

        // Update the stored prices for materials and ores.
        $schedule->job(new UpdateMaterialValues)->dailyAt('05:00');

        // Process any new mining activity.
        $schedule->job(new ProcessMiningActivity)->dailyAt('15:00');

        // Archive old price history records.
        $schedule->job(new ArchiveReprocessedMaterialsHistory)->dailyAt('06:55');

        // Send weekly invoices.
        $schedule->job(new GenerateInvoices)->weekly()->mondays()->at('07:00');

        // Send monthly rental invoices.
        $schedule->job(new GenerateRentalInvoices)->monthlyOn(1, '09:00');

        // Monthly check of miner corporation membership.
        $schedule->job(new CorporationChecks)->monthlyOn(15, '23:00');

        // Monthly recalculation of moon rental fees.
        $schedule->job(new CalculateRent)->monthlyOn(25, '16:00');

        // Monthly notification of updated moon rental fees.
        $schedule->job(new GenerateRentNotifications)->monthlyOn(25, '22:00');

        // Twice monthly reminder emails about unpaid moon rental fees.
        $schedule->job(new GenerateRentReminders)->monthlyOn(14, '09:00');
        $schedule->job(new GenerateRentReminders)->monthlyOn(26, '09:00');

        // Send monthly summary of delinquent renters to the site admin.
        $schedule->job(new SendRenterDelinquencyList)->monthlyOn(29, '09:00');

    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
