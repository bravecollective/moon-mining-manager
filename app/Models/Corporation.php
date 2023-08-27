<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Corporation
 *
 * @property int $id
 * @property int $corporation_id
 * @property string $name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Refinery[] $refinery
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Corporation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Corporation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Corporation query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Corporation whereCorporationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Corporation whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Corporation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Corporation whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Corporation whereUpdatedAt($value)
 * @property-read int|null $refinery_count
 * @mixin \Eloquent
 */
class Corporation extends Model
{
    public function refinery()
    {
        return $this->hasMany('App\Models\Refinery');
    }
}
