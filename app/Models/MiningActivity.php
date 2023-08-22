<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
//use jdavidbakr\ReplaceableModel\ReplaceableModel;

/**
 * App\Models\MiningActivity
 *
 * @property int $id
 * @property string $hash
 * @property int $miner_id
 * @property int $refinery_id
 * @property int $type_id
 * @property int $quantity
 * @property float|null $tax_amount
 * @property int $processed
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Miner $miner
 * @property-read \App\Models\Refinery $refinery
 * @property-read \App\Models\Type $type
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MiningActivity newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MiningActivity newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MiningActivity query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MiningActivity whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MiningActivity whereHash($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MiningActivity whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MiningActivity whereMinerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MiningActivity whereProcessed($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MiningActivity whereQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MiningActivity whereRefineryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MiningActivity whereTaxAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MiningActivity whereTypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MiningActivity whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class MiningActivity extends Model
{

    // Allow for using insertIgnore calls on this model.
    //use ReplaceableModel;
    
    protected $table = 'mining_activities';

    /**
     * Get the miner record associated with the activity.
     */
    public function miner()
    {
        return $this->belongsTo('App\Models\Miner');
    }

    /**
     * Get the refinery record associated with the activity.
     */
    public function refinery()
    {
        return $this->belongsTo('App\Models\Refinery', 'refinery_id', 'observer_id');
    }

    /**
     * Get the type record associated with the activity.
     */
    public function type()
    {
        return $this->belongsTo('App\Models\Type', 'type_id', 'typeID');
    }

}
