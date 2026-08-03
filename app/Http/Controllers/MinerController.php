<?php
/** @noinspection PhpUnused */

namespace App\Http\Controllers;

use App\Models\Corporation;
use App\Models\Invoice;
use App\Models\Miner;
use App\Models\MiningActivity;
use App\Models\Payment;
use Illuminate\Http\Request;

class MinerController extends Controller
{

    /**
     * List all miners together with their total payments.
     */
    public function showMiners(Request $request)
    {
        $sortColumns = [
            'name' => 'miners.name',
            'corporation' => Corporation::select('name')
                ->whereColumn('corporations.corporation_id', 'miners.corporation_id')
                ->limit(1),
            'amount_owed' => 'miners.amount_owed',
            'total_payments' => Payment::selectRaw('COALESCE(SUM(amount_received), 0)')
                ->whereColumn('payments.miner_id', 'miners.eve_id'),
            'latest_mining_activity' => MiningActivity::selectRaw('MAX(created_at)')
                ->whereColumn('mining_activities.miner_id', 'miners.eve_id'),
            'latest_invoice' => Invoice::selectRaw('MAX(updated_at)')
                ->whereColumn('invoices.miner_id', 'miners.eve_id'),
            'latest_payment' => Payment::selectRaw('MAX(updated_at)')
                ->whereColumn('payments.miner_id', 'miners.eve_id'),
        ];

        $sort = $request->query('sort', 'name');
        $sort = is_string($sort) && array_key_exists($sort, $sortColumns) ? $sort : 'name';
        $direction = $request->query('direction') === 'desc' ? 'desc' : 'asc';

        $miners = Miner::with('corporation')
            ->orderBy($sortColumns[$sort], $direction)
            ->when($sort !== 'name', fn ($query) => $query->orderBy('miners.name'))
            ->orderBy('miners.eve_id')
            ->paginate(250)
            ->withQueryString();

        return view('miners.all', [
            'miners' => $miners,
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    /**
     * Show a detailed history of a specific miner.
     */
    public function showMinerDetails($id = NULL)
    {

        // If no user id supplied, redirect to the miners list.
        if ($id == NULL) {
            return redirect('/miners');
        }

        // Retrieve all history of the miner's mining, invoices and payments.
        $mining_activities = MiningActivity::where('miner_id', $id)->get();
        $invoices = Invoice::where('miner_id', $id)->get();
        $payments = Payment::where('miner_id', $id)->get();

        // Loop through each collection and add them to a master array.
        $activity_log = [];
        foreach ($mining_activities as $mining_activity) {
            $activity_log[] = $mining_activity;
        }
        foreach ($invoices as $invoice) {
            $activity_log[] = $invoice;
        }
        foreach ($payments as $payment) {
            $activity_log[] = $payment;
        }

        // Sort the log into reverse chronological order.
        usort($activity_log, [$this, "sortByDate"]);

        return view('miners.single', [
            'miner' => Miner::where('eve_id', $id)->first(),
            'activity_log' => $activity_log,
        ]);

    }
}
