<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\ReprocessedMaterialsHistory
 *
 * @property int $id
 * @property int $type_id
 * @property float $average_price
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\ReprocessedMaterial $reprocessed_material
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ReprocessedMaterialsHistory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ReprocessedMaterialsHistory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ReprocessedMaterialsHistory query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ReprocessedMaterialsHistory whereAveragePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ReprocessedMaterialsHistory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ReprocessedMaterialsHistory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ReprocessedMaterialsHistory whereTypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ReprocessedMaterialsHistory whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ReprocessedMaterialsHistory extends Model
{
    protected $table = 'reprocessed_materials_history';

    public function reprocessed_material()
    {
        return $this->belongsTo(ReprocessedMaterial::class, 'materialTypeID');
    }
}
