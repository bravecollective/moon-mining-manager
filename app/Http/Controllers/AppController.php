<?php
/** @noinspection PhpUnused */

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Miner;
use App\Models\Payment;
use App\Models\Refinery;
use App\Models\SolarSystem;
use App\Models\User;
use App\Models\Whitelist;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AppController extends Controller
{

    /**
     * App homepage. Check if the user is currently signed in, and either show
     * a signin prompt or the homepage.
     *
     * @return View
     */
    public function home()
    {
        $three_months_ago = Carbon::now()->subMonths(3);

        // Build the WHERE clause to filter by alliance and/or corporation membership.
        $whitelist_where = [];
        $blacklist_where = [];

        if (env('EVE_ALLIANCES_WHITELIST')) {
            $whitelist_where[] = 'alliance_id IN (' . env('EVE_ALLIANCES_WHITELIST') . ')';
            $blacklist_where[] = '(alliance_id NOT IN (' . env('EVE_ALLIANCES_WHITELIST') . ') OR alliance_id IS NULL)';
        }
        if (env('EVE_CORPORATIONS_WHITELIST')) {
            $whitelist_where[] = 'corporation_id IN (' . env('EVE_CORPORATIONS_WHITELIST') . ')';
            $blacklist_where[] = 'corporation_id NOT IN (' . env('EVE_CORPORATIONS_WHITELIST') . ')';
        }

        $whitelist_whereRaw = null;
        $blacklist_whereRaw = null;

        if (count($whitelist_where)) {
            $whitelist_whereRaw = '(' . implode(' OR ', $whitelist_where) . ')';
            $blacklist_whereRaw = '(' . implode(' AND ', $blacklist_where) . ')';
        }

        // Calculate the total currently owed and total income generated.
        $total_amount_owed = null;
        if ($whitelist_whereRaw) {
            $total_amount_owed = DB::table('miners')->select(DB::raw('SUM(amount_owed) AS total'))
                ->where('amount_owed', '>', 0)->whereRaw($whitelist_whereRaw)->first();
        }
        $total_income = DB::table('payments')->select(DB::raw('SUM(amount_received) AS total'))->first();

        // Grab the top miner, refinery and system.
        /* @var Payment $top_payer */
        $top_payer = Payment::select(DB::raw('miner_id, SUM(amount_received) AS total'))
            ->groupBy('miner_id')->orderBy('total', 'desc')->first();

        if (isset($top_payer)) {
            $top_miner = Miner::where('eve_id', $top_payer->miner_id)->first();
            /** @noinspection PhpUndefinedFieldInspection */
            $top_miner->total = $top_payer->total;
        }

        $top_refinery = Refinery::orderBy('income', 'desc')->where('available', 1)->where('updated_at', '>=', $three_months_ago)->first();

        /* @var Refinery $top_refinery_system */
        $top_refinery_system = Refinery::select(DB::raw('solar_system_id, SUM(income) AS total'))
            ->where('available', 1)->groupBy('solar_system_id')->orderBy('total', 'desc')->first();

        if (isset($top_refinery_system) && $top_refinery_system->solar_system_id > 0) {
            /* @var SolarSystem $top_system */
            $top_system = SolarSystem::find($top_refinery_system->solar_system_id);
            /** @noinspection PhpUndefinedFieldInspection */
            $top_system->total = $top_refinery_system->total;
        }

        return view('home', [
            'top_miner' => (isset($top_miner)) ? $top_miner : null,
            'top_refinery' => (isset($top_refinery)) ? $top_refinery : null,
            'top_system' => (isset($top_system)) ? $top_system : null,
            'miners' => Miner::where('amount_owed', '>=', 1)->whereRaw($whitelist_whereRaw)
                ->orderBy('amount_owed', 'desc')->get(),
            'ninjas' => $blacklist_whereRaw ? Miner::whereRaw($blacklist_whereRaw)->limit(100)->get() : [],
            'total_amount_owed' => $total_amount_owed ? $total_amount_owed->total : 0,
            'refineries' => Refinery::orderBy('income', 'desc')->where('available', 1)->where('updated_at', '>=', $three_months_ago)->get(),
            'total_income' => $total_income->total,
        ]);
    }

    /**
     * Access management user list. List all the current whitelisted users, together
     * with the person that authorised them.
     *
     * @return View
     */
    public function showAuthorisedUsers()
    {

        return view('settings', [
            'admin_users' => Whitelist::where('is_admin', TRUE)->get(),
            'whitelisted_users' => Whitelist::where('is_admin', FALSE)->get(),
            'access_history' => User::whereNotIn('eve_id', function ($q) {
                $q->select('eve_id')->from('whitelist');
            })->get(),
        ]);

    }

    /**
     * Whitelist a new user.
     */
    public function makeUserAdmin($id = NULL)
    {
        if ($id == NULL) {
            return redirect('/access');
        }
        $user = Auth::user();
        // Check if this user is already in the whitelist table.
        $whitelist = Whitelist::where('eve_id', $id)->first();
        if (!isset($whitelist)) {
            $whitelist = new Whitelist;
            $whitelist->eve_id = $id;
        }
        $whitelist->is_admin = TRUE;
        $whitelist->added_by = $user->eve_id;
        $whitelist->save();
        return redirect('/access');
    }

    /**
     * Whitelist a new user.
     */
    public function whitelistUser($id = NULL)
    {
        if ($id == NULL) {
            return redirect('/access');
        }
        $user = Auth::user();
        $whitelist = new Whitelist;
        $whitelist->eve_id = $id;
        $whitelist->added_by = $user->eve_id;
        $whitelist->save();
        return redirect('/access');
    }

    /**
     * Blacklist a new user. (Well, it's not really a blacklist, just de-whitelist them.)
     *
     * @throws \Exception
     */
    public function blacklistUser($id = NULL)
    {
        if ($id == NULL) {
            return redirect('/access');
        }
        $user = Whitelist::where('eve_id', $id);
        $user->delete();
        return redirect('/access');
    }

    public function toggleFormMail($id)
    {
        $user = Whitelist::where('eve_id', $id)->first(); /* @var Whitelist $user */
        if ($user) {
            $user->form_mail = !$user->form_mail;
            $user->save();
        }

        return redirect('/access');
    }

    /**
     * Logout the currently authenticated user.
     *
     * @return RedirectResponse
     */
    public function logout()
    {
        Auth::logout();
        return redirect('/');
    }

}
