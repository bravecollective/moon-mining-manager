<?php

namespace App\Jobs;

use App\Classes\EsiConnection;
use App\Models\ReprocessedMaterial;
use App\Models\ReprocessedMaterialsHistory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class UpdateMaterialValue implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 10;
    private $materialTypeID;

    /**
     * Create a new job instance.
     *
     * @param int $id
     * @return void
     */
    public function __construct($id)
    {
        $this->materialTypeID = $id;
    }

    /**
     * Execute the job.
     *
     * @return void
     * @throws \Exception
     */
    public function handle()
    {

        $esi = new EsiConnection;
        $the_forge = 10000002;
        $rolling_day_average = 14;

        // Find the material to update.
        $material = ReprocessedMaterial::find($this->materialTypeID);

        // Pull history for this material.
        $history = (array)$esi->getConnection()->setQueryString([
            'type_id' => $material->materialTypeID,
        ])->invoke('get', '/markets/{region_id}/history/', [
            'region_id' => $the_forge,
        ]);

        Log::info('UpdateMaterialValue: pulled market history', [
            'material_id' => $material->materialTypeID,
            'history_days' => count($history),
        ]);

        // Loop the history, starting from the end and counting backwards.
        $weight = $rolling_day_average;
        $weighted_average = 0;
        $weighted_total = 0;
        foreach (array_reverse($history) as $row) {
            if ($weight > 0) {
                $weighted_average += $row->average * $weight;
                $weighted_total += $weight;
                $weight--;
            } else {
                break;
            }
        }

        // Calculate the weighted average value of this item and save it.
        $material->average_price = $weighted_average / $weighted_total;
        $material->save();
        Log::info('UpdateMaterialValue: calculated and saved the weighted average value', [
            'material_id' => $this->materialTypeID,
            'wma_value' => number_format($material->average_price, 2) . ' ISK',
        ]);

        // Save the new average value into the history table as well.
        $history = new ReprocessedMaterialsHistory;
        $history->type_id = $this->materialTypeID;
        $history->average_price = $weighted_average / $weighted_total;
        $history->save();
        Log::info('UpdateMaterialValue: saved the historical value for material', [
            'material_id' => $this->materialTypeID,
        ]);

    }
}
