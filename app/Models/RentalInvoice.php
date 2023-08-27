<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\RentalInvoice
 *
 * @property int $id
 * @property int $renter_id
 * @property int $refinery_id
 * @property int $moon_id
 * @property float $amount
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Refinery $refinery
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\RentalInvoice newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\RentalInvoice newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\RentalInvoice query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\RentalInvoice whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\RentalInvoice whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\RentalInvoice whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\RentalInvoice whereRefineryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\RentalInvoice whereRenterId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\RentalInvoice whereUpdatedAt($value)
 * @property-read \App\Models\Moon|null $moon
 * @method static \Illuminate\Database\Eloquent\Builder|RentalInvoice whereMoonId($value)
 * @mixin \Eloquent
 */
class RentalInvoice extends Model
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
}
