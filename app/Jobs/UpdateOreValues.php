<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Models\TaxRate;
use App\Models\ReprocessedMaterial;
use Illuminate\Support\Facades\Log;

class UpdateOreValues implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // Grab all tax rate records, and all stored current values for materials (keyed by id).
        $tax_rates = TaxRate::all();
        $material_values = ReprocessedMaterial::select(['materialTypeID', 'average_price'])
            ->get()->keyBy('materialTypeID');

        // Loop through the rates, calculating the total value per unit of ore.
        foreach ($tax_rates as $rate) {
            $total_unit_cost = 0;
            $materials = $rate->reprocessed_materials;

            foreach ($materials as $material) {
                $total_unit_cost += $material_values[$material->materialTypeID]->average_price *
                    ($material->quantity * 0.86);
            }
            $rate->value = $total_unit_cost / 100;
            $rate->save();
            Log::info('UpdateOreValues: calculated and saved unit cost for ore', ['type_id' => $rate->type_id]);
        }
    }
}
