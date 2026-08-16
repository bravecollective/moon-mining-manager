<?php

namespace App\Http\Controllers;

use App\Models\Moon;
use App\Models\Type;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MoonController extends Controller
{
    /**
     * Rows per page. Matches MinerController, and AppServiceProvider already registers the
     * paginator view this renders through.
     */
    private const PER_PAGE = 250;

    /**
     * Public sort keys mapped to the column they order by. Request input is matched against
     * these keys and never reaches the query as a column name.
     */
    private const SORTS = [
        'id'      => 'moons.id',
        'region'  => 'region_id',
        'system'  => 'solar_system_id',
        'planet'  => 'planet',
        'active'  => 'monthly_rental_fee',
        'passive' => 'monthly_corp_rental_fee',
    ];

    /**
     * Total composition is derived rather than stored, so the `total` sort key orders by this
     * expression instead of a column.
     */
    private const TOTAL_PERCENT = 'COALESCE(mineral_1_percent, 0) + COALESCE(mineral_2_percent, 0)'
        .' + COALESCE(mineral_3_percent, 0) + COALESCE(mineral_4_percent, 0)';

    /**
     * Status filter values and their labels. The same six the client side filter offered, except
     * the default is now Available rather than All.
     */
    private const STATUS_LABELS = [
        'available'      => 'Available',
        'rented'         => 'Rented',
        'alliance_owned' => 'Alliance owned',
        'lottery_only'   => 'Lottery only',
        'reserved'       => 'Reserved',
        'all'            => 'All',
    ];

    /**
     * Status values that are a plain `status_flag` match. Available and Rented also depend on
     * whether a rental is running today, so they are handled separately.
     */
    private const STATUS_FLAGS = [
        'alliance_owned' => Moon::STATUS_ALLIANCE_OWNED,
        'lottery_only'   => Moon::STATUS_LOTTERY_ONLY,
        'reserved'       => Moon::STATUS_RESERVED,
    ];

    public function index(Request $request)
    {
        $filters = $this->filters($request);

        $query = Moon::query()->where('moons.available', 1);

        match ($filters['status']) {
            'available' => $query->where('status_flag', Moon::STATUS_AVAILABLE)->withoutActiveRenter(),
            'rented'    => $query->where('status_flag', Moon::STATUS_AVAILABLE)->withActiveRenter(),
            'all'       => $query,
            default     => $query->where('status_flag', self::STATUS_FLAGS[$filters['status']]),
        };

        $query
            ->when($filters['region'], fn (Builder $q, int $regionId) => $q->where('region_id', $regionId))
            ->when($filters['system'], fn (Builder $q, string $name) => $q->whereIn('solar_system_id', $this->systemIdsByName($name)))
            ->when($filters['mineral'], fn (Builder $q, array $typeIds) => $q->containsType($typeIds));

        $this->applySort($query, $filters);

        $moons = $query->with($this->relations($filters))
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        if ($filters['status'] === 'available') {
            // The antijoin already proved none of these moons has a rental running today. Seed the
            // relation so the status column does not lazy load one query per row.
            $moons->each(fn (Moon $moon) => $moon->setRelation('renter', new Collection()));
        }

        // We want to display information differently to administrators and prospective renters.

        return view('moons.public', [
            'moons'    => $moons,
            'regions'  => $this->regionOptions(),
            'minerals' => $this->mineralOptions(),
            'statuses' => self::STATUS_LABELS,
            'filters'  => $filters,
        ]);
    }

    /**
     * Reduce the query string to values the builder can be handed directly. Anything unrecognised
     * falls back to its default: a listing page should degrade rather than throw a validation error.
     */
    private function filters(Request $request): array
    {
        $status = $this->stringParam($request, 'status');
        $sort   = $this->stringParam($request, 'sort');
        $region = (int) $this->stringParam($request, 'region');

        return [
            'status'  => (isset(self::STATUS_LABELS[$status])) ? $status : 'available',
            'sort'    => ($sort === 'total' || isset(self::SORTS[$sort])) ? $sort : 'region',
            'dir'     => (strtolower($this->stringParam($request, 'dir')) === 'desc') ? 'desc' : 'asc',
            'region'  => $region ?: null,
            'system'  => $this->stringParam($request, 'system') ?: null,
            'mineral' => $this->mineralParam($request),
        ];
    }

    private function stringParam(Request $request, string $key): string
    {
        $value = $request->query($key);

        return (is_scalar($value)) ? trim((string) $value) : '';
    }

    /**
     * @return int[]
     */
    private function mineralParam(Request $request): array
    {
        $values = $request->query('mineral', []);

        if (! is_array($values)) {
            $values = [$values];
        }

        $ids = array_map('intval', array_filter($values, 'is_scalar'));

        return array_values(array_unique(array_filter($ids)));
    }

    private function applySort(Builder $query, array $filters): void
    {
        if ($filters['sort'] === 'total') {
            $query->orderByRaw('('.self::TOTAL_PERCENT.') '.$filters['dir']);
        } else {
            $query->orderBy(self::SORTS[$filters['sort']], $filters['dir']);
        }

        // Offset pagination needs a total order. Without a unique tiebreaker, rows tied on the
        // chosen column can repeat or disappear as the reader pages through.
        $sorted = self::SORTS[$filters['sort']] ?? null;

        foreach (['region_id', 'solar_system_id', 'planet', 'moon', 'moons.id'] as $tiebreaker) {
            if ($tiebreaker !== $sorted) {
                $query->orderBy($tiebreaker);
            }
        }
    }

    /**
     * The view reads $moon->active_renter and nothing else off the relation, so load only the
     * rental running today rather than a moon's entire rental history.
     */
    private function relations(array $filters): array
    {
        $relations = ['region', 'system', 'mineral_1', 'mineral_2', 'mineral_3', 'mineral_4'];

        if ($filters['status'] !== 'available') {
            $relations['renter'] = fn ($renters) => $renters->active();
        }

        return $relations;
    }

    /**
     * Solar system IDs whose name starts with the search term. `mapSolarSystems` is small and
     * static, so one prefix scan per request beats joining it into the main query.
     */
    private function systemIdsByName(string $name)
    {
        return DB::table('mapSolarSystems')
            ->where('solarSystemName', 'like', addcslashes($name, '%_\\').'%')
            ->pluck('solarSystemID');
    }

    /**
     * Every region holding inventory, with counts. Deliberately built independently of the active
     * filters, so selecting a region does not collapse the dropdown to that one region.
     */
    private function regionOptions()
    {
        return DB::table('moons')
            ->join('mapRegions', 'moons.region_id', '=', 'mapRegions.regionID')
            ->where('moons.available', 1)
            ->groupBy('moons.region_id', 'mapRegions.regionName')
            ->orderBy('mapRegions.regionName')
            ->get(['moons.region_id', 'mapRegions.regionName', DB::raw('COUNT(*) as moon_count')]);
    }

    /**
     * Minerals worth filtering on, resolved from a fixed name list. Moon materials only: a moon's
     * composition also carries ordinary ore, but nobody picks a rental by its Veldspar content.
     * A DISTINCT across the four mineral columns would cost four scans of `moons`; this costs
     * one of `invTypes`.
     *
     * Listed in the constant's own order, which is rarity ascending. `invTypes` carries nothing to
     * sort on that recovers it, so the ordering is applied in PHP over twenty rows.
     */
    private function mineralOptions()
    {
        $rarity = array_flip(Moon::MOON_MATERIALS);

        return Type::query()
            ->whereIn('typeName', Moon::MOON_MATERIALS)
            ->get(['typeID', 'typeName'])
            ->sortBy(fn (Type $type) => $rarity[$type->typeName] ?? PHP_INT_MAX)
            ->values();
    }
}
