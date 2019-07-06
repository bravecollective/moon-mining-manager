<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Renter
 *
 * @property int $id
 * @property string $type
 * @property int $character_id
 * @property int|null $refinery_id
 * @property int|null $moon_id
 * @property string|null $notes
 * @property float $monthly_rental_fee
 * @property float $amount_owed
 * @property string|null $generate_invoices_job_run
 * @property string $start_date
 * @property string|null $end_date
 * @property int|null $updated_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Moon $moon
 * @property-read \App\Models\Refinery $refinery
 * @property-read \App\Models\User|null $updatedBy
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Renter newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Renter newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Renter query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Renter whereAmountOwed($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Renter whereCharacterId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Renter whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Renter whereEndDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Renter whereGenerateInvoicesJobRun($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Renter whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Renter whereMonthlyRentalFee($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Renter whereMoonId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Renter whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Renter whereRefineryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Renter whereStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Renter whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Renter whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Renter whereUpdatedBy($value)
 * @mixin \Eloquent
 */
class Renter extends Model
{
    protected $table = 'renters';

    /**
     * Get the refinery being rented.
     */
    public function refinery()
    {
        return $this->hasOne('App\Models\Refinery', 'observer_id', 'refinery_id');
    }

    /**
     * Get the moon where this refinery is located.
     */
    public function moon()
    {
        return $this->hasOne('App\Models\Moon', 'id', 'moon_id');
    }

    public function updatedBy()
    {
        return $this->belongsTo('App\Models\User', 'updated_by', 'eve_id');
    }
}