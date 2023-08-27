<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\RentalPayment
 *
 * @property int $id
 * @property int $renter_id
 * @property int $refinery_id
 * @property int $moon_id
 * @property int|null $ref_id
 * @property int|null $created_by
 * @property float $amount_received
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User|null $createdBy
 * @property-read \App\Models\Refinery $refinery
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\RentalPayment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\RentalPayment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\RentalPayment query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\RentalPayment whereAmountReceived($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\RentalPayment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\RentalPayment whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\RentalPayment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\RentalPayment whereRefId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\RentalPayment whereRefineryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\RentalPayment whereRenterId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\RentalPayment whereUpdatedAt($value)
 * @property-read \App\Models\Moon|null $moon
 * @property-read \App\Models\Renter|null $renter
 * @method static \Illuminate\Database\Eloquent\Builder|RentalPayment whereMoonId($value)
 * @mixin \Eloquent
 */
class RentalPayment extends Model
{
    /**
     * Get the refinery record associated with the invoice.
     */
    public function refinery()
    {
        return $this->hasOne('App\Models\Refinery', 'observer_id', 'refinery_id');
    }

    public function moon()
    {
        return $this->hasOne('App\Models\Moon', 'id', 'moon_id');
    }

    /**
     * Get the miner record associated with the payment.
     */
    public function renter()
    {
        return $this->belongsTo('App\Models\Renter', 'renter_id', 'character_id');
    }

    public function createdBy()
    {
        return $this->belongsTo('App\Models\User', 'created_by', 'eve_id');
    }
}
