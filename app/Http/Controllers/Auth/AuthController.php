<?php

namespace App\Http\Controllers\Auth;

use App\Classes\EsiConnection;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Whitelist;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Socialite;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{

    private $socialite_driver = 'eve-sso';

    public function __construct()
    {
    }

    /**
     * Redirect the user to the EVE Online SSO page.
     *
     * @return Response;
     */
    public function redirectToProvider()
    {
        return Socialite::driver($this->socialite_driver)->redirect();
    }

    /**
     * Redirect the user to the EVE Online SSO page and ask for necessary permissions/scopes.
     *
     * @return Response
     */
    public function redirectToProviderForAdmin()
    {
        return Socialite::driver($this->socialite_driver)->scopes([
            'esi-industry.read_corporation_mining.v1',
            'esi-wallet.read_corporation_wallets.v1',
            'esi-mail.send_mail.v1',
            'esi-universe.read_structures.v1',
            'esi-corporations.read_structures.v1',
            'esi-characters.read_notifications.v1',
        ])->redirect();
    }

    /**
     * Obtain the user information from EVE Online.
     *
     * @return Response
     * @throws \Exception
     */
    public function handleProviderCallback()
    {
        // Find or create the user.
        $user = Socialite::driver($this->socialite_driver)->user();
        $authUser = $this->findOrCreateUser($user);
        Log::info('AuthController: login attempt by ' . $authUser->name);

        $esi = new EsiConnection;
        $conn = $esi->getConnection();

        // Check if the user is a member of the correct alliance.
        $req = $conn->setBody([
                intval($authUser->eve_id)
            ])->invoke('post', '/characters/affiliation/');
        $character = current($req->getArrayCopy());

        // If this is a new login, save the corporation ID.
        if (!isset($authUser->corporation_id)) {
            $authUser->corporation_id = $character->corporation_id;
            $authUser->save();
        }

        $corporation = $conn->invoke('get', '/corporations/{corporation_id}/', [
            'corporation_id' => $character->corporation_id,
        ]);

        // If an alliance is set, it must match the stored environment variable.
        $allowedAlliances = explode(',', env('EVE_ALLIANCES_LOGIN'));
        $allowedCorporations = explode(',', env('EVE_CORPORATIONS_LOGIN'));
        if (($character->corporation_id > 0 && in_array($character->corporation_id, $allowedCorporations))
            ||
            (
                isset($corporation->alliance_id) && $corporation->alliance_id > 0
                && in_array($corporation->alliance_id, $allowedAlliances)
            )
        ) {
            Auth::login($authUser, true);
            Log::info('AuthController: successful login by ' . $authUser->name);
        } else {
            Log::info('AuthController: unsuccessful login by ' . $authUser->name . ', alliance/corp match failed');
            return redirect()->route('login');
        }

        // Check if the user is whitelisted to access the administrator area.
        $whitelist = Whitelist::where('eve_id', $authUser->eve_id)->first();
        if (isset($whitelist)) {
            Log::info('AuthController: successful administrator login by ' . $authUser->name);
            return redirect('/');
        } else {
            return redirect('/timers');
        }
    }

    /**
     * Return user if exists; create and return if doesn't.
     *
     * @param $user
     * @return User
     */
    private function findOrCreateUser($user)
    {
        if ($authUser = User::where('eve_id', $user->id)->first()) {

            // Set tokens, but only if there are scopes. This ensures that an admin token is
            // not overwritten if the user logs in again using the normal login.
            if ($user->refreshToken !== null && !empty($user->user['Scopes'])) {
                $authUser->token = $user->token;
                $authUser->refresh_token = $user->refreshToken;
            }

            $authUser->save();
            return $authUser;
        }

        return User::create([
            'eve_id' => $user->id,
            'corporation_id' => NULL,
            'name' => $user->name,
            'avatar' => $user->avatar,
            'token' => $user->token,
            'refresh_token' => $user->refreshToken,
        ]);
    }

}
