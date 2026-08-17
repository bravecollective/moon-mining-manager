<?php

namespace App\Http\Controllers;

use App\Models\Extraction;
use App\Models\MiningActivity;
use App\Models\Type;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExtractionsController extends Controller
{
    /**
     * Cache of ore types by name.
     *
     * @var array<string, array<int>>
     */
    private $oreTypes = [];

    public function index(Request $request): View
    {
        $limit = (int)$request->get('limit', 50);
        $page = (int)$request->get('page', 1);
        $offset = $page > 1 ? ($page - 1) * $limit : 0;
        $corporation = (int)$request->get('corporation');
        $corporationId = $corporation > 0 ? $corporation : config('eve.tax_corporation_id');
        $moonId = (int)$request->get('moon');

        // All moons with detonations from the selected corporation
        /** @noinspection PhpUndefinedMethodInspection */
        $moonsQuery = Extraction::select('moon_id')->distinct()
            ->with('invMoon:itemID,itemName')
            ->whereHas('refinery', function ($q)  use ($corporationId) {
                $q->where('corporation_id', '=', $corporationId);
            });
        $moons = array_map(function ($row) {
            return ['id' => $row['moon_id'],'name' => $row['inv_moon']['itemName']];
        }, $moonsQuery->get()->sortBy('invMoon.itemName')->toArray());

        // Collect all detonation dates from all moons
        $detonations = [];
        $detonationsQuery = Extraction::select(['id', 'moon_id', 'notification_timestamp'])
            ->orderBy('notification_timestamp', 'desc');
        foreach ($detonationsQuery->get() as $detonation) {
            $detonations[$detonation->moon_id][] = [
                'detonation_id' => $detonation->id,
                'detonation_time' => $detonation->notification_timestamp,
            ];
        }

        // Get extraction data
        /** @noinspection PhpUndefinedMethodInspection */
        $extractionQuery = (new Extraction)
            ->select([
                'id', 'moon_id', 'extractions.refinery_id', 'notification_timestamp',
                'ore1_type_id', 'ore1_volume', 'ore2_type_id', 'ore2_volume',
                'ore3_type_id', 'ore3_volume', 'ore4_type_id', 'ore4_volume',
            ])
            ->with([
                'invMoon:itemID,itemName', 'refinery:observer_id,corporation_id,name',
                'ore1:typeID,typeName', 'ore2:typeID,typeName', 'ore3:typeID,typeName', 'ore4:typeID,typeName',
            ])
            ->whereHas('refinery', function ($q)  use ($corporationId) {
                $q->where('corporation_id', '=', $corporationId);
            })
            ->orderBy('notification_timestamp', 'desc');
        if ($moonId > 0) {
            $extractionQuery->whereIn('moon_id', [$moonId]);
        }
        $total = $extractionQuery->count();
        if ($limit > 0) {
            $extractionQuery->offset($offset)->limit($limit);
        }
        $extractions = $extractionQuery->get();

        // Fetch mined volumes
        foreach ($extractions as $num => $extraction) {
            // find datetime range
            $from = null;
            $to = date('Y-m-d H:i:s');
            foreach ($detonations[$extraction->moon_id] as $key => $detonationTime) {
                if ($detonationTime['detonation_id'] === $extraction->id) {
                    $from = $detonationTime['detonation_time'];
                    // entries in array are order by time, desc
                    if (isset($detonations[$extraction->moon_id][$key - 1])) {
                        $to = $detonations[$extraction->moon_id][$key - 1]['detonation_time'];
                    }
                    break;
                }
            }
            if (!$from) {
                continue;
            }

            // fetch sums
            if ($extraction->ore1) { // should always be there
                $extractions[$num]['ore1_mined'] = (new MiningActivity)
                    ->where('refinery_id', $extraction->refinery_id)
                    ->whereIn('type_id', $this->getTypeIds($extraction->ore1->typeName))
                    ->where('created_at', '>=', $from)
                    ->where('created_at', '<', $to)
                    ->sum('quantity');
            }
            if ($extraction->ore2) {
                $extractions[$num]['ore2_mined'] = (new MiningActivity)
                    ->where('refinery_id', $extraction->refinery_id)
                    ->whereIn('type_id', $this->getTypeIds($extraction->ore2->typeName))
                    ->where('created_at', '>=', $from)
                    ->where('created_at', '<', $to)
                    ->sum('quantity');
            }
            if ($extraction->ore3) {
                $extractions[$num]['ore3_mined'] = (new MiningActivity)
                    ->where('refinery_id', $extraction->refinery_id)
                    ->whereIn('type_id', $this->getTypeIds($extraction->ore3->typeName))
                    ->where('created_at', '>=', $from)
                    ->where('created_at', '<', $to)
                    ->sum('quantity');
            }
            if ($extraction->ore4) {
                $extractions[$num]['ore4_mined'] = (new MiningActivity)
                    ->where('refinery_id', $extraction->refinery_id)
                    ->whereIn('type_id', $this->getTypeIds($extraction->ore4->typeName))
                    ->where('created_at', '>=', $from)
                    ->where('created_at', '<', $to)
                    ->sum('quantity');
            }
        }

        return view('moons.extractions', [
            'corporationTax' => config('eve.tax_corporation_id'),
            'corporationRent' => config('eve.rent_corporation_id'),
            'corporationId' => $corporationId,
            'moonId' => $moonId,
            'moons' => $moons,
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'items' => $extractions,
        ]);
    }

    private function getTypeIds(string $typeName): array
    {
        if (!isset($this->oreTypes[$typeName])) {
            $ores = Type::select('typeID')
                ->where('typeName', 'LIKE', "%$typeName")
                ->whereIn('groupID', [1884, 1920, 1921, 1922, 1923]);

            $this->oreTypes[$typeName] = array_map(function ($row) {
                return $row['typeID'];
            }, $ores->get()->toArray());
        }

        return $this->oreTypes[$typeName];
    }
}
