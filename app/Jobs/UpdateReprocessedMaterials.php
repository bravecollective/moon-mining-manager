<?php

namespace App\Jobs;

use App\Models\ReprocessedMaterial;
use App\Models\TaxRate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class UpdateReprocessedMaterials implements ShouldQueue
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
        // Find any tax rates (i.e. ore types) that need to have their reprocessed materials checked.
        $tax_rates = TaxRate::where('check_materials', 1)->get();
        Log::info('UpdateReprocessedMaterials: found tax rates needing materials', ['count' => count($tax_rates)]);
        foreach ($tax_rates as $rate) {
            // Pull the reprocessed material components for this item and store them in the
            // table if they don't already exist.
            $materials = $rate->reprocessed_materials;
            foreach ($materials as $material) {
                $existing_reprocessed_material = ReprocessedMaterial::find($material->materialTypeID);
                if (!isset($existing_reprocessed_material)) {
                    $x = new ReprocessedMaterial;
                    $x->materialTypeID = $material->materialTypeID;
                    $x->average_price = 0;
                    $x->save();
                    Log::info('UpdateReprocessedMaterials: created new entry for material', ['material_id' => $x->materialTypeID]);
                }
            }
            // Update the flag to show the materials have been checked and save the tax rate record.
            $rate->check_materials = 0;
            $rate->save();
        }
    }
}
