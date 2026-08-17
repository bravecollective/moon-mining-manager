<?php
/** @noinspection PhpUnused */

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Miner;
use App\Models\Renter;
use App\Models\Payment;
use App\Models\RentalPayment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    /**
     * @throws \Exception
     */
    public function listManualPayments()
    {
        return view('payment.list', [
            'minerPayments' => Payment::whereNotNull('created_by')->orderByDesc('created_at')->get(),
            'rentalPayments' => RentalPayment::whereNotNull('created_by')->orderByDesc('created_at')->get(),
        ]);
    }

    /**
     * @throws \Exception
     */
    public function addNewPayment()
    {
        return view('payment.new', [
            'miners' => Miner::orderBy('name')->get(),
            'renters' => Renter::orderBy('character_name')->get(),
        ]);
    }

    public function insertNewPayment(Request $request)
    {
        $miner_id = (int) $request->input('miner_id');
        $rental_id = (int) $request->input('rental_id');
        $amount = (int) $request->input('amount');
        $user = Auth::user();

        if (($miner_id === 0 && $rental_id === 0) || $amount === 0) {
            return redirect('/payment/new')
                ->with('message', 'Please choose a miner OR renter and add an amount <> 0.');
        }

        $ctx = array();
        if ($miner_id > 0 && $amount !== 0) {
            // Create a record of the new payment.
            $payment = new Payment;
            $payment->miner_id = $miner_id;
            $payment->amount_received = $amount;
            $payment->created_by = $user->eve_id;
            $payment->save();

            // Deduct it from the current outstanding balance.
            $miner = Miner::where('eve_id', $miner_id)->first();
            $miner->amount_owed -= $amount;
            $miner->save();

            $ctx['type'] = 'tax';
            $ctx['admin_id'] = $user->eve_id;
            $ctx['amount'] = number_format($amount);
            $ctx['char_id'] = $miner_id;
        }
        else if ($rental_id > 0 && $amount !== 0)
        {
            // Grab a reference to the rental record.
            $renter = Renter::find($rental_id);

            // Create a record of the new rental payment.
            $rental_payment = new RentalPayment;
            $rental_payment->renter_id = $renter->character_id;
            $rental_payment->refinery_id = $renter->refinery_id;
            $rental_payment->moon_id = $renter->moon_id;
            $rental_payment->amount_received = $amount;
            $rental_payment->created_by = $user->eve_id;
            $rental_payment->save();

            // Deduct it from the current outstanding balance.
            $renter->amount_owed -= $amount;
            $renter->save();

            // Log the payment.
            $ctx['type'] = 'rental';
            $ctx['admin_id'] = $user->eve_id;
            $ctx['amount'] = number_format($amount);
            $ctx['char_id'] = $renter->character_id;
            $ctx['refinery_id'] = $renter->refinery_id;
            $ctx['moon_id'] = $renter->moon_id;
        }

        // Log the payment.
        Log::info('PaymentController: payment manually submitted', $ctx);

        return redirect('/payment');
    }
}
