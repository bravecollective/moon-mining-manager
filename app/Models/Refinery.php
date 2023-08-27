<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Refinery
 *
 * @property int $id
 * @property int $observer_id
 * @property int|null $corporation_id
 * @property string|null $extraction_start_time
 * @property string|null $chunk_arrival_time
 * @property string|null $natural_decay_time
 * @property int|null $claimed_by_primary
 * @property int|null $claimed_by_secondary
 * @property string|null $custom_detonation_time
 * @property string $observer_type
 * @property string|null $name
 * @property int|null $solar_system_id
 * @property float $income
 * @property int|null $available
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Corporation|null $corporation
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\MiningActivity[] $mining_activity
 * @property-read \App\Models\User|null $primary
 * @property-read \App\Models\User|null $secondary
 * @property-read \App\Models\SolarSystem|null $system
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Refinery newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Refinery newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Refinery query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Refinery whereChunkArrivalTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Refinery whereClaimedByPrimary($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Refinery whereClaimedBySecondary($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Refinery whereCorporationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Refinery whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Refinery whereCustomDetonationTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Refinery whereExtractionStartTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Refinery whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Refinery whereIncome($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Refinery whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Refinery whereNaturalDecayTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Refinery whereObserverId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Refinery whereObserverType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Refinery whereSolarSystemId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Refinery whereUpdatedAt($value)
 * @property-read int|null $mining_activity_count
 * @method static \Illuminate\Database\Eloquent\Builder|Refinery whereAvailable($value)
 * @mixin \Eloquent
 */
class Refinery extends Model
{

    protected $table = 'refineries';

    /**
     * Get the mining activity for the refinery.
     */
    public function mining_activity()
    {
        return $this->hasMany('App\Models\MiningActivity', 'refinery_id', 'observer_id');
    }

    /**
     * Get the solar system record associated with the activity.
     */
    public function system()
    {
        return $this->belongsTo('App\Models\SolarSystem', 'solar_system_id');
    }

    /**
     * Get the user record for the primary detonation character.
     */
    public function primary()
    {
        return $this->belongsTo('App\Models\User', 'claimed_by_primary', 'eve_id');
    }

    /**
     * Get the user record for the secondary detonation character.
     */
    public function secondary()
    {
        return $this->belongsTo('App\Models\User', 'claimed_by_secondary', 'eve_id');
    }

    public function corporation()
    {
        return $this->belongsTo('App\Models\Corporation', 'corporation_id', 'corporation_id');
    }
}
