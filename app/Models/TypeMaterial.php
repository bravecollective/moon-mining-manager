<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\TypeMaterial
 *
 * @property int $typeID
 * @property int $materialTypeID
 * @property int $quantity
 * @property-read \App\Models\Type $type
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\TypeMaterial newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\TypeMaterial newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\TypeMaterial query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\TypeMaterial whereMaterialTypeID($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\TypeMaterial whereQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\TypeMaterial whereTypeID($value)
 * @mixin \Eloquent
 */
class TypeMaterial extends Model
{
    protected $table = 'invTypeMaterials';
    public $incrementing = false;
    public $timestamps = false;

    public function type()
    {
        return $this->belongsTo(Type::class, 'typeID', 'typeID');
    }

}
