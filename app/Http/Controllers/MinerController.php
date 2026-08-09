<?php
/** @noinspection PhpUnused */

namespace App\Http\Controllers;

use App\Models\Miner;
use App\Models\Invoice;
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
        $search = trim((string) $request->query('search', ''));

        $miners = Miner::with('corporation')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', '%' . $search . '%');

                    if (ctype_digit($search)) {
                        $query->orWhere('eve_id', $search);
                    }
                });
            })
            ->orderBy('name')
            ->paginate(250)
            ->withQueryString();

        return view('miners.all', [
            'miners' => $miners,
            'search' => $search,
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
