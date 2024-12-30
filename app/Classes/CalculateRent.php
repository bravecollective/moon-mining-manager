<?php

namespace App\Classes;

use App\Jobs\UpdateMaterialValues;
use App\Jobs\UpdateReprocessedMaterials;
use App\Models\Moon;
use App\Models\Renter;
use App\Models\TaxRate;
use App\Models\Type;
use Illuminate\Support\Facades\Log;

class CalculateRent
{
    protected $total_ore_volume = 21500000; // 21.5m m3 represents a 30-day mining cycle, approximately.
    protected $r4_discount_value = 50000000; // 50m flat rate discount for moons that are only R4
    protected $min_moon_rental_price = 50000000; // 50m minimum rental price

    public function updateMoon(Moon $moon, $contractType): int
    {
        // Set the monthly rental value to zero.
        $fee = 0;

        $type = Type::find($moon->mineral_1_type_id);
        $ore_groups[] = $type->groupID;
        $fee += $this->calculateOreTaxValue($type, $moon->mineral_1_percent, $contractType);
        $type = Type::find($moon->mineral_2_type_id);
        $ore_groups[] = $type->groupID;
        $fee += $this->calculateOreTaxValue($type, $moon->mineral_2_percent, $contractType);
        if ($moon->mineral_3_type_id) {
            $type = Type::find($moon->mineral_3_type_id);
            $ore_groups[] = $type->groupID;
            $fee += $this->calculateOreTaxValue($type, $moon->mineral_3_percent, $contractType);
        }
        if ($moon->mineral_4_type_id) {
            $type = Type::find($moon->mineral_4_type_id);
            $ore_groups[] = $type->groupID;
            $fee += $this->calculateOreTaxValue($type, $moon->mineral_4_percent, $contractType);
        }

        // Apply a flat 50m discount to rentals if there is only r4 ores in the moon.
        if (count(array_diff($ore_groups, [1884])) === 0) {
          $fee -= $this->r4_discount_value;
          $fee = max($fee, $this->min_moon_rental_price);
        }

        // Save the updated rental fee.
        if ($contractType === Renter::TYPE_PASSIVE) {
            $moon->monthly_corp_rental_fee = $fee;
        } else { // Renter::TYPE_ACTIVE
            $moon->monthly_rental_fee = $fee;
        }
        $moon->save();

        return $fee;
    }

    private function calculateOreTaxValue($type, $percent, $contractType): int
    {
        // Retrieve the value of the mineral from the taxes table.
        $tax_rate = TaxRate::where('type_id', $type->typeID)->first();

        // If we don't have a stored tax rate for this ore type, queue a job to calculate it.
        if (isset($tax_rate)) {
            // Grab the stored value of this ore.
            $oreValue = $tax_rate->value;

            // Calculate what volume of the total ore will be this type.
            $oreVolume = $this->total_ore_volume * $percent / 100;

            // Based on the volume of the ore type, how many units does that volume represent.
            $units = $oreVolume / $type->volume;

            // Base Tax Rate of 0%
            $taxRate = 0;

            // Addition of previously-taxable value for each ore.
            switch ($type->groupID) {
                case 1884: // Ubiquitous R4
                    $taxRate = 0;
                    break;
                case 1920: // Common R8
                    $taxRate = 0;
                    break;
                case 1921: // Uncommon R16
                    $taxRate = 10;
                    break;
                case 1922: // Rare R32
                    $taxRate = 20;
                    break;
                case 1923: // Exceptional R64
                    $taxRate = 35;
                    break;
            }

            // Increase rent for passive renters
            $taxRate *= ($contractType === Renter::TYPE_ACTIVE)? 1 : 1.62;

            // Reduce moon value to 70% for rent calculation
            $moonValue = $oreValue * $units * 0.7;

            // For non-moon ores, apply a 50% discount.
            $discount = (in_array($type->groupID, [1884, 1920, 1921, 1922, 1923])) ? 1 : 0.5;

            // Calculate the tax value to be charged for the volume of this ore that can be mined.
            return (int) round($moonValue * $taxRate / 100 * $discount);
        } else {
            // Add a new record for this unknown ore type.
            $tax_rate = new TaxRate;
            $tax_rate->type_id = $type->typeID;
            $tax_rate->check_materials = 1;
            $tax_rate->value = 0;
            $tax_rate->tax_rate = 7;
            $tax_rate->updated_by = 0;
            $tax_rate->save();

            Log::info('CalculateRent: unknown ore ' . $type->typeID . ' found, new tax rate record created');

            // Queue the jobs to update the ore values rather than waiting for the next scheduled job.
            UpdateReprocessedMaterials::dispatch();
            UpdateMaterialValues::dispatch();

            return 0;
        }
    }
}
