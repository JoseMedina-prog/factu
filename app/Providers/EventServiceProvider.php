<?php

namespace App\Providers;

use App\Events\InvoiceOverdue;
use App\Events\InvoiceRejected;
use App\Events\InvoiceValidated;
use App\Events\LowStockReached;
use App\Events\NumberingRangeAlert;
use App\Events\PaymentRegistered;
use App\Listeners\SendAdminInvoiceNotification;
use App\Listeners\SendInvoiceEmailListener;
use App\Listeners\SendLowStockNotification;
use App\Listeners\SendNumberingAlertNotification;
use App\Listeners\SendOverdueInvoiceNotification;
use App\Listeners\SendPaymentNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        InvoiceValidated::class => [
            SendInvoiceEmailListener::class,
        ],
        InvoiceRejected::class => [
            SendAdminInvoiceNotification::class,
        ],
        PaymentRegistered::class => [
            SendPaymentNotification::class,
        ],
        InvoiceOverdue::class => [
            SendOverdueInvoiceNotification::class,
        ],
        NumberingRangeAlert::class => [
            SendNumberingAlertNotification::class,
        ],
        LowStockReached::class => [
            SendLowStockNotification::class,
        ],
    ];

    public function boot(): void
    {
        parent::boot();
    }
}