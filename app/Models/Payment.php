<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Payment
 *
 * @property int $id
 * @property int $miner_id
 * @property int|null $ref_id
 * @property int|null $created_by
 * @property float $amount_received
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User|null $createdBy
 * @property-read \App\Models\Miner $miner
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Payment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Payment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Payment query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Payment whereAmountReceived($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Payment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Payment whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Payment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Payment whereMinerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Payment whereRefId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Payment whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Payment extends Model
{

    /**
     * Get the miner record associated with the payment.
     */
    public function miner()
    {
        return $this->belongsTo(Miner::class, 'miner_id', 'eve_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by', 'eve_id');
    }
}
