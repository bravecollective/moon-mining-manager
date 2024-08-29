<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Application;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Renter;
use App\Models\Refinery;
use App\Models\RentalInvoice;
use App\Models\RentalPayment;
use App\Models\Moon;
use App\Classes\EsiConnection;

class RenterController extends Controller
{
    /**
     * List all current renting individuals and corporations.
     *
     * @throws \Exception
     * @noinspection PhpUnused
     */
    public function showRenters()
    {
        // Retrieve records.
        $renters = Renter::whereRaw('end_date IS NULL OR end_date >= CURDATE()')->get();
        $renters = $this->addMissingNames($renters);

        // Load the renter report.
        return view('renters.home', [
            'renters' => $renters,
            'type' => 'current',
        ]);

    }

    /**
     * @noinspection PhpUnused
     */
    public function showExpiredRenters()
    {
        $renters = Renter::whereRaw('end_date IS NOT NULL AND end_date < CURDATE()')->get();
        $renters = $this->addMissingNames($renters);

        return view('renters.home', [
            'renters' => $renters,
            'type' => 'expired',
        ]);
    }

    /**
     * Show a summary of invoices and payments for a specific refinery.
     *
     * @throws \Exception
     * @noinspection PhpUnused
     */
    public function refineryDetails($id = NULL)
    {
        if ($id == NULL) {
            return redirect('/renters');
        }

        $renter = Renter::where('refinery_id', $id)->first(); /* @var Renter $renter */
        $refinery = Refinery::where('observer_id', $id)->first();

        // Pull the renter character information via ESI.
        if ($renter !== null) {
            $esi = new EsiConnection;
            $conn = $esi->getConnection();
            $renter->character = $conn->invoke('get', '/characters/{character_id}/', [
                'character_id' => $renter->character_id,
            ]);
            $renter->character->avatar = $conn->invoke('get', '/characters/{character_id}/portrait/', [
                'character_id' => $renter->character_id,
            ]);

            // since this isn't used for checking, only display, I'm ok with stale data
            $renter->character->corporation = $conn->invoke('get', '/corporations/{corporation_id}/', [
                'corporation_id' => $renter->character->corporation_id,
            ]);
        }

        // Build a list of all the invoice and payment activity of this refinery.
        $invoices = RentalInvoice::where('refinery_id', $id)->get();
        $payments = RentalPayment::where('refinery_id', $id)->get();

        // Loop through each collection and add them to a master array.
        $activity_log = [];
        foreach ($invoices as $invoice) {
            $activity_log[] = $invoice;
        }
        foreach ($payments as $payment) {
            $activity_log[] = $payment;
        }

        // Sort the log into reverse chronological order.
        usort($activity_log, [$this, "sortByCreatedDate"]);

        return view('renters.refinery', [
            'renter' => $renter,
            'refinery' => $refinery,
            'activity_log' => $activity_log,
        ]);

    }

    /**
     * Show a summary of invoices and payments for a specific character that is renting refinery(s).
     *
     * @param null|int $id
     * @throws \Exception
     * @noinspection PhpUnused
     */
    public function renterDetails($id = NULL)
    {
        if ($id == NULL) {
            return redirect('/renters');
        }

        // Pull the renter character information via ESI.
        $esi = new EsiConnection;
        $conn = $esi->getConnection();
        $renter = $conn->invoke('get', '/characters/{character_id}/', [
            'character_id' => $id,
        ]);
        $renter->avatar = $conn->invoke('get', '/characters/{character_id}/portrait/', [
            'character_id' => $id,
        ]);

        // since this isn't used for checking, only display, I'm ok with stale data
        $renter->corporation = $conn->invoke('get', '/corporations/{corporation_id}/', [
            'corporation_id' => $renter->corporation_id,
        ]);

        // Build a list of all the invoice and payment activity of this refinery.
        $invoices = RentalInvoice::where('renter_id', $id)->get();
        $payments = RentalPayment::where('renter_id', $id)->get();

        // Loop through each collection and add them to a master array.
        $activity_log = [];
        foreach ($invoices as $invoice) {
            $activity_log[] = $invoice;
        }
        foreach ($payments as $payment) {
            $activity_log[] = $payment;
        }

        // Sort the log into reverse chronological order.
        usort($activity_log, [$this, "sortByCreatedDate"]);

        return view('renters.character', [
            'renter' => $renter,
            'activity_log' => $activity_log,
            'total_rent_paid' => DB::table('rental_payments')
                ->select(DB::raw('SUM(amount_received) AS total'))->where('renter_id', $id)->first()->total,
            'total_rent_due' => DB::table('renters')
                ->select(DB::raw('SUM(amount_owed) AS total'))->where('character_id', $id)->first()->total,
            'rentals' => Renter::where('character_id', $id)->whereNotNull('moon_id')->get(),
        ]);

    }

    /**
     * Form to edit an existing renter record.
     *
     * @param null|int $id
     * @throws \Exception
     * @noinspection PhpUnused
     */
    public function editRenter($id = NULL)
    {
        if ($id == NULL) {
            return redirect('/renters');
        }

        // Retrieve more detailed information about the named character.
        $renter = Renter::find($id);
        if ($renter === null) {
            return redirect('/renters');
        }

        $esi = new EsiConnection;
        $conn = $esi->getConnection();
        $character = $conn->invoke('get', '/characters/{character_id}/', [
            'character_id' => $renter->character_id,
        ]);
        $portrait = $conn->invoke('get', '/characters/{character_id}/portrait/', [
            'character_id' => $renter->character_id,
        ]);
        $character->portrait = $portrait->px128x128;

        // since this isn't used for checking, only display, I'm ok with stale data
        $corporation = $conn->invoke('get', '/corporations/{corporation_id}/', [
            'corporation_id' => $character->corporation_id,
        ]);
        $character->corporation = $corporation->name;

        return view('renters.edit', [
            'renter' => $renter,
            'character' => $character,
            'refineries' => Refinery::orderBy('name')->where('available', 1)->get(),
            'moons' => $this->getMoons(),
        ]);
    }

    /**
     * Form to create a new renter record.
     *
     * @noinspection PhpUnused
     */
    public function addNewRenter()
    {
        return view('renters.new', [
            'refineries' => Refinery::orderBy('name')->where('available', 1)->get(),
            'moons' => $this->getMoons(),
        ]);
    }

    /**
     * Handle new renter form submission.
     *
     * @noinspection PhpUnused
     * @param Request|\Illuminate\Support\Facades\Request $request
     * @return Application|RedirectResponse|Redirector
     */
    public function saveNewRenter(Request $request)
    {
        $request->validate([
            'type' => 'required',
            'character_id' => 'required|numeric',
            'refinery_id' => 'nullable|numeric',
            'moon_id' => 'nullable|numeric',
            'monthly_rental_fee' => 'required|numeric',
            'start_date' => 'required|date',
        ]);

        // If validation rules pass, then create the new Renter object.
        $renter = new Renter;
        $this->populateDataAndSave($renter, $request);

        return redirect('/renters');
    }

    /**
     * Save updated information on an existing renter.
     * @param mixed $id
     * @param Request|\Illuminate\Support\Facades\Request $request
     * @return RedirectResponse|Redirector
     * @noinspection PhpUnused
     */
    public function updateRenter($id, Request $request)
    {
        $request->validate([
            'type' => 'required',
            'character_id' => 'required|numeric',
            'refinery_id' => 'nullable|numeric',
            'moon_id' => 'nullable|numeric',
            'monthly_rental_fee' => 'required|numeric',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date',
        ]);

        // If validation rules pass, then update the existing Renter record.
        $renter = Renter::find($id);
        $this->populateDataAndSave($renter, $request);

        return redirect('/renters');
    }

    private function populateDataAndSave(Renter $renter, $request)
    {
        $renter->type = ($request->type == Renter::TYPE_ACTIVE)? Renter::TYPE_ACTIVE : Renter::TYPE_PASSIVE;
        $renter->character_id = $request->character_id;
        $renter->character_name = $this->getName($renter->character_id);
        $renter->refinery_id = $request->refinery_id;
        $renter->moon_id = $request->moon_id;
        $renter->notes = $request->notes;
        $renter->monthly_rental_fee = $request->monthly_rental_fee;
        $renter->start_date = $request->start_date;
        $renter->end_date = $request->end_date ?: null;
        $renter->updated_by = Auth::user()->eve_id;

        $renter->save();
    }

    private function sortByCreatedDate($a, $b): int
    {
        if ($a->created_at == $b->created_at) {
            return 0;
        }
        return ($a->created_at > $b->created_at) ? -1 : 1;
    }

    private function getName($characterId)
    {
        $esi = new EsiConnection;
        try {
            $character = $esi->getConnection()->invoke('get', '/characters/{character_id}/', [
                'character_id' => $characterId,
            ]);
        } catch (\Exception $e) {
            return '';
        }

        return $character->name;
    }

    /**
     * @param Collection|Renter[] $renters
     * @return Renter[]
     */
    private function addMissingNames($renters)
    {
        $esi = new EsiConnection;
        foreach ($renters as $renter) {
            if (! empty($renter->character_name)) {
                continue;
            }

            $character = null;
            try {
                $character = $esi->getConnection()->invoke('get', '/characters/{character_id}/', [
                    'character_id' => $renter->character_id,
                ]);
            } catch (\Exception $e) {
            }
            if ($character) {
                $renter->character_name = $character->name;
                $renter->save();
            }
        }

        return $renters;
    }

    private function getMoons()
    {
        // Pull all the moon data.
        return Moon::with(['region', 'system'])
            ->join('mapSolarSystems', 'mapSolarSystems.SolarSystemId', '=', 'moons.solar_system_id')
            ->where('available', 1)
            ->orderBy('region_id')
            ->orderBy('mapSolarSystems.solarSystemName')
            ->orderBy('planet')
            ->orderBy('moon')
            ->get();
    }
}
