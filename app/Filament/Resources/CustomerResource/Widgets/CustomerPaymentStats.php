<?php

namespace App\Filament\Resources\CustomerResource\Widgets;

use App\Filament\Resources\CustomerResource;
use App\Filament\Resources\UserSettingResource;
use App\Filament\Resources\InvoiceResource;
use App\Models\Invoice;
use App\Models\Payment;
use Carbon\Carbon;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CustomerPaymentStats extends BaseWidget
{
    protected static string $resource = CustomerResource::class;

    // Filament v3 automatiškai injectina šitą property
    public $record;

    protected function getCards(): array
    {
        /** @var \App\Models\Customer $customer */
        $customer = $this->record;

        if (!$customer) {
            return [];
        }

        // Get currency info
        $settings = UserSettingResource::getSettings();
        $currencyCode = $settings->currency ?? 'EUR';
        $symbol = InvoiceResource::getCurrencySymbol($currencyCode);

        // Get all invoice IDs for this customer
        $invoiceIds = Invoice::where('customer_id', $customer->customer_id)->pluck('id');

        // 1) Total Paid - verify payments exist
        $totalPaid = Payment::whereIn('invoice_id', $invoiceIds)
            ->sum('amount');

        // 2) Total Unpaid - check status values in database
        $totalUnpaid = Invoice::whereIn('id', $invoiceIds)
            ->whereIn('status', ['sent', 'overdue'])
            ->sum('total_with_vat');

        // 3) Due within 30 days - verify date ranges
        $due30 = Invoice::whereIn('id', $invoiceIds)
            ->where('status', 'sent')
            ->whereBetween('due_date', [
                Carbon::now(),
                Carbon::now()->addDays(30),
            ])->sum('total_with_vat');

        // 4) Avg payment time - check relationship
        $avgPaymentTime = Payment::whereIn('invoice_id', $invoiceIds)
            ->join('invoices', 'payments.invoice_id', '=', 'invoices.id')
            ->selectRaw('AVG(DATEDIFF(payments.paid_at, invoices.issue_date)) as avg_days')
            ->value('avg_days') ?? 0;

        $fmt = fn(float $v): string => number_format($v, 2, '.', '');

       return [
    Stat::make(__('translate.customer.widgets.total_paid'), "{$fmt($totalPaid)} {$symbol}")
        ->description(__('translate.customer.widgets.from_invoices', ['count' => $invoiceIds->count()])),
    Stat::make(__('translate.customer.widgets.total_unpaid'), "{$fmt($totalUnpaid)} {$symbol}")
        ->description(__('translate.customer.widgets.still_sent')),
    Stat::make(__('translate.customer.widgets.due_30'), "{$fmt($due30)} {$symbol}"),
    Stat::make(__('translate.customer.widgets.avg_payment'), __('translate.customer.widgets.days', ['days' => round($avgPaymentTime, 1)])),
];
    }
}
