<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * App\Models\Moon
 *
 * @property int $id
 * @property int $region_id
 * @property int $solar_system_id
 * @property int $planet
 * @property int $moon
 * @property int $mineral_1_type_id
 * @property float $mineral_1_percent
 * @property int $mineral_2_type_id
 * @property float $mineral_2_percent
 * @property int|null $mineral_3_type_id
 * @property float|null $mineral_3_percent
 * @property int|null $mineral_4_type_id
 * @property float|null $mineral_4_percent
 * @property float $monthly_rental_fee
 * @property float $monthly_corp_rental_fee
 * @property float $previous_monthly_rental_fee
 * @property float $previous_monthly_corp_rental_fee
 * @property int|null $renter_id
 * @property int $status_flag
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read mixed $active_renter
 * @property-read \App\Models\Type $mineral_1
 * @property-read \App\Models\Type $mineral_2
 * @property-read \App\Models\Type|null $mineral_3
 * @property-read \App\Models\Type|null $mineral_4
 * @property-read \App\Models\Region $region
 * @property-read \App\Models\Renter[] $renter
 * @property-read \App\Models\SolarSystem $system
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Moon newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Moon newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Moon query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Moon whereAllianceOwned($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Moon whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Moon whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Moon whereMineral1Percent($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Moon whereMineral1TypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Moon whereMineral2Percent($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Moon whereMineral2TypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Moon whereMineral3Percent($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Moon whereMineral3TypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Moon whereMineral4Percent($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Moon whereMineral4TypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Moon whereMonthlyRentalFee($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Moon whereMoon($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Moon wherePlanet($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Moon wherePreviousMonthlyRentalFee($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Moon whereRegionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Moon whereRenterId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Moon whereSolarSystemId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Moon whereUpdatedAt($value)
 * @property int $available
 * @property-read int|null $renter_count
 * @method static \Illuminate\Database\Eloquent\Builder|Moon whereAvailable($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Moon whereMonthlyCorpRentalFee($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Moon wherePreviousMonthlyCorpRentalFee($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Moon whereStatusFlag($value)
 * @mixin \Eloquent
 */
class Moon extends Model
{
    const STATUS_AVAILABLE = 0;

    const STATUS_ALLIANCE_OWNED = 1;

    const STATUS_LOTTERY_ONLY = 2;

    const STATUS_RESERVED = 3;

    /**
     * The four columns holding a moon's composition.
     */
    public const MINERAL_COLUMNS = [
        'mineral_1_type_id',
        'mineral_2_type_id',
        'mineral_3_type_id',
        'mineral_4_type_id',
    ];

    /**
     * Moon materials, the reason a moon is worth renting. Names as they appear in `invTypes`.
     *
     * Order is significant: R4, R8, R16, R32, R64, four to a line, and the mineral filter renders
     * them in it. Keep new entries in their rarity group rather than sorting the list.
     */
    public const MOON_MATERIALS = [
        'Bitumens', 'Coesite', 'Sylvite', 'Zeolites',
        'Cobaltite', 'Euxenite', 'Scheelite', 'Titanite',
        'Chromite', 'Otavite', 'Sperrylite', 'Vanadinite',
        'Carnotite', 'Cinnabar', 'Pollucite', 'Zircon',
        'Loparite', 'Monazite', 'Xenotime', 'Ytterbite',
    ];

    /**
     * Moon IDs with a rental running today, as a subquery.
     *
     * `renters` carries no index on `moon_id` and this does not add one, so the goal is to read
     * that table exactly once per request. DISTINCT stops MariaDB merging the subquery into the
     * outer statement: it is materialised once, and derived_with_keys then indexes the
     * materialised result for the join back to `moons`.
     */
    protected static function activeRentals(): Builder
    {
        return Renter::query()
            ->select('moon_id')
            ->distinct()
            ->whereNotNull('moon_id')
            ->active();
    }

    /**
     * Restrict to moons nobody is renting today.
     */
    public function scopeWithoutActiveRenter(Builder $query): Builder
    {
        return $query
            ->select('moons.*')
            ->leftJoinSub(static::activeRentals(), 'active_rentals', 'active_rentals.moon_id', '=', 'moons.id')
            ->whereNull('active_rentals.moon_id');
    }

    /**
     * Restrict to moons somebody is renting today. DISTINCT in the subquery keeps this from
     * duplicating a moon that somehow has two overlapping rentals.
     */
    public function scopeWithActiveRenter(Builder $query): Builder
    {
        return $query
            ->select('moons.*')
            ->joinSub(static::activeRentals(), 'active_rentals', 'active_rentals.moon_id', '=', 'moons.id');
    }

    /**
     * Restrict to moons whose composition includes any of the given mineral types.
     */
    public function scopeContainsType(Builder $query, array $typeIds): Builder
    {
        if ($typeIds === []) {
            return $query;
        }

        // The closure is load bearing. Without it the ORs bind looser than the surrounding ANDs
        // and `available = 1` stops applying, leaking unavailable moons into the results.
        return $query->where(function (Builder $group) use ($typeIds) {
            foreach (self::MINERAL_COLUMNS as $column) {
                $group->orWhereIn($column, $typeIds);
            }
        });
    }

    /**
     * Get the solar system where this moon is located.
     */
    public function system() : BelongsTo
    {
        return $this->belongsTo(SolarSystem::class, 'solar_system_id');
    }

    /**
     * Get the region this moon is part of.
     */
    public function region() : BelongsTo
    {
        return $this->belongsTo(Region::class, 'region_id');
    }

    /**
     * Get the renters of this moon.
     */
    public function renter() : HasMany
    {
        return $this->hasMany(Renter::class, 'moon_id', 'id');
    }

    /**
     * Find any active renter.
     *
     * Same test as Renter::scopeActive(), applied in PHP over an already loaded relation.
     * Change both together.
     */
    public function getActiveRenterAttribute()
    {
        $today = gmdate('Y-m-d');
        foreach ($this->renter as $renter) {
            if (
                $renter->start_date <= $today &&
                (!$renter->end_date || $renter->end_date >= $today)
            ) {
                return $renter;
            }
        }

        return null;
    }

    /**
     * Get the mineral type object for each of the possible mineral types.
     */
    public function mineral_1() : BelongsTo
    {
        return $this->belongsTo(Type::class, 'mineral_1_type_id');
    }

    public function mineral_2() : BelongsTo
    {
        return $this->belongsTo(Type::class, 'mineral_2_type_id');
    }

    public function mineral_3() : BelongsTo
    {
        return $this->belongsTo(Type::class, 'mineral_3_type_id');
    }

    public function mineral_4() : BelongsTo
    {
        return $this->belongsTo(Type::class, 'mineral_4_type_id');
    }

    public function getName(bool $withRegionName = true): string
    {
        $name = "{$this->system->solarSystemName} - P$this->planet M$this->moon";

        if ($withRegionName) {
            $name .= " ({$this->region->regionName})";
        }

        return $name;
    }
}
