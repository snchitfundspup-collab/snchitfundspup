<?php

use App\Models\ChitGroup;
use App\Models\ChitGroupMember;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->travelTo(now()->setDate(2026, 10, 5)->setTime(10, 0));

    $this->actingAs(User::factory()->create());

    /* started 15 Sep, ₹5,000 a month → month 2 is due 15 Oct */
    $this->sepGroup = ChitGroup::factory()->running()->create(['name' => 'Sep Group', 'start_date' => '2026-09-15', 'installment_amount' => 5000]);

    /* started 15 Jan, ₹1,000 a month → months 1–9 past due on 5 Oct */
    $this->janGroup = ChitGroup::factory()->running()->create(['name' => 'Jan Group', 'start_date' => '2026-01-15', 'installment_amount' => 1000]);

    $seat = function (ChitGroup $group, string $name, array $payments) {
        $member = ChitGroupMember::factory()->create([
            'chit_group_id' => $group->id,
            'customer_id' => Customer::factory()->create(['name' => $name, 'phone' => '98'.random_int(10000000, 99999999)])->id,
        ]);

        foreach ($payments as $month => $amount) {
            Payment::record(ChitGroupMember::with('chitGroup', 'allocations')->find($member->id), [
                'amount' => $amount, 'month_number' => $month, 'paid_at' => '2026-09-20 10:00', 'method' => 'cash',
            ]);
        }

        return $member;
    };

    $seat($this->sepGroup, 'Ravi Pending', []);
    $seat($this->sepGroup, 'Sita Due', [1 => 5000]);
    $seat($this->sepGroup, 'Tara Part', [1 => 5000, 2 => 1500]);
    $seat($this->sepGroup, 'Uma Ahead', [1 => 5000, 2 => 5000]);
    $seat($this->janGroup, 'Kumar Behind', []);
});

test('guests cannot open the pending and due report', function () {
    auth()->logout();

    $this->get(route('reports.dues'))->assertRedirect(route('login'));
});

test('the report lists pending (longest overdue first) and due members with totals', function () {
    $response = $this->get(route('reports.dues'))->assertOk();

    $pending = $response->viewData('pending');
    $due = $response->viewData('due');

    expect($pending->pluck('name')->all())->toBe(['Kumar Behind', 'Ravi Pending'])
        ->and($pending[0]['months'])->toBe([1, 2, 3, 4, 5, 6, 7, 8, 9])
        ->and($pending[0]['amount'])->toBe(9000)
        ->and($pending[0]['days_overdue'])->toBe(263)
        ->and($pending[1]['amount'])->toBe(5000)
        ->and($pending[1]['days_overdue'])->toBe(20)
        ->and($due->pluck('name')->all())->toBe(['Sita Due', 'Tara Part'])
        ->and($due[1]['amount'])->toBe(3500)
        ->and($due[1]['part_paid'])->toBe(1500);

    $response
        ->assertViewHas('byGroup', fn ($groups) => $groups->pluck('name')->all() === ['Jan Group', 'Sep Group']
            && $groups[1]['pending_amount'] === 5000 && $groups[1]['due_amount'] === 8500)
        ->assertSeeTextInOrder(['Pending', '₹14,000', 'Due', '₹8,500', 'Past the due date', 'Kumar Behind', 'Ravi Pending', 'Due this month', 'Sita Due', 'Tara Part'])
        ->assertDontSee('Uma Ahead');
});

test('the report can show one list and one group', function () {
    $this->get(route('reports.dues', ['type' => 'due']))
        ->assertOk()
        ->assertDontSee('Kumar Behind')
        ->assertSee('Sita Due');

    $this->get(route('reports.dues', ['type' => 'pending', 'group' => $this->sepGroup->id]))
        ->assertOk()
        ->assertViewHas('pending', fn ($rows) => $rows->pluck('name')->all() === ['Ravi Pending'])
        ->assertDontSee('Sita Due');
});

test('each member row links to their payment form and can call them', function () {
    $ravi = ChitGroupMember::whereHas('customer', fn ($query) => $query->where('name', 'Ravi Pending'))->with('customer')->sole();

    $this->get(route('reports.dues'))
        ->assertSee(route('payments.create', ['customer' => $ravi->customer_id, 'member' => $ravi->id]).'#collect')
        ->assertSee('href="tel:'.$ravi->customer->phone.'"', false);
});

test('the report prints and downloads as a pdf', function () {
    $this->get(route('reports.dues'))
        ->assertSee(route('reports.dues.print'))
        ->assertSee(route('reports.dues.pdf'));

    $this->get(route('reports.dues.print', ['group' => $this->sepGroup->id]))
        ->assertOk()
        ->assertSeeTextInOrder(['Mon, 05 Oct 2026', 'Sep Group', 'Pending — past the due date', 'Ravi Pending', 'Total (1 member)', 'Due — due this month', 'Sita Due', 'Tara Part'])
        ->assertSee('window.print()', false);

    $response = $this->get(route('reports.dues.pdf'))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');

    expect($response->headers->get('content-disposition'))->toContain('Pending-and-Due-2026-10-05.pdf');
});

test('the reports menu links to the pending and due report', function () {
    $this->get(route('dashboard'))->assertSee(route('reports.dues'));
});
