<?php

use App\Models\ChitGroup;
use App\Models\ChitGroupMember;
use App\Models\Customer;
use App\Models\Draw;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->travelTo(now()->setDate(2026, 3, 20)->setTime(18, 0));

    $this->actingAs(User::factory()->create());

    /* started 15 Jan 2026 → months 1–3 can be drawn on 20 Mar 2026 */
    $this->group = ChitGroup::factory()->running()->withPayouts(150000, 5000)->create([
        'name' => 'Draw Group',
        'start_date' => '2026-01-15',
        'months' => 4,
        'member_count' => 4,
    ]);

    $this->members = collect(['Anand', 'Bharathi', 'Chitra', 'Devi'])->map(
        fn (string $name, int $index) => ChitGroupMember::factory()->create([
            'chit_group_id' => $this->group->id,
            'customer_id' => Customer::factory()->create(['name' => $name])->id,
            'position' => $index + 1,
        ])
    );
});

/**
 * Run a draw through the endpoint the wheel uses.
 *
 * @param  array<int, int>  $participantIds
 */
function runDraw(ChitGroup $group, array $participantIds): TestResponse
{
    return test()->postJson(route('draws.store'), [
        'chit_group_id' => $group->id,
        'participant_ids' => $participantIds,
    ]);
}

test('guests cannot open the draw pages', function () {
    auth()->logout();

    $this->get(route('draws.index'))->assertRedirect(route('login'));
    $this->get(route('draws.create'))->assertRedirect(route('login'));
});

test('run draw lists the members yet to win with the prize for the month', function () {
    $this->get(route('draws.create', ['group' => $this->group->id]))
        ->assertOk()
        ->assertViewHas('month', 1)
        ->assertViewHas('canDraw', true)
        ->assertViewHas('withdrawal', 150000)
        ->assertViewHas('eligible', fn ($eligible) => $eligible->count() === 4)
        ->assertSee('Anand')
        ->assertSee('Spin the wheel');
});

test('the winner is picked from the chosen members and returned for the wheel', function () {
    $chosen = $this->members->take(2)->pluck('id')->all();

    $response = runDraw($this->group, $chosen)->assertOk();

    $draw = Draw::sole();

    expect($chosen)->toContain($draw->winner_member_id)
        ->and($draw->month_number)->toBe(1)
        ->and($draw->withdrawal_amount)->toBe(150000)
        ->and($draw->participants->pluck('id')->sort()->values()->all())->toBe($chosen)
        ->and($draw->drawn_at->format('Y-m-d H:i'))->toBe('2026-03-20 23:30');

    $response->assertJson([
        'winner_id' => $draw->winner_member_id,
        'winner_code' => $draw->winner->member_code,
        'url' => route('draws.show', $draw),
    ]);
});

test('a winner is not offered in the next draw', function () {
    $winner = $this->members->first();

    runDraw($this->group, [$winner->id])->assertOk();
    runDraw($this->group, [$this->members[1]->id])->assertOk();

    $this->get(route('draws.create', ['group' => $this->group->id]))
        ->assertViewHas('month', 3)
        ->assertViewHas('lastDraw', fn (Draw $draw) => $draw->month_number === 2)
        ->assertViewHas('eligible', fn ($eligible) => $eligible->count() === 2 && ! $eligible->contains('id', $winner->id));

    runDraw($this->group, [$winner->id])->assertUnprocessable()->assertJsonValidationErrors('participant_ids');

    expect(Draw::count())->toBe(2);
});

test('members of another group cannot enter the draw', function () {
    $outsider = ChitGroupMember::factory()->create();

    runDraw($this->group, [$outsider->id])->assertJsonValidationErrors('participant_ids');

    expect(Draw::count())->toBe(0);
});

test('at least one member must be chosen', function () {
    runDraw($this->group, [])->assertJsonValidationErrors('participant_ids');
});

test('a month cannot be drawn before it begins', function () {
    foreach ([1, 2, 3] as $month) {
        runDraw($this->group, [$this->members[$month - 1]->id])->assertOk();
    }

    /* month 4 begins on 15 Apr */
    runDraw($this->group, [$this->members[3]->id])->assertJsonValidationErrors('chit_group_id');

    $this->get(route('draws.create', ['group' => $this->group->id]))
        ->assertViewHas('canDraw', false)
        ->assertViewHas('month', 4);

    $this->travelTo(now()->setDate(2026, 4, 15)->setTime(18, 0));

    runDraw($this->group, [$this->members[3]->id])->assertOk();

    $this->get(route('draws.create', ['group' => $this->group->id]))
        ->assertViewHas('month', null)
        ->assertSee('Every month of this group has been drawn.');
});

test('a group that has not started cannot hold a draw', function () {
    $forming = ChitGroup::factory()->withMembers(2)->create();

    runDraw($forming, $forming->members->pluck('id')->all())->assertJsonValidationErrors('chit_group_id');
});

test('the draw page shows the result and a payout form', function () {
    runDraw($this->group, $this->members->pluck('id')->all());

    $draw = Draw::sole();

    $this->get(route('draws.show', $draw))
        ->assertOk()
        ->assertSee($draw->winner->customer->name)
        ->assertSee('Record payout')
        ->assertSee('1,50,000');
});

test('the payout is acknowledged with a voucher number', function () {
    runDraw($this->group, $this->members->pluck('id')->all());

    $draw = Draw::sole();

    $this->post(route('draws.payout', $draw), [
        'payout_amount' => '1,50,000',
        'payout_method' => 'cash',
        'paid_at' => '2026-03-20T17:45',
        'payout_notes' => 'Handed over at the office',
    ])->assertRedirect(route('draws.show', $draw));

    $draw->refresh();

    expect($draw->isPaidOut())->toBeTrue()
        ->and($draw->payout_amount)->toBe(150000)
        ->and($draw->payout_method)->toBe('cash')
        ->and($draw->voucher_number)->toBe('PV'.str_pad((string) $draw->id, 6, '0', STR_PAD_LEFT))
        ->and($draw->paid_by)->toBe(auth()->id());

    $this->get(route('draws.show', $draw))
        ->assertSee('Prize money handed over')
        ->assertSee($draw->voucher_number)
        ->assertDontSee('Confirm paid');
});

test('a payout needs a valid amount, method and a date that is not in the future', function () {
    runDraw($this->group, $this->members->pluck('id')->all());

    $draw = Draw::sole();

    $this->post(route('draws.payout', $draw), [
        'payout_amount' => '0',
        'payout_method' => 'gold',
        'paid_at' => '2026-03-22T10:00',
    ])->assertSessionHasErrors(['payout_amount', 'payout_method', 'paid_at']);

    expect($draw->refresh()->isPaidOut())->toBeFalse();
});

test('a prize cannot be paid out twice', function () {
    runDraw($this->group, $this->members->pluck('id')->all());

    $draw = Draw::sole();
    $payout = ['payout_amount' => 150000, 'payout_method' => 'cash', 'paid_at' => '2026-03-20 17:00'];

    $this->post(route('draws.payout', $draw), $payout);
    $this->post(route('draws.payout', $draw), [...$payout, 'payout_amount' => 1])
        ->assertSessionHas('error');

    expect($draw->refresh()->payout_amount)->toBe(150000);
});

test('the payout voucher downloads as a pdf once paid', function () {
    runDraw($this->group, $this->members->pluck('id')->all());

    $draw = Draw::sole();

    $this->get(route('draws.voucher.pdf', $draw))->assertNotFound();

    $this->post(route('draws.payout', $draw), ['payout_amount' => 150000, 'payout_method' => 'upi', 'payout_reference' => 'UPI123', 'paid_at' => '2026-03-20 17:00']);

    $draw->refresh();

    $response = $this->get(route('draws.voucher.pdf', $draw))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');

    expect($response->headers->get('content-disposition'))->toContain("Voucher-{$draw->voucher_number}.pdf")
        ->and(substr($response->getContent(), 0, 4))->toBe('%PDF');
});

test('an unpaid draw can be cancelled and run again', function () {
    runDraw($this->group, $this->members->pluck('id')->all());

    $draw = Draw::sole();

    $this->delete(route('draws.destroy', $draw))
        ->assertRedirect(route('draws.create', ['group' => $this->group->id]));

    expect(Draw::count())->toBe(0);

    runDraw($this->group, $this->members->pluck('id')->all())->assertOk();

    expect(Draw::sole()->month_number)->toBe(1);
});

test('a paid out draw cannot be cancelled', function () {
    runDraw($this->group, $this->members->pluck('id')->all());

    $draw = Draw::sole();

    $this->post(route('draws.payout', $draw), ['payout_amount' => 150000, 'payout_method' => 'cash', 'paid_at' => '2026-03-20 17:00']);

    $this->delete(route('draws.destroy', $draw))->assertSessionHas('error');

    expect(Draw::count())->toBe(1);
});

test('all draws can be filtered by payout status and searched', function () {
    runDraw($this->group, [$this->members[0]->id]);
    runDraw($this->group, [$this->members[1]->id]);

    $paid = Draw::where('winner_member_id', $this->members[0]->id)->sole();

    $this->post(route('draws.payout', $paid), ['payout_amount' => 150000, 'payout_method' => 'cash', 'paid_at' => '2026-03-20 17:00']);

    $this->get(route('draws.index', ['status' => 'pending']))
        ->assertOk()
        ->assertViewHas('draws', fn ($draws) => $draws->pluck('winner_member_id')->all() === [$this->members[1]->id])
        ->assertViewHas('pendingCount', 1)
        ->assertViewHas('pendingAmount', 155000);

    $this->get(route('draws.index', ['status' => 'paid']))
        ->assertViewHas('draws', fn ($draws) => $draws->pluck('id')->all() === [$paid->id]);

    $this->get(route('draws.index', ['q' => 'Bharathi']))
        ->assertViewHas('draws', fn ($draws) => $draws->pluck('winner_member_id')->all() === [$this->members[1]->id]);
});

test('the prize is shown in the payment ledger and on the member card', function () {
    runDraw($this->group, [$this->members[1]->id]);

    $draw = Draw::sole();

    $this->get(route('payments.ledger', ['group' => $this->group->id]))
        ->assertOk()
        ->assertViewHas('totalPrizes', 150000)
        ->assertViewHas('rows', fn ($rows) => $rows[1]['won']?->is($draw) && $rows[0]['won'] === null)
        ->assertSee('Prize won')
        ->assertSee('Awaiting payout');

    $this->get(route('groups.show', $this->group))
        ->assertSee('Won</span> · M1', false);

    $this->get(route('payments.ledger.pdf', ['group' => $this->group->id]))->assertOk();
});

test('a member who took part in a draw cannot be removed from the group', function () {
    runDraw($this->group, [$this->members[2]->id]);

    $this->delete(route('groups.members.destroy', [$this->group, $this->members[2]]));

    expect(ChitGroupMember::whereKey($this->members[2]->id)->exists())->toBeTrue();
});
