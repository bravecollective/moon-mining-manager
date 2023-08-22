<?php

namespace App\Classes;

use Ixudra\Curl\Facades\Curl;
use Seat\Eseye\Configuration;
use Seat\Eseye\Containers\EsiAuthentication;
use Seat\Eseye\Eseye;
use App\Models\User;

/**
 * Generic class for use by controllers or queued jobs that need to request information
 * from the ESI API.
 */
class EsiConnection
{
    /**
     * Eseye objects for performing all ESI requests
     *
     * @var Eseye[]
     */
    private $connections = [];

    /**
     * @param null|int $userId
     * @return Eseye
     * @throws \Exception
     */
    public function getConnection($userId = null)
    {
        $userId = $userId === null ? 0 : (int) $userId;

        if (! isset($this->connections[$userId])) {
            $this->connections[$userId] = $this->createConnection($userId);
        }

        return $this->connections[$userId];
    }

    /**
     * Returns the EVE corporation ID of an user.
     *
     * @param int $userId
     * @return int
     * @throws \Exception
     */
    public function getCorporationId($userId)
    {
        // Retrieve the user's character details.
        $character = $this->getConnection()->invoke('get', '/characters/{character_id}/', [
            'character_id' => $userId,
        ]);

        return $character->corporation_id;
    }

    /**
     * Returns the configured "prime" user for a corporation from the configuration.
     *
     * @param int $corporationId RENT_CORPORATION_ID or TAX_CORPORATION_ID from the configuration
     * @return int|null
     */
    public function getPrimeUserOfCorporation($corporationId)
    {
        if ($corporationId == env('RENT_CORPORATION_ID')) {
            return env('RENT_CORPORATION_PRIME_USER_ID');
        }

        if ($corporationId == env('TAX_CORPORATION_ID')) {
            return env('TAX_CORPORATION_PRIME_USER_ID');
        }

        return null;
    }

    /**
     * Create an ESI API object with or without access token to handle all requests.
     *
     * @param int $userId
     * @return Eseye
     * @throws \Exception
     */
    private function createConnection($userId = 0)
    {
        // Eseye configuration for all connections
        $configuration = Configuration::getInstance();
        /** @noinspection PhpUndefinedFieldInspection */
        $configuration->http_client = 'GuzzleHttp\Client';
        $configuration->datasource = 'tranquility';
        /** @noinspection PhpUndefinedFieldInspection */
        $configuration->logfile_location = storage_path() . '/logs';
        /** @noinspection PhpUndefinedFieldInspection */
        $configuration->file_cache_location = storage_path() . '/framework/cache';

        $authentication = null;
        if ($userId > 0) {
            // Create authentication with app details and refresh token from nominated prime user.
            $user = User::where('eve_id', $userId)->first();
            if ($user === null) {
                throw new \Exception('User '. $userId .' not found.');
            }

            $url = 'https://login.eveonline.com/v2/oauth/token';
            $secret = env('EVEONLINE_CLIENT_SECRET');
            $client_id = env('EVEONLINE_CLIENT_ID');

            // Need to request a new valid access token from EVE SSO using the refresh token of the original request.
            $response = Curl::to($url)
                ->withData(array(
                    'grant_type' => "refresh_token",
                    'refresh_token' => $user->refresh_token
                ))
                ->withHeaders(array(
                    'Authorization: Basic ' . base64_encode($client_id . ':' . $secret)
                ))
                //->enableDebug('logFile.txt')
                ->post();
            $new_token = json_decode($response);
            if (isset($new_token->refresh_token)) {
                $user->refresh_token = $new_token->refresh_token;
                $user->save();
            }

            $authentication = new EsiAuthentication([
                'secret'        => $secret,
                'client_id'     => $client_id,
                'access_token'  => isset($new_token->access_token) ? $new_token->access_token : null,
                'refresh_token' => $user->refresh_token,
                'scopes'        => [
                    'esi-industry.read_corporation_mining.v1',
                    'esi-wallet.read_corporation_wallets.v1',
                    'esi-mail.send_mail.v1',
                    'esi-universe.read_structures.v1',
                    'esi-corporations.read_structures.v1',
                    'esi-characters.read_notifications.v1',
                ],
                'token_expires' => isset($new_token->expires_in) ?
                    date('Y-m-d H:i:s', time() + $new_token->expires_in) :
                    null,
            ]);
        }

        // Create ESI API object.
        return new Eseye($authentication);
    }
}
