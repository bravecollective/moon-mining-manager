<?php

namespace App\Http\Controllers;

use App\Jobs\RegenerateInvoices;
use App\Models\Miner;
use App\Models\MiningActivity;
use App\Models\Payment;
use Illuminate\Support\Facades\Log;

class ReportsController extends Controller
{
    /**
     * Default report view. Show a table of dates with amount mined per day.
     */
    public function main($year = NULL, $month = NULL)
    {

        if (!isset($year)) $year = date('Y');
        if (!isset($month)) $month = date('m');

        // Grab all mining activity and payments in the specified month.
        $mining_activity = MiningActivity::whereRaw(
            'YEAR(created_at) = ' . $year . ' AND MONTH(created_at) = ' . $month
        )->get();
        $payment_activity = Payment::whereRaw('YEAR(created_at) = ' . $year . ' AND MONTH(created_at) = ' . $month)
            ->get();

        // Set the first and last dates of the selected month.
        $first_date = $year . '-' . $month . '-01';
        $last_date = $year . '-' . $month . '-' . date('t', strtotime($first_date));

        // Make an array of the entire date range.
        $date = $first_date;
        $dates = [date('m-d', strtotime($date))];
        while ($date < $last_date) {
            $date = date('Y-m-d', strtotime($date . ' + 1 day'));
            $dates[] = date('m-d', strtotime($date));
        }

        // Loop through all mining activity, and add values to each date.
        $mining = [];
        foreach ($mining_activity as $row) {
            $date = date('m-d', strtotime($row->created_at));
            if (isset($mining[$date])) {
                $mining[$date] += $row->quantity;
            } else {
                $mining[$date] = $row->quantity;
            }
        }

        // Loop through all payments, add values to each date in range.
        $payments = [];
        foreach ($payment_activity as $row) {
            $date = date('m-d', strtotime($row->created_at));
            if (isset($payments[$date])) {
                $payments[$date] += $row->amount_received;
            } else {
                $payments[$date] = $row->amount_received;
            }
        }

        return view('reports.main', [
            'year' => $year,
            'month' => $month,
            'prev_month' => ($month == 1) ? 12 : $month - 1,
            'next_month' => ($month == 12) ? 1 : $month + 1,
            'dates' => $dates,
            'mining' => $mining,
            'payments' => $payments,
        ]);

    }

    /**
     * Manual triggering of the job to regenerate invoices after an error.
     */
    public function fix()
    {

        // Build the WHERE clause to filter by alliance and/or corporation membership.
        $whitelist_where = [];
        if (config('eve.alliances_whitelist')) {
            $whitelist_where[] = 'alliance_id IN (' . config('eve.alliances_whitelist') . ')';
        }
        if (config('eve.corporations_whitelist')) {
            $whitelist_where[] = 'corporation_id IN (' . config('eve.corporations_whitelist') . ')';
        }
        if (count($whitelist_where)) {
            $whitelist_whereRaw = '(' . implode(' OR ', $whitelist_where) . ')';
        }

        // Figure out the date of the last Monday when invoices should have been generated,
        // and find miners that should have been sent an invoice but weren't.
        $last_monday = date('Y-m-d', strtotime('Monday this week'));
        $miners = Miner::select(['eve_id', 'name'])->where('amount_owed', '>=', 1)->whereRaw($whitelist_whereRaw)
            ->whereRaw('eve_id NOT IN (SELECT miner_id FROM invoices WHERE DATE(created_at) = "' . $last_monday . '")')
            ->get();

        return view('reports.fix', [
            'last_monday' => $last_monday,
            'miners' => $miners,
        ]);
    }

    public function regenerate()
    {
        RegenerateInvoices::dispatch();
        Log::info('ReportsController: dispatched job to regenerate invoices');
        return redirect('/reports');
    }

}
