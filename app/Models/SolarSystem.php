<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\SolarSystem
 *
 * @property int|null $regionID
 * @property int|null $constellationID
 * @property int $solarSystemID
 * @property string|null $solarSystemName
 * @property float|null $x
 * @property float|null $y
 * @property float|null $z
 * @property float|null $xMin
 * @property float|null $xMax
 * @property float|null $yMin
 * @property float|null $yMax
 * @property float|null $zMin
 * @property float|null $zMax
 * @property float|null $luminosity
 * @property int|null $border
 * @property int|null $fringe
 * @property int|null $corridor
 * @property int|null $hub
 * @property int|null $international
 * @property int|null $regional
 * @property int|null $constellation
 * @property float|null $security
 * @property int|null $factionID
 * @property float|null $radius
 * @property int|null $sunTypeID
 * @property string|null $securityClass
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Moon[] $moons
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Refinery[] $refinery
 * @property-read \App\Models\Region|null $region
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SolarSystem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SolarSystem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SolarSystem query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SolarSystem whereBorder($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SolarSystem whereConstellation($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SolarSystem whereConstellationID($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SolarSystem whereCorridor($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SolarSystem whereFactionID($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SolarSystem whereFringe($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SolarSystem whereHub($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SolarSystem whereInternational($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SolarSystem whereLuminosity($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SolarSystem whereRadius($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SolarSystem whereRegionID($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SolarSystem whereRegional($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SolarSystem whereSecurity($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SolarSystem whereSecurityClass($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SolarSystem whereSolarSystemID($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SolarSystem whereSolarSystemName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SolarSystem whereSunTypeID($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SolarSystem whereX($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SolarSystem whereXMax($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SolarSystem whereXMin($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SolarSystem whereY($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SolarSystem whereYMax($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SolarSystem whereYMin($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SolarSystem whereZ($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SolarSystem whereZMax($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SolarSystem whereZMin($value)
 * @property-read int|null $moons_count
 * @property-read int|null $refinery_count
 * @mixin \Eloquent
 */
class SolarSystem extends Model
{
    
    protected $table = 'mapSolarSystems';
    protected $primaryKey = 'solarSystemID';
    public $incrementing = false;
    public $timestamps = false;

    /**
     * Get the mining activity for the refinery.
     */
    public function refinery()
    {
        return $this->hasMany('App\Models\Refinery');
    }

    /**
     * Get the region this system is part of.
     */
    public function region()
    {
        return $this->belongsTo('App\Models\Region', 'regionID');
    }

    /**
     * Get the moons that are in this solar system.
     */
    public function moons()
    {
        return $this->hasMany('App\Models\Moon');
    }

}
