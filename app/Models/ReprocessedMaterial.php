<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\ReprocessedMaterial
 *
 * @property int $id
 * @property int $materialTypeID
 * @property float $average_price
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ReprocessedMaterialsHistory[] $history
 * @property-read \App\Models\Type $type
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ReprocessedMaterial newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ReprocessedMaterial newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ReprocessedMaterial query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ReprocessedMaterial whereAveragePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ReprocessedMaterial whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ReprocessedMaterial whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ReprocessedMaterial whereMaterialTypeID($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ReprocessedMaterial whereUpdatedAt($value)
 * @property-read int|null $history_count
 * @mixin \Eloquent
 */
class ReprocessedMaterial extends Model
{
    protected $table = 'reprocessed_materials';
    protected $primaryKey = 'materialTypeID';

    public function type()
    {
        return $this->hasOne('App\Models\Type', 'typeID', 'materialTypeID');
    }

    public function history()
    {
        return $this->hasMany('App\Models\ReprocessedMaterialsHistory', 'type_id', 'materialTypeID');
    }
}
