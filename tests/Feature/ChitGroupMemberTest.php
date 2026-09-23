<?php

use App\Models\ChitGroup;
use App\Models\ChitGroupMember;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

test('the edit members page searches active customers only', function () {
    $group = ChitGroup::factory()->create();

    Customer::factory()->create(['name' => 'Kumar Active']);
    Customer::factory()->inactive()->create(['name' => 'Kumar Inactive']);

    $this->get(route('groups.members.edit', [$group, 'q' => 'Kumar']))
        ->assertOk()
        ->assertSee('Kumar Active')
        ->assertDontSee('Kumar Inactive');
});

test('customers already in the group are hidden from the search', function () {
    $group = ChitGroup::factory()->create();
    $otherGroup = ChitGroup::factory()->create();

    $member = Customer::factory()->create(['name' => 'Kumar Member']);
    Customer::factory()->create(['name' => 'Kumar Free']);
    $inOtherGroup = Customer::factory()->create(['name' => 'Kumar Elsewhere']);

    $this->post(route('groups.members.store', $group), ['customer_id' => $member->id]);
    $this->post(route('groups.members.store', $otherGroup), ['customer_id' => $inOtherGroup->id]);

    $this->get(route('groups.members.edit', [$group, 'q' => 'Kumar']))
        ->assertOk()
        ->assertViewHas('results', fn ($results) => $results->pluck('name')->sort()->values()->all() === ['Kumar Elsewhere', 'Kumar Free']);
});

test('a customer can be added to a group using their customer id as member id', function () {
    $group = ChitGroup::factory()->create();
    $customer = Customer::factory()->create(['customer_code' => 'SN2612', 'name' => 'Bala']);

    $this->post(route('groups.members.store', $group), ['customer_id' => $customer->id, 'q' => 'Bala'])
        ->assertRedirect(route('groups.members.edit', [$group, 'q' => 'Bala']))
        ->assertSessionHas('success', 'Added Bala as SN2612.');

    expect($group->members()->pluck('member_code')->all())->toBe(['SN2612']);
});

test('the same customer can take several seats, each with its own id', function () {
    $group = ChitGroup::factory()->create();
    $customer = Customer::factory()->create(['customer_code' => 'SN2612']);

    foreach (range(1, 3) as $seat) {
        $this->post(route('groups.members.store', $group), ['customer_id' => $customer->id]);
    }

    expect($group->members()->pluck('member_code')->all())
        ->toBe(['SN2612', 'SN2612-2', 'SN2612-3']);
});

test('a removed seat id is reused before a new one is made', function () {
    $group = ChitGroup::factory()->create();
    $customer = Customer::factory()->create(['customer_code' => 'SN2612']);

    $this->post(route('groups.members.store', $group), ['customer_id' => $customer->id]);
    $this->post(route('groups.members.store', $group), ['customer_id' => $customer->id]);

    $this->delete(route('groups.members.destroy', [$group, $group->members()->first()]));

    $this->post(route('groups.members.store', $group), ['customer_id' => $customer->id]);

    expect($group->members()->pluck('member_code')->sort()->values()->all())
        ->toBe(['SN2612', 'SN2612-2']);
});

test('inactive customers cannot be added', function () {
    $group = ChitGroup::factory()->create();
    $customer = Customer::factory()->inactive()->create();

    $this->post(route('groups.members.store', $group), ['customer_id' => $customer->id])
        ->assertSessionHasErrors('customer_id');

    expect($group->members()->count())->toBe(0);
});

test('a forming group can hold more members than planned', function () {
    $group = ChitGroup::factory()->withMembers(3)->create(['member_count' => 2]);

    $this->post(route('groups.members.store', $group), ['customer_id' => Customer::factory()->create()->id])
        ->assertSessionHasNoErrors();

    expect($group->members()->count())->toBe(4);

    $this->get(route('groups.show', $group))
        ->assertOk()
        ->assertSee('Remove 2 to start');
});

test('a member can be removed from a forming group', function () {
    $group = ChitGroup::factory()->withMembers(2)->create();
    $member = $group->members()->first();

    $this->delete(route('groups.members.destroy', [$group, $member]))
        ->assertSessionHas('success');

    expect($group->members()->count())->toBe(1);
});

test('a member of another group cannot be removed through this group', function () {
    $group = ChitGroup::factory()->create();
    $otherMember = ChitGroupMember::factory()->create();

    $this->delete(route('groups.members.destroy', [$group, $otherMember]))->assertNotFound();

    expect(ChitGroupMember::whereKey($otherMember->id)->exists())->toBeTrue();
});

test('a group starts only with exactly the planned number of members', function (int $memberTotal, bool $canStart) {
    $group = ChitGroup::factory()->withPayouts()->withMembers($memberTotal)->create(['member_count' => 3]);

    $this->post(route('groups.start', $group))->assertRedirect(route('groups.show', $group));

    expect($group->fresh()->isRunning())->toBe($canStart);
})->with([
    'too few' => [2, false],
    'exact' => [3, true],
    'too many' => [4, false],
]);

test('new members are added to the end of the order', function () {
    $group = ChitGroup::factory()->create();

    foreach (['First', 'Second', 'Third'] as $name) {
        $this->post(route('groups.members.store', $group), [
            'customer_id' => Customer::factory()->create(['name' => $name])->id,
        ]);
    }

    expect($group->members()->with('customer')->get()->pluck('customer.name')->all())->toBe(['First', 'Second', 'Third'])
        ->and($group->members()->pluck('position')->all())->toBe([1, 2, 3]);
});

test('members can be reordered and the order shows on the group page', function () {
    $group = ChitGroup::factory()->create();

    $members = collect(['Anand', 'Bharathi', 'Chandran'])->map(fn ($name, $index) => ChitGroupMember::factory()->create([
        'chit_group_id' => $group->id,
        'customer_id' => Customer::factory()->create(['name' => $name])->id,
        'position' => $index + 1,
    ]));

    $this->patchJson(route('groups.members.reorder', $group), [
        'member_ids' => [$members[2]->id, $members[0]->id, $members[1]->id],
    ])->assertOk()->assertJson(['message' => 'Order saved.']);

    expect($group->members()->with('customer')->get()->pluck('customer.name')->all())
        ->toBe(['Chandran', 'Anand', 'Bharathi']);

    $this->get(route('groups.show', $group))->assertSeeInOrder(['Chandran', 'Anand', 'Bharathi']);
    $this->get(route('groups.members.edit', $group))->assertSeeInOrder(['Chandran', 'Anand', 'Bharathi']);
});

test('a reorder must list exactly the members of this group', function (Closure $makeIds) {
    $group = ChitGroup::factory()->withMembers(3)->create();
    $otherMember = ChitGroupMember::factory()->create();

    $ids = $makeIds($group->members()->pluck('id')->all(), $otherMember->id);

    $this->patchJson(route('groups.members.reorder', $group), ['member_ids' => $ids])
        ->assertUnprocessable();
})->with([
    'missing a member' => [fn (array $ids) => array_slice($ids, 0, 2)],
    'member from another group' => [fn (array $ids, int $otherId) => [$ids[0], $ids[1], $otherId]],
    'duplicate member' => [fn (array $ids) => [$ids[0], $ids[0], $ids[1]]],
]);

test('members can be reordered after the group has started', function () {
    $group = ChitGroup::factory()->running()->withMembers(2)->create();
    $ids = $group->members()->pluck('id')->all();

    $this->patchJson(route('groups.members.reorder', $group), ['member_ids' => array_reverse($ids)])
        ->assertOk();

    expect($group->members()->pluck('id')->all())->toBe(array_reverse($ids));
});

test('members can be added and removed after the group has started', function () {
    $group = ChitGroup::factory()->running()->withMembers(2)->create();
    $member = $group->members()->first();

    $this->get(route('groups.show', $group))
        ->assertOk()
        ->assertSee(route('groups.members.edit', $group));

    $this->get(route('groups.members.edit', $group))
        ->assertOk()
        ->assertDontSee('to start');

    $this->post(route('groups.members.store', $group), ['customer_id' => Customer::factory()->create()->id])
        ->assertSessionHas('success');

    $this->delete(route('groups.members.destroy', [$group, $member]))->assertSessionHas('success');

    expect($group->members()->count())->toBe(2)
        ->and($group->fresh()->isRunning())->toBeTrue();
});

test('each member card has a call button that dials their phone', function () {
    $customer = Customer::factory()->create(['name' => 'Murugan', 'phone' => '98765 43210']);
    $group = ChitGroup::factory()->create();

    ChitGroupMember::factory()->create(['chit_group_id' => $group->id, 'customer_id' => $customer->id]);

    $this->get(route('groups.show', $group))
        ->assertOk()
        ->assertSee('href="tel:9876543210"', false)
        ->assertSee('Call Murugan on 98765 43210');
});

test('view group shows member cards and their details with every linked group', function () {
    $customer = Customer::factory()->create(['customer_code' => 'SN2700', 'name' => 'Chitra Devi', 'remarks' => 'Teacher']);

    $group = ChitGroup::factory()->create(['name' => 'Main Group']);
    $otherGroup = ChitGroup::factory()->create(['name' => 'Second Group']);

    ChitGroupMember::factory()->create(['chit_group_id' => $group->id, 'customer_id' => $customer->id]);
    ChitGroupMember::factory()->create(['chit_group_id' => $otherGroup->id, 'customer_id' => $customer->id, 'member_code' => 'SN2700']);

    $this->get(route('groups.show', $group))
        ->assertOk()
        ->assertSee('SN2700')
        ->assertSee('Chitra Devi')
        ->assertSee('Teacher')
        ->assertSee('Details')
        ->assertViewHas('membersData', function (array $members) {
            $details = array_values($members)[0];

            return $details['name'] === 'Chitra Devi'
                && collect($details['groups'])->pluck('name')->all() === ['Main Group', 'Second Group'];
        });
});
