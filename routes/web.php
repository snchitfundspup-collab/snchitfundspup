<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\ChitGroupController;
use App\Http\Controllers\ChitGroupMemberController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DrawController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PaymentLedgerController;
use App\Http\Controllers\Settings\PasswordController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| ADMIN LOGIN
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {

    Route::get(
        '/login',
        [LoginController::class, 'create']
    )->name('login');

    Route::post(
        '/login',
        [LoginController::class, 'store']
    )->name('login.store');

});

/*
|--------------------------------------------------------------------------
| ADMIN AREA (signed-in staff only)
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {

    Route::get(
        '/',
        [DashboardController::class, 'index']
    )->name('dashboard');

    Route::post(
        '/logout',
        [LoginController::class, 'destroy']
    )->name('logout');

    Route::get(
        '/customers',
        [CustomerController::class, 'index']
    )->name('customers.index');

    Route::get(
        '/customers/create',
        [CustomerController::class, 'create']
    )->name('customers.create');

    Route::post(
        '/customers',
        [CustomerController::class, 'store']
    )->name('customers.store');

    Route::put(
        '/customers/{customer}',
        [CustomerController::class, 'update']
    )->name('customers.update');

    /*
    |--------------------------------------------------------------------------
    | GROUPS
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/groups',
        [ChitGroupController::class, 'index']
    )->name('groups.index');

    Route::get(
        '/groups/create',
        [ChitGroupController::class, 'create']
    )->name('groups.create');

    Route::post(
        '/groups',
        [ChitGroupController::class, 'store']
    )->name('groups.store');

    Route::get(
        '/groups/{group}',
        [ChitGroupController::class, 'show']
    )->name('groups.show');

    Route::get(
        '/groups/{group}/edit',
        [ChitGroupController::class, 'edit']
    )->name('groups.edit');

    Route::put(
        '/groups/{group}',
        [ChitGroupController::class, 'update']
    )->name('groups.update');

    Route::post(
        '/groups/{group}/start',
        [ChitGroupController::class, 'start']
    )->name('groups.start');

    Route::get(
        '/groups/{group}/members',
        [ChitGroupMemberController::class, 'edit']
    )->name('groups.members.edit');

    Route::post(
        '/groups/{group}/members',
        [ChitGroupMemberController::class, 'store']
    )->name('groups.members.store');

    Route::patch(
        '/groups/{group}/members/order',
        [ChitGroupMemberController::class, 'reorder']
    )->name('groups.members.reorder');

    Route::delete(
        '/groups/{group}/members/{member}',
        [ChitGroupMemberController::class, 'destroy']
    )->scopeBindings()->name('groups.members.destroy');

    /*
    |--------------------------------------------------------------------------
    | PAYMENTS
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/payments',
        [PaymentController::class, 'index']
    )->name('payments.index');

    Route::get(
        '/payments/ledger',
        [PaymentLedgerController::class, 'index']
    )->name('payments.ledger');

    Route::get(
        '/payments/ledger/pdf',
        [PaymentLedgerController::class, 'pdf']
    )->name('payments.ledger.pdf');

    Route::get(
        '/payments/{payment}/pdf',
        [PaymentController::class, 'receiptPdf']
    )->name('payments.receipt.pdf');

    Route::get(
        '/payments/collect',
        [PaymentController::class, 'create']
    )->name('payments.create');

    Route::post(
        '/payments',
        [PaymentController::class, 'store']
    )->name('payments.store');

    Route::get(
        '/payments/{payment}',
        [PaymentController::class, 'show']
    )->name('payments.show');

    Route::delete(
        '/payments/{payment}',
        [PaymentController::class, 'destroy']
    )->name('payments.destroy');

    /*
    |--------------------------------------------------------------------------
    | DRAWS
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/draws',
        [DrawController::class, 'index']
    )->name('draws.index');

    Route::get(
        '/draws/run',
        [DrawController::class, 'create']
    )->name('draws.create');

    Route::post(
        '/draws',
        [DrawController::class, 'store']
    )->name('draws.store');

    Route::get(
        '/draws/{draw}',
        [DrawController::class, 'show']
    )->name('draws.show');

    Route::post(
        '/draws/{draw}/payout',
        [DrawController::class, 'payout']
    )->name('draws.payout');

    Route::get(
        '/draws/{draw}/voucher.pdf',
        [DrawController::class, 'voucherPdf']
    )->name('draws.voucher.pdf');

    Route::delete(
        '/draws/{draw}',
        [DrawController::class, 'destroy']
    )->name('draws.destroy');

    Route::get(
        '/settings/password',
        [PasswordController::class, 'edit']
    )->name('password.edit');

    Route::put(
        '/settings/password',
        [PasswordController::class, 'update']
    )->name('password.update');

});
