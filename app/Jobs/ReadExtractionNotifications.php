<?php

namespace App\Jobs;

use App\Classes\EsiConnection;
use App\Models\Extraction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ReadExtractionNotifications implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 10;

    /**
     * @var int
     */
    private $userId;

    public function __construct(int $userId)
    {
        $this->userId = $userId;
    }

    /**
     * @throws \Throwable
     */
    public function handle(): void
    {
        $esi = new EsiConnection;

        Log::info('ReadExtractionNotifications: reading notifications.');

        // Read notifications
        $esiConnection = $esi->getConnection($this->userId);
        try {
            $notifications = $esiConnection->invoke(
                'get',
                '/characters/{character_id}/notifications/',
                ['character_id' => $this->userId]
            );
        } catch (\Throwable $e) {
            if (strpos($e->getMessage(), 'esi-characters.read_notifications.v1') !== false) {
                // token is not valid for scope
                Log::warning('ReadExtractionNotifications: exception thrown', [
                    'message' => $e->getMessage()
                ]);
                return;
            }
            throw $e;
        }

        foreach ($notifications as $notification) {
            if (
                $notification->type !== 'MoonminingAutomaticFracture' &&
                $notification->type !== 'MoonminingLaserFired'
            ) {
                continue;
            }

            $moonId = null;
            $refineryId = null;
            $ores = [];
            $nextIsOre = false;
            foreach (explode("\n", $notification->text) as $row) {
                $firstColonPosition = strpos($row, ':');
                $key = substr($row, 0, $firstColonPosition);
                $value = substr($row, $firstColonPosition + 2);
                if ($key === 'moonID') {
                    $moonId = (int)$value;
                }
                if ($key === 'structureID') {
                    $refineryId = (int)$value;
                }
                if ($nextIsOre && substr($key, 0, 2) === '  ') {
                    $ores[] = [
                        (int)trim($key),
                        (int)round((float)$value)
                    ];
                } else {
                    $nextIsOre = false;
                }
                if ($key === 'oreVolumeByType') {
                    $nextIsOre = true;
                }
            }
            if ($moonId === null || $refineryId === null || empty($ores)) {
                continue;
            }

            $timestamp = str_replace(['T', 'Z'], [' ', ''], $notification->timestamp);

            // check if this extraction already exists
            $check = Extraction::where('moon_id', $moonId)
                ->where('refinery_id', $refineryId)
                ->where('notification_timestamp', $timestamp)
                ->first();
            if ($check) {
                continue;
            }

            // add new extraction
            $extraction = new Extraction();
            $extraction->moon_id = $moonId;
            $extraction->refinery_id = $refineryId;
            $extraction->notification_timestamp = $timestamp;
            $extraction->ore1_type_id = $ores[0][0] ?? null;
            $extraction->ore1_volume = $ores[0][1] ?? null;
            $extraction->ore2_type_id = $ores[1][0] ?? null;
            $extraction->ore2_volume = $ores[1][1] ?? null;
            $extraction->ore3_type_id = $ores[2][0] ?? null;
            $extraction->ore3_volume = $ores[2][1] ?? null;
            $extraction->ore4_type_id = $ores[3][0] ?? null;
            $extraction->ore4_volume = $ores[3][1] ?? null;
            $extraction->save();
        }
    }
}
