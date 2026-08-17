<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Extraction
 *
 * @property int $id
 * @property int $moon_id
 * @property int $refinery_id
 * @property string $notification_timestamp
 * @property int|null $ore1_type_id
 * @property int|null $ore1_volume
 * @property int|null $ore2_type_id
 * @property int|null $ore2_volume
 * @property int|null $ore3_type_id
 * @property int|null $ore3_volume
 * @property int|null $ore4_type_id
 * @property int|null $ore4_volume
 * @property-read \App\Models\UniqueNames $invMoon
 * @property-read \App\Models\Type|null $ore1
 * @property-read \App\Models\Type|null $ore2
 * @property-read \App\Models\Type|null $ore3
 * @property-read \App\Models\Type|null $ore4
 * @property-read \App\Models\Refinery $refinery
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Extraction newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Extraction newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Extraction query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Extraction whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Extraction whereMoonId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Extraction whereNotificationTimestamp($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Extraction whereOre1TypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Extraction whereOre1Volume($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Extraction whereOre2TypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Extraction whereOre2Volume($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Extraction whereOre3TypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Extraction whereOre3Volume($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Extraction whereOre4TypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Extraction whereOre4Volume($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Extraction whereRefineryId($value)
 * @mixin \Eloquent
 */
class Extraction extends Model
{
    public $timestamps = false;

    public function invMoon()
    {
        return $this->belongsTo(UniqueNames::class, 'moon_id');
    }

    public function refinery()
    {
        return $this->belongsTo(Refinery::class, 'refinery_id', 'observer_id');
    }

    public function ore1()
    {
        return $this->belongsTo(Type::class, 'ore1_type_id');
    }

    public function ore2()
    {
        return $this->belongsTo(Type::class, 'ore2_type_id');
    }

    public function ore3()
    {
        return $this->belongsTo(Type::class, 'ore3_type_id');
    }

    public function ore4()
    {
        return $this->belongsTo(Type::class, 'ore4_type_id');
    }
}
