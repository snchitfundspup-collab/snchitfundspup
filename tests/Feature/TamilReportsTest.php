<?php

use App\Http\Middleware\SetLanguage;
use App\Models\ChitGroup;
use App\Models\ChitGroupMember;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->travelTo(now()->setDate(2026, 10, 20)->setTime(10, 0));

    $this->actingAs(User::factory()->create(['name' => 'Narayanan']));

    $group = ChitGroup::factory()->running()->create(['name' => 'Diwali Group', 'start_date' => '2026-09-15', 'installment_amount' => 5000]);
    $this->customer = Customer::factory()->create(['name' => 'Lakshmi', 'remarks' => 'Teacher']);
    $member = ChitGroupMember::factory()->create(['chit_group_id' => $group->id, 'customer_id' => $this->customer->id]);

    $this->payment = Payment::record(
        ChitGroupMember::with('chitGroup', 'allocations')->find($member->id),
        ['amount' => 5000, 'month_number' => 1, 'paid_at' => '2026-09-20 10:00', 'method' => 'cash'],
    );
});

test('with Tamil chosen, printed reports are in Tamil', function () {
    $this->withUnencryptedCookie(SetLanguage::COOKIE, 'ta')
        ->get(route('traders.stock.print'))
        ->assertOk()
        ->assertSee('lang="ta"', false)
        ->assertSeeText('அச்சிடு')
        ->assertSeeText('இருப்பில் உள்ள அரிசி')
        ->assertDontSeeText('Rice in stock');
});

test('with Tamil chosen, PDFs are made in Tamil by mPDF', function () {
    $response = $this->withUnencryptedCookie(SetLanguage::COOKIE, 'ta')
        ->get(route('payments.receipt.pdf', $this->payment))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');

    /* mPDF writes PDF 1.4; DomPDF (which cannot join Tamil letters) writes 1.7 */
    expect($response->getContent())->toStartWith('%PDF-1.4');
});

test('English stays the default for reports and PDFs', function () {
    $this->get(route('traders.stock.print'))
        ->assertOk()
        ->assertSeeText('Rice in stock')
        ->assertDontSeeText('இருப்பில் உள்ள அரிசி');

    $response = $this->get(route('payments.receipt.pdf', $this->payment))->assertOk();

    expect($response->getContent())->toStartWith('%PDF-1.7');
});

test('payment methods and month labels follow the language', function () {
    app()->setLocale('ta');

    expect($this->payment->methodLabel())->toBe('ரொக்கம்')
        ->and($this->payment->load('allocations')->monthsCoveredLabel())->toBe('மாதம் 1');

    app()->setLocale('en');

    expect($this->payment->methodLabel())->toBe('Cash');
});
