<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Moon;
use App\Models\Renter;

/**
 * Development fixture: fills the `moons` and `renters` tables with believable data so the
 * public moon list at /moons can be worked on without a live ESI import.
 *
 * Region, system and mineral IDs are resolved from the imported EVE static data, so the
 * relationships the view walks ($moon->region->regionName, $moon->mineral_1->typeName, ...)
 * all resolve to real names. Run `php artisan eve:import-static-data` first.
 *
 * Holdings are confined to the five regions in REGIONS, so the region dropdown gets the shape
 * production has - a handful of regions holding thousands each - rather than a long tail of
 * four-moon regions.
 *
 * Volume is set by MOON_SEED_COUNT, defaulting to 100k:
 *
 *     MOON_SEED_COUNT=1000 php artisan db:seed --class=MoonSeeder
 *
 * Renters get history, not just current rentals, so `renters` ends up several times larger
 * than the number of moons actually rented today. That is the case the /moons query has to
 * survive, since nothing indexes `renters.moon_id`.
 *
 * WARNING: this empties `moons` and `renters` before seeding so it can be re-run.
 */
class MoonSeeder extends Seeder
{
    /** Default moon count. Override per run with MOON_SEED_COUNT. */
    private const MOON_COUNT = 100000;

    /** Sovereign holdings. Names must match `mapRegions`.`regionName` exactly. */
    private const REGIONS = ['Tenerifis', 'Impass', 'Detorid', 'Feythabolis', 'Catch'];

    /** Rows per INSERT. 21 columns x 500 stays well inside the placeholder limit. */
    private const CHUNK = 500;

    /**
     * Moons numbered per planet before rolling over to the next planet. Real systems top out
     * around 10, but 425 systems cannot hold 100k moons at a realistic density, so the
     * numbering stretches. Planet and moon numbers stop being plausible above ~40k moons;
     * the row count is what the scale test needs, not the numbering.
     */
    private const MOONS_PER_PLANET = 16;

    /**
     * The 20 moon materials, grouped by rarity. Rarity drives the rental price below,
     * the same way it drives a moon's real value in game.
     */
    private const GOO_TIERS = [
        'ubiquitous'   => ['Bitumens', 'Coesite', 'Sylvite', 'Zeolites'],
        'common'       => ['Cobaltite', 'Euxenite', 'Scheelite', 'Titanite'],
        'uncommon'     => ['Chromite', 'Otavite', 'Sperrylite', 'Vanadinite'],
        'rare'         => ['Carnotite', 'Cinnabar', 'Pollucite', 'Zircon'],
        'exceptional'  => ['Loparite', 'Monazite', 'Xenotime', 'Ytterbite'],
    ];

    /** How often a moon's headline material comes from each tier (higher = more common). */
    private const TIER_WEIGHTS = [
        'ubiquitous' => 30, 'common' => 28, 'uncommon' => 22, 'rare' => 14, 'exceptional' => 6,
    ];

    /** Rough monthly ISK an individual renter pays, before jitter. */
    private const TIER_BASE_RENT = [
        'ubiquitous' => 320000000, 'common' => 600000000, 'uncommon' => 1100000000,
        'rare' => 1900000000, 'exceptional' => 3400000000,
    ];

    /**
     * Sample renter notes. Hand-written rather than $faker->sentence(): the bundled
     * fzaninotto/faker is abandoned and its Lorem provider is broken on PHP 8.
     */
    private const RENTER_NOTES = [
        'Renewed for another 6 months.',
        'Paying quarterly, agreed with rental team.',
        'Moved from a previous moon in the same constellation.',
        'Corp contact handles payment, not the listed character.',
        'Late payer - watch this one.',
        'Long standing renter, no issues.',
        'Asked about upgrading to a better moon when one frees up.',
        'Split rental with an alt corp.',
    ];

    /** @var int[] Goo type IDs, the only thing a seeded composition is built from. */
    private array $mineralPool = [];

    /** @var array<string, int> Goo type IDs keyed by name. */
    private array $gooIds = [];

    /** @var string[] Weighted tier pool, one entry per weight point. */
    private array $tierPool = [];

    /** @var int[] Weighted status_flag pool. */
    private array $statusPool = [];

    /** @var string[] Character names, generated once and reused - faker is too slow per row. */
    private array $names = [];

    public function run(): void
    {
        $faker = \Faker\Factory::create();

        // Fixed seed so re-running produces the same page - makes visual diffing possible.
        $faker->seed(20260812);
        mt_srand(20260812);

        $target = (int) (env('MOON_SEED_COUNT') ?: self::MOON_COUNT);

        $this->gooIds      = $this->resolveTypeIds(array_merge(...array_values(self::GOO_TIERS)));
        $this->mineralPool = array_values($this->gooIds);
        $this->tierPool   = $this->weightedTierPool();
        $this->statusPool = $this->weightedStatusPool();
        $this->names      = $this->namePool($faker);

        $systems = $this->systems();

        if ($systems->isEmpty()) {
            $this->command->error(
                'No nullsec systems found in '.implode(', ', self::REGIONS)
                .' - run `php artisan eve:import-static-data` first.'
            );

            return;
        }

        $this->command->warn('Clearing the moons and renters tables before seeding...');
        DB::table('renters')->delete();
        DB::table('moons')->delete();

        $this->command->info(sprintf(
            'Seeding %s moons across %d systems in %d regions...',
            number_format($target), $systems->count(), count(self::REGIONS)
        ));

        $this->seedMoons($systems, $target);
        $this->seedRenters();
        $this->report();
    }

    /**
     * Nullsec systems in the held regions. Joining through the system keeps region_id
     * consistent with solar_system_id.
     */
    private function systems()
    {
        return DB::table('mapSolarSystems')
            ->join('mapRegions', 'mapRegions.regionID', '=', 'mapSolarSystems.regionID')
            ->whereIn('mapRegions.regionName', self::REGIONS)
            ->where('mapSolarSystems.security', '<', 0.0)
            ->orderBy('mapSolarSystems.solarSystemID')
            ->get(['mapSolarSystems.solarSystemID', 'mapSolarSystems.regionID']);
    }

    /**
     * Spread $target moons over the systems, weighted so density varies between them, and
     * insert in chunks. Planet and moon numbers are allocated sequentially per system, which
     * guarantees uniqueness without the retry loop a random allocator would need at this size.
     */
    private function seedMoons($systems, int $target): void
    {
        $weights = [];
        foreach ($systems as $index => $system) {
            $weights[$index] = mt_rand(60, 140);
        }
        $totalWeight = array_sum($weights);

        // Proportional shares lose a few rows to flooring; hand the remainder to the first systems.
        $shares = [];
        $allocated = 0;
        foreach ($weights as $index => $weight) {
            $shares[$index] = (int) floor($target * $weight / $totalWeight);
            $allocated += $shares[$index];
        }
        for ($index = 0; $allocated < $target; $index = ($index + 1) % count($shares)) {
            $shares[$index]++;
            $allocated++;
        }

        $now = now();
        $bar = $this->command->getOutput()->createProgressBar($target);
        $buffer = [];

        foreach ($systems as $index => $system) {
            for ($n = 0; $n < $shares[$index]; $n++) {
                $buffer[] = $this->moonRow(
                    (int) $system->regionID,
                    (int) $system->solarSystemID,
                    1 + intdiv($n, self::MOONS_PER_PLANET),
                    1 + $n % self::MOONS_PER_PLANET,
                    $now,
                );

                if (count($buffer) >= self::CHUNK) {
                    DB::table('moons')->insert($buffer);
                    $bar->advance(count($buffer));
                    $buffer = [];
                }
            }
        }

        if ($buffer) {
            DB::table('moons')->insert($buffer);
            $bar->advance(count($buffer));
        }

        $bar->finish();
        $this->command->newLine();
    }

    private function moonRow(int $regionId, int $systemId, int $planet, int $moon, $now): array
    {
        $tier = $this->tierPool[array_rand($this->tierPool)];

        // The headline material comes from the chosen tier; the rest is other goo. Ordinary ore
        // is never seeded, so every row is composed of the 20 materials the filter offers.
        $tierNames = self::GOO_TIERS[$tier];
        $minerals = [$this->gooIds[$tierNames[array_rand($tierNames)]]];

        $wanted = mt_rand(2, 4);
        while (count($minerals) < $wanted) {
            $candidate = $this->mineralPool[array_rand($this->mineralPool)];
            if (! in_array($candidate, $minerals, true)) {
                $minerals[] = $candidate;
            }
        }

        $percents = $this->splitHundred(count($minerals));

        $activeRent  = $this->jitter(self::TIER_BASE_RENT[$tier]);
        $passiveRent = $this->jitter((int) round(self::TIER_BASE_RENT[$tier] * 1.45));

        return [
            'region_id'                        => $regionId,
            'solar_system_id'                  => $systemId,
            'planet'                           => $planet,
            'moon'                             => $moon,
            'mineral_1_type_id'                => $minerals[0],
            'mineral_1_percent'                => $percents[0],
            'mineral_2_type_id'                => $minerals[1],
            'mineral_2_percent'                => $percents[1],
            'mineral_3_type_id'                => $minerals[2] ?? null,
            'mineral_3_percent'                => $percents[2] ?? null,
            'mineral_4_type_id'                => $minerals[3] ?? null,
            'mineral_4_percent'                => $percents[3] ?? null,
            'monthly_rental_fee'               => $activeRent,
            'monthly_corp_rental_fee'          => $passiveRent,
            'previous_monthly_rental_fee'      => $this->jitter($activeRent),
            'previous_monthly_corp_rental_fee' => $this->jitter($passiveRent),
            'renter_id'                        => null,
            'status_flag'                      => $this->statusPool[array_rand($this->statusPool)],
            'available'                        => 1,
            'created_at'                       => $now,
            'updated_at'                       => $now,
        ];
    }

    /**
     * Give roughly a third of the otherwise-available moons a renter today, and give most of
     * them a rental history on top. A few current rentals are expired on purpose: the moon
     * falls back to "Available" and exercises Moon::getActiveRenterAttribute().
     *
     * The history is the point. It makes `renters` several times larger than the set of moons
     * rented right now, which is what the antijoin in Moon::scopeWithoutActiveRenter() has to
     * cope with given nothing indexes `renters.moon_id`.
     */
    private function seedRenters(): void
    {
        $today = strtotime(gmdate('Y-m-d'));
        $day = 86400;
        $now = now();

        $total = DB::table('moons')->where('status_flag', Moon::STATUS_AVAILABLE)->count();
        $this->command->info('Seeding renters and rental history for '.number_format($total).' rentable moons...');

        $bar = $this->command->getOutput()->createProgressBar($total);
        $buffer = [];
        $index = 0;

        DB::table('moons')
            ->where('status_flag', Moon::STATUS_AVAILABLE)
            ->orderBy('id')
            ->chunkById(2000, function ($moons) use (&$buffer, &$index, $today, $day, $now, $bar) {
                foreach ($moons as $moon) {
                    // Past rentals, all ended before today, so they never count as active.
                    for ($past = mt_rand(0, 3); $past > 0; $past--) {
                        $end   = $today - $day * mt_rand(30, 1500);
                        $start = $end - $day * mt_rand(90, 900);
                        $buffer[] = $this->renterRow($moon, $start, $end, $now);
                    }

                    if (mt_rand(1, 100) <= 35) {
                        $start = $today - $day * mt_rand(30, 420);

                        // Every 7th current rental has already lapsed, so the moon reads as
                        // available again despite having a renter row.
                        if ($index % 7 === 6) {
                            $end = $today - $day * mt_rand(2, 21);
                        } else {
                            $end = mt_rand(1, 100) <= 25 ? $today + $day * mt_rand(30, 300) : null;
                        }

                        $buffer[] = $this->renterRow($moon, $start, $end, $now);
                        $index++;
                    }

                    if (count($buffer) >= self::CHUNK) {
                        DB::table('renters')->insert($buffer);
                        $buffer = [];
                    }
                }

                $bar->advance($moons->count());
            });

        if ($buffer) {
            DB::table('renters')->insert($buffer);
        }

        $bar->finish();
        $this->command->newLine();
    }

    private function renterRow($moon, int $start, ?int $end, $now): array
    {
        $isCorp = mt_rand(1, 100) <= 40;
        $fee = $isCorp ? $moon->monthly_corp_rental_fee : $moon->monthly_rental_fee;

        return [
            'type'               => $isCorp ? Renter::TYPE_PASSIVE : Renter::TYPE_ACTIVE,
            'character_id'       => mt_rand(90000000, 2120000000),
            'character_name'     => $this->names[array_rand($this->names)],
            'refinery_id'        => null,
            'moon_id'            => $moon->id,
            'notes'              => mt_rand(1, 100) <= 30
                ? self::RENTER_NOTES[array_rand(self::RENTER_NOTES)]
                : null,
            'monthly_rental_fee' => $fee,
            // Most renters are square; some are carrying a balance.
            'amount_owed'        => mt_rand(1, 100) <= 35 ? mt_rand(1, 4) * $fee : 0,
            'start_date'         => gmdate('Y-m-d', $start),
            'end_date'           => $end ? gmdate('Y-m-d', $end) : null,
            'updated_by'         => null,
            'created_at'         => $now,
            'updated_at'         => $now,
        ];
    }

    /**
     * Look type names up in the imported static data, keyed by name.
     *
     * @param  string[]  $names
     * @return array<string, int>
     */
    private function resolveTypeIds(array $names): array
    {
        $ids = DB::table('invTypes')
            ->whereIn('typeName', $names)
            ->pluck('typeID', 'typeName')
            ->toArray();

        $missing = array_diff($names, array_keys($ids));
        if ($missing) {
            throw new \RuntimeException(
                'Missing EVE types: ' . implode(', ', $missing) . '. Run `php artisan eve:import-static-data`.'
            );
        }

        return $ids;
    }

    /**
     * Faker's name generators are far too slow to call once per renter at this volume, so
     * build a pool up front and pick from it.
     *
     * @return string[]
     */
    private function namePool(\Faker\Generator $faker): array
    {
        $names = [];
        for ($i = 0; $i < 2000; $i++) {
            $names[] = $faker->firstName . ' ' . $faker->lastName;
        }

        return array_values(array_unique($names));
    }

    /**
     * Split 100% across $count minerals, largest share first, always totalling exactly 100.
     *
     * @return float[]
     */
    private function splitHundred(int $count): array
    {
        $weights = [];
        for ($i = 0; $i < $count; $i++) {
            $weights[] = mt_rand(10, 40);
        }

        $total = array_sum($weights);
        $percents = [];
        $running = 0.0;

        foreach ($weights as $i => $weight) {
            if ($i === $count - 1) {
                // Last share absorbs the rounding drift so the row totals 100%.
                $percents[] = round(100 - $running, 2);
                break;
            }
            $share = round($weight / $total * 100, 2);
            $percents[] = $share;
            $running += $share;
        }

        rsort($percents);

        return $percents;
    }

    /** Nudge a figure by +/-12% so the columns aren't suspiciously round. */
    private function jitter(int $amount): int
    {
        return (int) round($amount * mt_rand(88, 112) / 100);
    }

    /** @return string[] */
    private function weightedTierPool(): array
    {
        $pool = [];
        foreach (self::TIER_WEIGHTS as $tier => $weight) {
            $pool = array_merge($pool, array_fill(0, $weight, $tier));
        }

        return $pool;
    }

    /** @return int[] */
    private function weightedStatusPool(): array
    {
        return array_merge(
            array_fill(0, 70, Moon::STATUS_AVAILABLE),
            array_fill(0, 12, Moon::STATUS_ALLIANCE_OWNED),
            array_fill(0, 10, Moon::STATUS_LOTTERY_ONLY),
            array_fill(0, 8, Moon::STATUS_RESERVED),
        );
    }

    /**
     * Counts come from aggregates rather than hydrated models - at 100k moons the old
     * Moon::with('renter')->get() would exhaust the memory limit.
     */
    private function report(): void
    {
        $rented = Moon::query()
            ->where('status_flag', Moon::STATUS_AVAILABLE)
            ->withActiveRenter()
            ->count();

        $available = Moon::query()
            ->where('status_flag', Moon::STATUS_AVAILABLE)
            ->withoutActiveRenter()
            ->count();

        $byStatus = DB::table('moons')
            ->groupBy('status_flag')
            ->pluck(DB::raw('COUNT(*)'), 'status_flag');

        $this->command->info(sprintf(
            'Seeded %s moons and %s renter rows (%s available, %s rented, %s alliance owned, %s lottery only, %s reserved).',
            number_format(array_sum($byStatus->all())),
            number_format(DB::table('renters')->count()),
            number_format($available),
            number_format($rented),
            number_format($byStatus[Moon::STATUS_ALLIANCE_OWNED] ?? 0),
            number_format($byStatus[Moon::STATUS_LOTTERY_ONLY] ?? 0),
            number_format($byStatus[Moon::STATUS_RESERVED] ?? 0),
        ));

        $this->command->table(
            ['Region', 'Moons'],
            DB::table('moons')
                ->join('mapRegions', 'mapRegions.regionID', '=', 'moons.region_id')
                ->groupBy('moons.region_id', 'mapRegions.regionName')
                ->orderByDesc(DB::raw('COUNT(*)'))
                ->get(['mapRegions.regionName', DB::raw('COUNT(*) as moons')])
                ->map(fn ($row) => [$row->regionName, number_format($row->moons)])
                ->all()
        );
    }
}
