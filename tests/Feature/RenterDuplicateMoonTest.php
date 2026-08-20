<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Renter;
use App\Models\User;
use App\Models\Whitelist;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RenterDuplicateMoonTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(): User
    {
        $user = User::factory()->create();

        $whitelist = new Whitelist;
        $whitelist->eve_id   = $user->eve_id;
        $whitelist->is_admin = true;
        $whitelist->form_mail = false;
        $whitelist->added_by = $user->eve_id;
        $whitelist->save();

        return $user;
    }

    private function basePayload(int $moonId): array
    {
        return [
            'type'               => Renter::TYPE_ACTIVE,
            'character_id'       => 12345,
            'moon_id'            => $moonId,
            'monthly_rental_fee' => 50000000,
            'start_date'         => now()->toDateString(),
        ];
    }

    /** @test */
    public function creating_a_renter_with_an_already_active_moon_is_rejected(): void
    {
        Renter::forceCreate([
            'type'               => Renter::TYPE_ACTIVE,
            'character_id'       => 99999,
            'moon_id'            => 42,
            'monthly_rental_fee' => 50000000,
            'start_date'         => now()->toDateString(),
            'amount_owed'        => 0,
        ]);

        $response = $this->actingAs($this->makeUser())
            ->post('/renters/new', $this->basePayload(42));

        $response->assertSessionHasErrors('moon_id');
    }

    /** @test */
    public function updating_a_renter_to_an_already_active_moon_is_rejected(): void
    {
        // Moon 42 is actively rented by another renter
        Renter::forceCreate([
            'type'               => Renter::TYPE_ACTIVE,
            'character_id'       => 99999,
            'moon_id'            => 42,
            'monthly_rental_fee' => 50000000,
            'start_date'         => now()->toDateString(),
            'amount_owed'        => 0,
        ]);

        // Our renter is on moon 55
        $renter = Renter::forceCreate([
            'type'               => Renter::TYPE_ACTIVE,
            'character_id'       => 11111,
            'moon_id'            => 55,
            'monthly_rental_fee' => 50000000,
            'start_date'         => now()->toDateString(),
            'amount_owed'        => 0,
        ]);

        $response = $this->actingAs($this->makeUser())
            ->post('/renters/' . $renter->id, $this->basePayload(42));

        $response->assertSessionHasErrors('moon_id');
    }

    /** @test */
    public function expired_moon_rental_does_not_block_new_assignment(): void
    {
        // Moon 42 was rented but contract has expired
        Renter::forceCreate([
            'type'               => Renter::TYPE_ACTIVE,
            'character_id'       => 99999,
            'moon_id'            => 42,
            'monthly_rental_fee' => 50000000,
            'start_date'         => now()->subDays(60)->toDateString(),
            'end_date'           => now()->subDays(1)->toDateString(),
            'amount_owed'        => 0,
        ]);

        $response = $this->actingAs($this->makeUser())
            ->post('/renters/new', $this->basePayload(42));

        // Should NOT have a moon_id error (may fail for other reasons like ESI, but not duplicate check)
        $response->assertSessionDoesntHaveErrors('moon_id');
    }
}
