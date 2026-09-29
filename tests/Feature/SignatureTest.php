<?php

use App\Models\ChitGroup;
use App\Models\ChitGroupMember;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\RiceVariety;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * A chit receipt recorded by the given staff member.
 */
function receiptRecordedBy(User $user): Payment
{
    $group = ChitGroup::factory()->running()->create(['start_date' => '2026-09-15', 'installment_amount' => 5000]);
    $member = ChitGroupMember::factory()->create(['chit_group_id' => $group->id, 'customer_id' => Customer::factory()]);

    return Payment::record(
        ChitGroupMember::with('chitGroup', 'allocations')->find($member->id),
        ['amount' => 5000, 'month_number' => 1, 'paid_at' => '2026-09-20 10:00', 'method' => 'cash'],
        $user,
    );
}

beforeEach(function () {
    $this->travelTo(now()->setDate(2026, 9, 25)->setTime(10, 0));
});

test('the signature of whoever recorded a receipt appears on it', function () {
    $sathiya = User::factory()->create(['name' => 'Sathiya', 'username' => 'sathiya']);
    $narayanan = User::factory()->create(['name' => 'Narayanan', 'username' => 'narayanan']);

    $this->actingAs($narayanan);

    $this->get(route('payments.show', receiptRecordedBy($sathiya)))
        ->assertOk()
        ->assertSee('alt="Signature of Sathiya"', false)
        ->assertSee('src="data:image/png;base64,', false)
        ->assertDontSee('alt="Signature of Narayanan"', false);

    $this->get(route('payments.receipt.pdf', receiptRecordedBy($narayanan)))->assertOk();
});

test('someone without a signature on file leaves the line blank', function () {
    $staff = User::factory()->create(['name' => 'New Staff', 'username' => 'newstaff']);

    $this->actingAs($staff)
        ->get(route('payments.show', receiptRecordedBy($staff)))
        ->assertOk()
        ->assertSeeText('Authorised signature')
        ->assertDontSee('class="signature-img"', false);
});

test('traders invoices carry the signature of whoever made the sale', function () {
    $narayanan = User::factory()->create(['name' => 'Narayanan', 'username' => 'narayanan']);
    $ponni = RiceVariety::factory()->create(['name' => 'Ponni', 'bag_kg' => 26]);

    $sale = Sale::record(['customer_id' => Customer::factory()->create()->id, 'sold_on' => '2026-09-25'], [
        ['variety_id' => $ponni->id, 'bags' => 2, 'bag_kg' => 26, 'loose_kg' => 0, 'rate' => 1450, 'rate_per' => 'bag'],
    ], null, $narayanan);

    $this->actingAs($narayanan)
        ->get(route('traders.sales.show', $sale))
        ->assertOk()
        ->assertSee('alt="Signature of Narayanan"', false);

    $this->actingAs($narayanan)->get(route('traders.sales.pdf', $sale))->assertOk();
});

test('signature images are not in the public folder', function () {
    expect(glob(public_path('**/*signature*')))->toBe([])
        ->and(is_file(resource_path('signatures/narayanan.png')))->toBeTrue()
        ->and(is_file(resource_path('signatures/sathiya.png')))->toBeTrue();
});
