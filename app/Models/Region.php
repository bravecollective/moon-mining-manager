<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Region
 *
 * @property int $regionID
 * @property string|null $regionName
 * @property float|null $x
 * @property float|null $y
 * @property float|null $z
 * @property float|null $xMin
 * @property float|null $xMax
 * @property float|null $yMin
 * @property float|null $yMax
 * @property float|null $zMin
 * @property float|null $zMax
 * @property int|null $factionID
 * @property float|null $radius
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Moon[] $moons
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\SolarSystem[] $systems
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Region newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Region newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Region query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Region whereFactionID($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Region whereRadius($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Region whereRegionID($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Region whereRegionName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Region whereX($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Region whereXMax($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Region whereXMin($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Region whereY($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Region whereYMax($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Region whereYMin($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Region whereZ($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Region whereZMax($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Region whereZMin($value)
 * @property int|null $nebula
 * @property-read int|null $moons_count
 * @property-read int|null $systems_count
 * @method static \Illuminate\Database\Eloquent\Builder|Region whereNebula($value)
 * @mixin \Eloquent
 */
class Region extends Model
{

    protected $table = 'mapRegions';
    protected $primaryKey = 'regionID';
    public $incrementing = false;
    public $timestamps = false;

    public function systems()
    {
        return $this->hasMany('App\Models\SolarSystem', 'regionID');
    }

    public function moons()
    {
        return $this->hasMany('App\Models\Moon');
    }

}
