<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\TaxRate
 *
 * @property int $id
 * @property int $type_id
 * @property int $check_materials
 * @property float $value
 * @property float $tax_rate
 * @property int $updated_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\TypeMaterial[] $reprocessed_materials
 * @property-read \App\Models\Type $type
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\TaxRate newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\TaxRate newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\TaxRate query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\TaxRate whereCheckMaterials($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\TaxRate whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\TaxRate whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\TaxRate whereTaxRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\TaxRate whereTypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\TaxRate whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\TaxRate whereUpdatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\TaxRate whereValue($value)
 * @property-read int|null $reprocessed_materials_count
 * @mixin \Eloquent
 */
class TaxRate extends Model
{
    protected $table = 'taxes';

    /**
     * Get the type record associated with the tax rate.
     */
    public function type()
    {
        return $this->belongsTo('App\Models\Type', 'type_id');
    }

    /**
     * Get the reprocessed material records associated with the tax rate.
     */
    public function reprocessed_materials()
    {
        return $this->hasMany('App\Models\TypeMaterial', 'typeID', 'type_id');
    }

}
