<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Type
 *
 * @property int $typeID
 * @property int|null $groupID
 * @property string|null $typeName
 * @property string|null $description
 * @property float|null $mass
 * @property float|null $volume
 * @property float|null $capacity
 * @property int|null $portionSize
 * @property int|null $raceID
 * @property float|null $basePrice
 * @property int|null $published
 * @property int|null $marketGroupID
 * @property int|null $iconID
 * @property int|null $soundID
 * @property int|null $graphicID
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\TypeMaterial[] $type_materials
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Type newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Type newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Type query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Type whereBasePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Type whereCapacity($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Type whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Type whereGraphicID($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Type whereGroupID($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Type whereIconID($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Type whereMarketGroupID($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Type whereMass($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Type wherePortionSize($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Type wherePublished($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Type whereRaceID($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Type whereSoundID($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Type whereTypeID($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Type whereTypeName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Type whereVolume($value)
 * @property-read int|null $type_materials_count
 * @mixin \Eloquent
 */
class Type extends Model
{
    
    protected $table = 'invTypes';
    protected $primaryKey = 'typeID';
    public $incrementing = false;
    public $timestamps = false;

    public function type_materials()
    {
        return $this->hasMany('App\Models\TypeMaterial', 'typeID', 'typeID');
    }

}
