<?php

use App\Http\Controllers\Client\ClientController;
use App\Http\Controllers\CreditNote\CreditNoteController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Invoice\InvoiceController;
use App\Http\Controllers\NumberingRange\NumberingRangeController;
use App\Http\Controllers\Inventory\InventoryMovementController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Payment\AccountsReceivableController;
use App\Http\Controllers\Payment\PaymentController;
use App\Http\Controllers\Product\ProductController;
use App\Http\Controllers\Report\ReportController;
use App\Http\Controllers\Setting\SettingController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('auth')->group(function () {
    Route::middleware(['tenant', 'subscription.active'])->group(function () {
        Route::get('/dashboard', DashboardController::class)->name('dashboard');

        Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
        Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllRead'])->name('notifications.mark-all-read');

        Route::resource('clients', ClientController::class)->names('clients');
        Route::resource('products', ProductController::class)->names('products');
        Route::resource('invoices', InvoiceController::class)->names('invoices');
        Route::resource('credit-notes', CreditNoteController::class)->names('credit-notes');

        Route::resource('payments', PaymentController::class)
            ->except(['edit', 'update'])
            ->names('payments');

        Route::post('payments/{payment}/confirm', [PaymentController::class, 'confirm'])->name('payments.confirm');
        Route::post('payments/{payment}/cancel', [PaymentController::class, 'cancel'])->name('payments.cancel');

        Route::get('accounts-receivable', [AccountsReceivableController::class, 'index'])->name('accounts-receivable.index');
        Route::get('accounts-receivable/export', [AccountsReceivableController::class, 'export'])->name('accounts-receivable.export');

        Route::prefix('inventory')->name('inventory.')->group(function () {
            Route::get('/', [InventoryMovementController::class, 'index'])->name('index');
            Route::get('/valuation', [InventoryMovementController::class, 'valuation'])->name('valuation');
            Route::get('/product/{product}', [InventoryMovementController::class, 'productHistory'])->name('product-history');
            Route::post('/entry', [InventoryMovementController::class, 'storeEntry'])->name('entry');
            Route::post('/exit', [InventoryMovementController::class, 'storeExit'])->name('exit');
            Route::post('/adjust', [InventoryMovementController::class, 'storeAdjustment'])->name('adjust');
        });

        Route::prefix('settings')->name('settings.')->group(function () {
            Route::get('/', [SettingController::class, 'index'])->name('index');
            Route::put('/', [SettingController::class, 'update'])->name('update');
            Route::post('/logo', [SettingController::class, 'uploadLogo'])->name('logo');

            Route::resource('numbering', NumberingRangeController::class)
                ->names('numbering')
                ->parameters(['numbering' => 'numberingRange']);
        });

        Route::get('/reports/sales', [ReportController::class, 'sales'])->name('reports.sales');
        Route::get('/reports/client/{client}', [ReportController::class, 'clientStatement'])->name('reports.client-statement');
        Route::get('/reports/invoices/export', [ReportController::class, 'exportInvoices'])->name('reports.invoices.export');

        Route::post('/invoices/{invoice}/send', [InvoiceController::class, 'send'])->name('invoices.send');
        Route::post('/invoices/{invoice}/cancel', [InvoiceController::class, 'cancel'])->name('invoices.cancel');
        Route::post('/invoices/{invoice}/refresh-status', [InvoiceController::class, 'refreshStatus'])->name('invoices.refreshStatus');
        Route::get('/invoices/{invoice}/download-pdf', [InvoiceController::class, 'downloadPdf'])->name('invoices.downloadPdf');
        Route::get('/invoices/{invoice}/stream-pdf', [InvoiceController::class, 'streamPdf'])->name('invoices.streamPdf');
        Route::get('/invoices/{invoice}/factus-pdf', [InvoiceController::class, 'downloadFactusPdf'])->name('invoices.factusPdf');
        Route::get('/invoices/{invoice}/factus-xml', [InvoiceController::class, 'downloadFactusXml'])->name('invoices.factusXml');

        Route::post('/credit-notes/{creditNote}/approve', [CreditNoteController::class, 'approve'])->name('credit-notes.approve');
        Route::post('/credit-notes/{creditNote}/cancel', [CreditNoteController::class, 'cancel'])->name('credit-notes.cancel');
        Route::get('/credit-notes/{creditNote}/download-pdf', [CreditNoteController::class, 'downloadPdf'])->name('credit-notes.downloadPdf');
        Route::get('/credit-notes/{creditNote}/stream-pdf', [CreditNoteController::class, 'streamPdf'])->name('credit-notes.streamPdf');
    });
});

require __DIR__ . '/admin.php';
require __DIR__ . '/auth.php';
