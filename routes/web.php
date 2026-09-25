<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\BalanceSheetController;
use App\Http\Controllers\ChitGroupController;
use App\Http\Controllers\ChitGroupMemberController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\CustomerStatementController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DrawController;
use App\Http\Controllers\DuesReportController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\JoinRequestController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PaymentLedgerController;
use App\Http\Controllers\Portal;
use App\Http\Controllers\Settings\PasswordController;
use App\Http\Controllers\Traders;
use App\Http\Controllers\UsageController;
use App\Http\Middleware\EnsureCustomerChoseOwnPassword;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| LOGIN PAGES — staff at /login/admin, customers at /login/customer
|--------------------------------------------------------------------------
*/

/* old address → staff login */
Route::redirect('/login', '/login/admin', 301);

Route::middleware('guest:web')->group(function () {

    Route::get(
        '/login/admin',
        [LoginController::class, 'create']
    )->name('login');

    Route::post(
        '/login/admin',
        [LoginController::class, 'store']
    )->name('login.store');

});

Route::prefix('login/customer')->name('portal.')->middleware('guest:customer')->group(function () {
    Route::get('/', [Portal\AuthController::class, 'create'])->name('login');
    Route::post('/', [Portal\AuthController::class, 'store'])->name('login.store')->middleware('throttle:20,1');
    Route::get('/choose', [Portal\AuthController::class, 'choose'])->name('choose');
    Route::post('/choose', [Portal\AuthController::class, 'chosen'])->name('choose.store');
});

/*
|--------------------------------------------------------------------------
| ADMIN AREA (signed-in staff only)
|--------------------------------------------------------------------------
*/

Route::middleware('auth:web')->group(function () {

    Route::get(
        '/',
        [DashboardController::class, 'index']
    )->name('dashboard');

    Route::post(
        '/logout',
        [LoginController::class, 'destroy']
    )->name('logout');

    Route::get(
        '/usage',
        [UsageController::class, 'index']
    )->name('usage.index')->middleware('can:view-usage');

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

    Route::delete(
        '/customers/{customer}/password',
        [CustomerController::class, 'resetPassword']
    )->name('customers.password.reset');

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

    /* customers asking to join a forming group: the office decides */
    Route::get(
        '/join-requests',
        [JoinRequestController::class, 'index']
    )->name('groups.requests.index');

    Route::post(
        '/join-requests/{joinRequest}/approve',
        [JoinRequestController::class, 'approve']
    )->name('groups.requests.approve');

    Route::post(
        '/join-requests/{joinRequest}/dismiss',
        [JoinRequestController::class, 'dismiss']
    )->name('groups.requests.dismiss');

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
        '/payments/print',
        [PaymentController::class, 'printList']
    )->name('payments.print');

    Route::get(
        '/payments/list.pdf',
        [PaymentController::class, 'listPdf']
    )->name('payments.pdf');

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
        '/draws/winners',
        [DrawController::class, 'winners']
    )->name('draws.winners');

    Route::get(
        '/draws/winners.pdf',
        [DrawController::class, 'winnersPdf']
    )->name('draws.winners.pdf');

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

    Route::post(
        '/draws/{draw}/voucher-printed',
        [DrawController::class, 'voucherPrinted']
    )->name('draws.voucher.printed');

    Route::delete(
        '/draws/{draw}',
        [DrawController::class, 'destroy']
    )->name('draws.destroy');

    /*
    |--------------------------------------------------------------------------
    | EXPENSES & BALANCE SHEET (Management)
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/expenses',
        [ExpenseController::class, 'index']
    )->name('expenses.index');

    Route::get(
        '/expenses/create',
        [ExpenseController::class, 'create']
    )->name('expenses.create');

    Route::post(
        '/expenses',
        [ExpenseController::class, 'store']
    )->name('expenses.store');

    Route::get(
        '/expenses/print',
        [ExpenseController::class, 'printList']
    )->name('expenses.print');

    Route::get(
        '/expenses/list.pdf',
        [ExpenseController::class, 'pdf']
    )->name('expenses.pdf');

    Route::get(
        '/expenses/balance-sheet',
        [BalanceSheetController::class, 'index']
    )->name('expenses.balance');

    Route::get(
        '/expenses/balance-sheet/print',
        [BalanceSheetController::class, 'printSheet']
    )->name('expenses.balance.print');

    Route::get(
        '/expenses/balance-sheet.pdf',
        [BalanceSheetController::class, 'pdf']
    )->name('expenses.balance.pdf');

    Route::post(
        '/expenses/settlements',
        [BalanceSheetController::class, 'storeSettlement']
    )->name('expenses.settlements.store');

    Route::delete(
        '/expenses/settlements/{settlement}',
        [BalanceSheetController::class, 'destroySettlement']
    )->name('expenses.settlements.destroy');

    Route::get(
        '/expenses/{expense}/edit',
        [ExpenseController::class, 'edit']
    )->name('expenses.edit');

    Route::put(
        '/expenses/{expense}',
        [ExpenseController::class, 'update']
    )->name('expenses.update');

    Route::delete(
        '/expenses/{expense}',
        [ExpenseController::class, 'destroy']
    )->name('expenses.destroy');

    /*
    |--------------------------------------------------------------------------
    | REPORTS
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/reports/pending-and-due',
        [DuesReportController::class, 'index']
    )->name('reports.dues');

    Route::get(
        '/reports/pending-and-due/print',
        [DuesReportController::class, 'printReport']
    )->name('reports.dues.print');

    Route::get(
        '/reports/pending-and-due.pdf',
        [DuesReportController::class, 'pdf']
    )->name('reports.dues.pdf');

    Route::get(
        '/reports/customer-statement',
        [CustomerStatementController::class, 'index']
    )->name('reports.customer');

    Route::get(
        '/reports/customer-statement/{customer}.pdf',
        [CustomerStatementController::class, 'pdf']
    )->name('reports.customer.pdf');

    Route::get(
        '/settings/password',
        [PasswordController::class, 'edit']
    )->name('password.edit');

    Route::put(
        '/settings/password',
        [PasswordController::class, 'update']
    )->name('password.update');

    /*
    |--------------------------------------------------------------------------
    | SN TRADERS (rice purchase & sales) — its own pages, customers shared
    |--------------------------------------------------------------------------
    */

    Route::prefix('traders')->name('traders.')->group(function () {

        Route::get('/', [Traders\DashboardController::class, 'index'])->name('dashboard');

        /* masters */
        Route::get('/varieties', [Traders\VarietyController::class, 'index'])->name('varieties.index');
        Route::post('/varieties', [Traders\VarietyController::class, 'store'])->name('varieties.store');
        Route::put('/varieties/{variety}', [Traders\VarietyController::class, 'update'])->name('varieties.update');
        Route::delete('/varieties/{variety}', [Traders\VarietyController::class, 'destroy'])->name('varieties.destroy');

        Route::get('/suppliers', [Traders\SupplierController::class, 'index'])->name('suppliers.index');
        Route::post('/suppliers', [Traders\SupplierController::class, 'store'])->name('suppliers.store');
        Route::put('/suppliers/{supplier}', [Traders\SupplierController::class, 'update'])->name('suppliers.update');
        Route::delete('/suppliers/{supplier}', [Traders\SupplierController::class, 'destroy'])->name('suppliers.destroy');

        /* purchases */
        Route::get('/purchases', [Traders\PurchaseController::class, 'index'])->name('purchases.index');
        Route::get('/purchases/new', [Traders\PurchaseController::class, 'create'])->name('purchases.create');
        Route::post('/purchases', [Traders\PurchaseController::class, 'store'])->name('purchases.store');
        Route::get('/purchases/print', [Traders\PurchaseController::class, 'printList'])->name('purchases.print');
        Route::get('/purchases/list.pdf', [Traders\PurchaseController::class, 'listPdf'])->name('purchases.list-pdf');
        Route::get('/purchases/{purchase}', [Traders\PurchaseController::class, 'show'])->name('purchases.show');
        Route::get('/purchases/{purchase}/pdf', [Traders\PurchaseController::class, 'pdf'])->name('purchases.pdf');
        Route::delete('/purchases/{purchase}', [Traders\PurchaseController::class, 'destroy'])->name('purchases.destroy');

        /* sales */
        Route::get('/sales', [Traders\SaleController::class, 'index'])->name('sales.index');
        Route::get('/sales/new', [Traders\SaleController::class, 'create'])->name('sales.create');
        Route::post('/sales', [Traders\SaleController::class, 'store'])->name('sales.store');
        Route::get('/sales/print', [Traders\SaleController::class, 'printList'])->name('sales.print');
        Route::get('/sales/list.pdf', [Traders\SaleController::class, 'listPdf'])->name('sales.list-pdf');
        Route::get('/sales/{sale}', [Traders\SaleController::class, 'show'])->name('sales.show');
        Route::get('/sales/{sale}/pdf', [Traders\SaleController::class, 'pdf'])->name('sales.pdf');
        Route::delete('/sales/{sale}', [Traders\SaleController::class, 'destroy'])->name('sales.destroy');

        /* stock */
        Route::get('/stock', [Traders\StockController::class, 'index'])->name('stock');
        Route::get('/stock/print', [Traders\StockController::class, 'printStock'])->name('stock.print');
        Route::get('/stock.pdf', [Traders\StockController::class, 'pdf'])->name('stock.pdf');

        /* customer credit: receipts, balances, account statements */
        Route::get('/receipts', [Traders\ReceiptController::class, 'index'])->name('receipts.index');
        Route::get('/receipts/new', [Traders\ReceiptController::class, 'create'])->name('receipts.create');
        Route::post('/receipts', [Traders\ReceiptController::class, 'store'])->name('receipts.store');
        Route::get('/receipts/{receipt}', [Traders\ReceiptController::class, 'show'])->name('receipts.show');
        Route::get('/receipts/{receipt}/pdf', [Traders\ReceiptController::class, 'pdf'])->name('receipts.pdf');
        Route::delete('/receipts/{receipt}', [Traders\ReceiptController::class, 'destroy'])->name('receipts.destroy');

        Route::get('/customer-balances', [Traders\CustomerAccountController::class, 'index'])->name('balances.index');
        Route::get('/customer-balances/print', [Traders\CustomerAccountController::class, 'printBalances'])->name('balances.print');
        Route::get('/customer-balances.pdf', [Traders\CustomerAccountController::class, 'balancesPdf'])->name('balances.pdf');
        Route::get('/customers/{customer}/account', [Traders\CustomerAccountController::class, 'show'])->name('accounts.show');
        Route::get('/customers/{customer}/account.pdf', [Traders\CustomerAccountController::class, 'pdf'])->name('accounts.pdf');

        /* rice orders customers place from their own pages */
        Route::get('/orders', [Traders\OrderController::class, 'index'])->name('orders.index');
        Route::post('/orders/{order}/cancel', [Traders\OrderController::class, 'cancel'])->name('orders.cancel');

        /* reports: rice sales, profit & loss, customer dues, day book */
        Route::get('/reports/customer-statement', [Traders\ReportController::class, 'customerStatement'])->name('reports.customer');
        Route::get('/reports/{report}', [Traders\ReportController::class, 'show'])->name('reports.show')->whereIn('report', array_keys(Traders\ReportController::REPORTS));
        Route::get('/reports/{report}/print', [Traders\ReportController::class, 'printReport'])->name('reports.print')->whereIn('report', array_keys(Traders\ReportController::REPORTS));
        Route::get('/reports/{report}/pdf', [Traders\ReportController::class, 'pdf'])->name('reports.pdf')->whereIn('report', array_keys(Traders\ReportController::REPORTS));

        /* expenses & balance sheet (its own tables) */
        Route::get('/expenses', [ExpenseController::class, 'index'])->name('expenses.index');
        Route::get('/expenses/create', [ExpenseController::class, 'create'])->name('expenses.create');
        Route::post('/expenses', [ExpenseController::class, 'store'])->name('expenses.store');
        Route::get('/expenses/print', [ExpenseController::class, 'printList'])->name('expenses.print');
        Route::get('/expenses/list.pdf', [ExpenseController::class, 'pdf'])->name('expenses.pdf');
        Route::get('/expenses/balance-sheet', [BalanceSheetController::class, 'index'])->name('expenses.balance');
        Route::get('/expenses/balance-sheet/print', [BalanceSheetController::class, 'printSheet'])->name('expenses.balance.print');
        Route::get('/expenses/balance-sheet.pdf', [BalanceSheetController::class, 'pdf'])->name('expenses.balance.pdf');
        Route::post('/expenses/settlements', [BalanceSheetController::class, 'storeSettlement'])->name('expenses.settlements.store');
        Route::delete('/expenses/settlements/{settlement}', [BalanceSheetController::class, 'destroySettlement'])->name('expenses.settlements.destroy');
        Route::get('/expenses/{expense}/edit', [ExpenseController::class, 'edit'])->name('expenses.edit');
        Route::put('/expenses/{expense}', [ExpenseController::class, 'update'])->name('expenses.update');
        Route::delete('/expenses/{expense}', [ExpenseController::class, 'destroy'])->name('expenses.destroy');

    });

});

/*
|--------------------------------------------------------------------------
| CUSTOMERS' OWN PAGES (/my) — signed in at /login/customer
|--------------------------------------------------------------------------
*/

Route::prefix('my')->name('portal.')->group(function () {

    Route::middleware('auth:customer')->group(function () {
        Route::post('/logout', [Portal\AuthController::class, 'destroy'])->name('logout');
        Route::get('/password', [Portal\PasswordController::class, 'edit'])->name('password.edit');
        Route::put('/password', [Portal\PasswordController::class, 'update'])->name('password.update');

        /* everything else waits until the customer has chosen their own password */
        Route::middleware(EnsureCustomerChoseOwnPassword::class)->group(function () {
            Route::get('/', [Portal\DashboardController::class, 'index'])->name('dashboard');

            /* SN Chit Funds */
            Route::get('/groups', [Portal\ChitController::class, 'groups'])->name('groups');
            Route::get('/groups/{member}', [Portal\ChitController::class, 'seat'])->name('groups.show');
            Route::get('/receipts/{payment}/pdf', [Portal\ChitController::class, 'receiptPdf'])->name('receipts.pdf');
            Route::get('/statement/pdf', [Portal\ChitController::class, 'statementPdf'])->name('statement.pdf');
            Route::get('/statement/print', [Portal\ChitController::class, 'statementPrint'])->name('statement.print');
            Route::get('/upcoming', [Portal\ChitController::class, 'upcoming'])->name('upcoming');
            Route::get('/upcoming/{group}', [Portal\ChitController::class, 'upcomingGroup'])->name('upcoming.show');
            Route::post('/upcoming/{group}/interest', [Portal\ChitController::class, 'showInterest'])->name('upcoming.interest');
            Route::post('/join-requests/{joinRequest}/withdraw', [Portal\ChitController::class, 'withdrawInterest'])->name('upcoming.withdraw');

            /* SN Traders */
            Route::get('/rice', [Portal\TradersController::class, 'rice'])->name('rice');
            Route::get('/orders', [Portal\TradersController::class, 'orders'])->name('orders');
            Route::post('/orders', [Portal\TradersController::class, 'placeOrder'])->name('orders.store');
            Route::post('/orders/{order}/cancel', [Portal\TradersController::class, 'cancelOrder'])->name('orders.cancel');
            Route::get('/bills', [Portal\TradersController::class, 'bills'])->name('bills');
            Route::get('/bills/statement/pdf', [Portal\TradersController::class, 'statementPdf'])->name('bills.statement.pdf');
            Route::get('/bills/statement/print', [Portal\TradersController::class, 'statementPrint'])->name('bills.statement.print');
            Route::get('/bills/invoices/{sale}/pdf', [Portal\TradersController::class, 'invoicePdf'])->name('bills.invoice.pdf');
            Route::get('/bills/receipts/{receipt}/pdf', [Portal\TradersController::class, 'receiptPdf'])->name('bills.receipt.pdf');
        });
    });

});
