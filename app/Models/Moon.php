<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Moon
 *
 * @property int $id
 * @property int $region_id
 * @property int $solar_system_id
 * @property int $planet
 * @property int $moon
 * @property int $mineral_1_type_id
 * @property float $mineral_1_percent
 * @property int $mineral_2_type_id
 * @property float $mineral_2_percent
 * @property int|null $mineral_3_type_id
 * @property float|null $mineral_3_percent
 * @property int|null $mineral_4_type_id
 * @property float|null $mineral_4_percent
 * @property float $monthly_rental_fee
 * @property float $monthly_corp_rental_fee
 * @property float $previous_monthly_rental_fee
 * @property float $previous_monthly_corp_rental_fee
 * @property int|null $renter_id
 * @property int $status_flag
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read mixed $active_renter
 * @property-read \App\Models\Type $mineral_1
 * @property-read \App\Models\Type $mineral_2
 * @property-read \App\Models\Type|null $mineral_3
 * @property-read \App\Models\Type|null $mineral_4
 * @property-read \App\Models\Region $region
 * @property-read \App\Models\Renter[] $renter
 * @property-read \App\Models\SolarSystem $system
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Moon newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Moon newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Moon query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Moon whereAllianceOwned($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Moon whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Moon whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Moon whereMineral1Percent($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Moon whereMineral1TypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Moon whereMineral2Percent($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Moon whereMineral2TypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Moon whereMineral3Percent($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Moon whereMineral3TypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Moon whereMineral4Percent($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Moon whereMineral4TypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Moon whereMonthlyRentalFee($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Moon whereMoon($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Moon wherePlanet($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Moon wherePreviousMonthlyRentalFee($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Moon whereRegionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Moon whereRenterId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Moon whereSolarSystemId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Moon whereUpdatedAt($value)
 * @property int $available
 * @property-read int|null $renter_count
 * @method static \Illuminate\Database\Eloquent\Builder|Moon whereAvailable($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Moon whereMonthlyCorpRentalFee($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Moon wherePreviousMonthlyCorpRentalFee($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Moon whereStatusFlag($value)
 * @mixin \Eloquent
 */
class Moon extends Model
{
    const STATUS_AVAILABLE = 0;

    const STATUS_ALLIANCE_OWNED = 1;

    const STATUS_LOTTERY_ONLY = 2;

    const STATUS_RESERVED = 3;

    /**
     * Get the solar system where this moon is located.
     */
    public function system()
    {
        return $this->belongsTo('App\Models\SolarSystem', 'solar_system_id');
    }

    /**
     * Get the region this moon is part of.
     */
    public function region()
    {
        return $this->belongsTo('App\Models\Region', 'region_id');
    }

    /**
     * Get the renters of this moon.
     */
    public function renter()
    {
        return $this->hasMany('App\Models\Renter', 'moon_id', 'id');
    }

    /**
     * Find any active renter.
     */
    public function getActiveRenterAttribute()
    {
        $today = gmdate('Y-m-d');
        foreach ($this->renter as $renter) {
            if (
                $renter->start_date <= $today &&
                (!$renter->end_date || $renter->end_date >= $today)
            ) {
                return $renter;
            }
        }

        return null;
    }

    /**
     * Get the mineral type object for each of the possible mineral types.
     */
    public function mineral_1()
    {
        return $this->belongsTo('App\Models\Type', 'mineral_1_type_id');
    }

    public function mineral_2()
    {
        return $this->belongsTo('App\Models\Type', 'mineral_2_type_id');
    }

    public function mineral_3()
    {
        return $this->belongsTo('App\Models\Type', 'mineral_3_type_id');
    }

    public function mineral_4()
    {
        return $this->belongsTo('App\Models\Type', 'mineral_4_type_id');
    }

    public function getName(bool $withRegionName = true): string
    {
        $name = "{$this->system->solarSystemName} - P$this->planet M$this->moon";

        if ($withRegionName) {
            $name .= " ({$this->region->regionName})";
        }

        return $name;
    }
}
