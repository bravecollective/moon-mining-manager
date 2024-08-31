<?php
/** @noinspection PhpUnused */

namespace App\Http\Controllers;

use App\Classes\CalculateRent;
use App\Models\Renter;
use Illuminate\Http\Request;
use App\Models\Moon;
use App\Models\Region;
use App\Models\SolarSystem;
use App\Models\Type;

class MoonAdminController extends Controller
{
    private $romans = [
        'M' => 1000,
        'CM' => 900,
        'D' => 500,
        'CD' => 400,
        'C' => 100,
        'XC' => 90,
        'L' => 50,
        'XL' => 40,
        'X' => 10,
        'IX' => 9,
        'V' => 5,
        'IV' => 4,
        'I' => 1,
    ];

    public function index()
    {
        $moons = Moon::with([
            'region', 'system', 'renter',
            'mineral_1', 'mineral_2', 'mineral_3', 'mineral_4',
        ])
            ->where('available', 1)
            ->orderBy('region_id')
            ->orderBy('solar_system_id')
            ->orderBy('planet')
            ->orderBy('moon')
            ->get();

        return view('moons.list', ['moons' => $moons]);
    }

    /**
     * Ajax request
     */
    public function updateStatus(Request $request): array
    {
        $success = false;

        $moon = Moon::find($request->input('id'));
        $status = (int) $request->input('status');

        if (
            $moon &&
            in_array($status, [
                Moon::STATUS_AVAILABLE,
                Moon::STATUS_ALLIANCE_OWNED,
                Moon::STATUS_LOTTERY_ONLY,
                Moon::STATUS_RESERVED
            ])
        ) {
            $moon->status_flag = $status;
            $success = $moon->save();
        }

        return ['success' => $success ];
    }

    public function admin()
    {
        return view('moons.admin');
    }

    public function import(Request $request)
    {

        // Convert the dump of spreadsheet data into a structured array.
        $data = [];
        $lines = explode("\n", $request->input('data'));
        foreach ($lines as $line) {
            $data[] = explode("\t", $line);
        }

        // Loop through each row and process it into the database.
        foreach ($data as $row) {
            $moon = new Moon;
            $moon->region_id = Region::where('regionName', $row[0])->first()->regionID;
            $moon->solar_system_id = SolarSystem::where('solarSystemName', $row[1])->first()->solarSystemID;
            $moon->planet = $row[2];
            $moon->moon = $row[3];
            /*
            if ($row[4])
            {
                $moon->renter_id = Miner::where('name', $row[4])->first()->eve_id;
            }
            */
            $moon->mineral_1_type_id = Type::where('typeName', $row[5])->first()->typeID;
            $moon->mineral_1_percent = round(str_replace(',', '.', $row[6]), 3);
            $moon->mineral_2_type_id = Type::where('typeName', $row[7])->first()->typeID;
            $moon->mineral_2_percent = round(str_replace(',', '.', $row[8]), 3);
            if (isset($row[9])) {
                $moon->mineral_3_type_id = Type::where('typeName', $row[9])->first()->typeID;
                $moon->mineral_3_percent = round(str_replace(',', '.', $row[10]), 3);
            }
            if (isset($row[11])) {
                $moon->mineral_4_type_id = Type::where('typeName', $row[11])->first()->typeID;
                $moon->mineral_4_percent = round(str_replace(',', '.', $row[12]), 3);
            }
            $moon->monthly_rental_fee = 0;
            $moon->monthly_corp_rental_fee = 0;
            $moon->previous_monthly_rental_fee = 0;
            $moon->previous_monthly_corp_rental_fee = 0;
            $moon->save();
        }

        // Redirect back to admin.
        return redirect('/moon-admin')->with('message', 'Import done.');

    }

    /** @noinspection PhpUnused */
    public function importSurveyData(Request $request)
    {
        $added = 0;
        $updated = 0;

        $moon = null;
        $newMoon = false;
        $num = 0;
        $planet = null;
        $moonNumber = null;

        foreach (explode("\n", $request->input('data')) as $row) {
            $cols = explode("\t", $row);

            // new moon?
            $matches = [];
            if (preg_match('/([A-Z0-9-]{6}) ([XVI]{1,4}) - Moon ([0-9]{1,2})/', trim($cols[0]), $matches)) {
                // save previous moon
                if ($moon instanceof Moon) {
                    $moon->save();
                }

                $newMoon = true;
                $num = 0;
                $planet = $this->romanNumberToInteger($matches[2]);
                $moonNumber = $matches[3];

                continue;
            }

            if ($newMoon) {
                $newMoon = false;
                $solarSystem = trim($cols[4]);
                $moon = Moon::where([
                    ['solar_system_id', $solarSystem],
                    ['planet', $planet],
                    ['moon', $moonNumber],
                ])->first();
                if (! $moon) {
                    $moon = new Moon();
                    $added ++;
                } else {
                    $updated ++;
                }
                $moon->solar_system_id = $solarSystem;
                $moon->planet = $planet;
                $moon->moon = $moonNumber;
                $moon->monthly_rental_fee = 0;
                $moon->monthly_corp_rental_fee = 0;
                if (! $moon->previous_monthly_rental_fee) {
                    $moon->previous_monthly_rental_fee = 0;
                    $moon->previous_monthly_corp_rental_fee = 0;
                }
                $moon->mineral_1_type_id = null;
                $moon->mineral_1_percent = null;
                $moon->mineral_2_type_id = null;
                $moon->mineral_2_percent = null;
                $moon->mineral_3_type_id = null;
                $moon->mineral_3_percent = null;
                $moon->mineral_4_type_id = null;
                $moon->mineral_4_percent = null;
            } elseif ($moon === null) {
                // skip the headline
                continue;
            }

            // moon ore data
            $num ++;
            $moon->region_id = SolarSystem::where('solarSystemID', $moon->solar_system_id)->first()->regionID;
            $moon->{'mineral_' . $num . '_type_id'} = trim($cols[3]);
            $moon->{'mineral_' . $num . '_percent'} = round(trim($cols[2]) * 100, 3);
        }

        // save last moon
        if ($moon instanceof Moon) {
            $moon->save();
        }

        // Redirect back to admin.
        return redirect('/moon-admin')->with(
            'message',
            "Import done: $added moons added, $updated moons updated."
        );
    }

    /**
     * @throws \Exception
     */
    public function export(Request $request)
    {
        $all = $request->input('all');

        $rows = [];
        $rows[] = [
            'Region',
            'System',
            'ID',
            'Location',
            'P',
            'M',
            'Renter (char ID)',
            'Renter (char name)',
            'status',
            '',
            'Mineral 1',
            '% 1',
            'Mineral 2',
            '% 2',
            'Mineral 3',
            '% 3',
            'Mineral 4',
            '% 4',
        ];

        $with = ['region', 'system', 'renter', 'mineral_1', 'mineral_2', 'mineral_3', 'mineral_4'];
        if ($all === '1') {
            $moons = Moon::with($with)->get()->sortBy('id');
            $filename = 'moon-export-all-' . date('Ymd-His') . '.csv';
        } else {
            $moons = Moon::with($with)->where('available', 1)->get()->sortBy('id');
            $filename = 'moon-export-available-' . date('Ymd-His') . '.csv';
        }

        foreach ($moons as $moon) { /* @var $moon Moon */
            // get renter name
            $renterName = '';
            $renter = $moon->getActiveRenterAttribute();
            if ($renter) {
                $renterName = $renter->character_name;
            } elseif ($moon->status_flag == Moon::STATUS_ALLIANCE_OWNED) {
                $renterName = 'Alliance';
            }

            $cols = [
                $moon->region ? $moon->region->regionName : '',
                $moon->system ? $moon->system->solarSystemName : '',
                $moon->id,
                $this->integerToRomanNumber($moon->planet) . ' - M ' . $moon->moon,
                $moon->planet,
                $moon->moon,
                isset($moon->renter[0]) ? $moon->renter[0]->character_id : '',
                $renterName,
                '', // status
                '', //
                $moon->mineral_1?->typeName,
                $moon->mineral_1_percent ? $moon->mineral_1_percent . '%' : '',
                $moon->mineral_2?->typeName,
                $moon->mineral_2_percent ? $moon->mineral_2_percent . '%' : '',
                $moon->mineral_3?->typeName,
                $moon->mineral_3_percent ? $moon->mineral_3_percent . '%' : '',
                $moon->mineral_4?->typeName,
                $moon->mineral_4_percent ? $moon->mineral_4_percent . '%' : '',
            ];
            $rows[] = $cols;
        }

        return response($this->arrayToCsv($rows), 200, [
            'Content-Type' => 'text/cvs',
            'Content-disposition' => "attachment;filename=$filename"
        ]);
    }

    /**
     * Calculate the monthly rental fee for every moon, based on its mineral composition.
     */
    public function calculate()
    {
        $calc = new CalculateRent();

        // Grab all of the (available) moons and loop through them.
        $moons = Moon::where('available', 1)->get();
        foreach ($moons as $moon) {
            $calc->updateMoon($moon, Renter::TYPE_ACTIVE);
            $calc->updateMoon($moon, Renter::TYPE_PASSIVE);
        }

        // Redirect back to admin.
        return redirect('/moon-admin')->with('message', 'Calculation done.');
    }

    private function romanNumberToInteger($roman)
    {
        $result = 0;
        foreach ($this->romans as $key => $value) {
            while (strpos($roman, $key) === 0) {
                $result += $value;
                $roman = substr($roman, strlen($key));
            }
        }
        return $result;
    }

    private function integerToRomanNumber($number)
    {
        $returnValue = '';
        while ($number > 0) {
            foreach ($this->romans as $roman => $int) {
                if ($number >= $int) {
                    $number -= $int;
                    $returnValue .= $roman;
                    break;
                }
            }
        }
        return $returnValue;
    }

    private function arrayToCsv(array $rows)
    {
        $result = '';

        $fp = fopen('php://temp', 'w');

        foreach ($rows as $fields) {
            fputcsv($fp, $fields, ';');
        }

        rewind($fp);
        while (($buffer = fgets($fp, 4096)) !== false) {
            $result .= $buffer;
        }

        fclose($fp);

        return $result;
    }
}
