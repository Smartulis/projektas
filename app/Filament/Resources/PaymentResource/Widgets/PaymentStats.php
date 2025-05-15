<?php

namespace App\Filament\Resources\PaymentResource\Widgets;

use App\Filament\Resources\PaymentResource;
use App\Filament\Resources\UserSettingResource;
use App\Models\Payment;
use App\Models\Invoice;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PaymentStats extends BaseWidget
{
    protected static string $resource = PaymentResource::class;
public static function getNavigationLabel(): string
    {
        return __('translate.payment.navigation_label');
    }
    protected function getCards(): array
    {
        $settings     = UserSettingResource::getSettings();
        $currencyCode = $settings->currency ?? 'EUR';

        $totalRevenue = Payment::sum('amount');
        $dueWithin7   = Invoice::where('status', 'sent')
            ->whereBetween('due_date', [now(), now()->addDays(14)])
            ->sum('total_with_vat');
        $avgPaymentTime = Payment::whereNotNull('paid_at')
            ->join('invoices', 'payments.invoice_id', '=', 'invoices.id')
            ->selectRaw('AVG(DATEDIFF(payments.paid_at, invoices.issue_date)) as avg_days')
            ->value('avg_days') ?? 0;
        $paidLastMonth = Payment::whereBetween('paid_at', [
                now()->subMonth()->startOfMonth(),
                now()->subMonth()->endOfMonth(),
            ])->sum('amount');

        $fmt = fn(float $v): string => number_format($v, 2, '.', '');

        return [
            Stat::make(__('translate.payment.stats.total_revenue'), $fmt($totalRevenue) . ' ' . $currencyCode),
            Stat::make(__('translate.payment.stats.due_within_14_days'), $fmt($dueWithin7)   . ' ' . $currencyCode),
            Stat::make(
                __('translate.payment.stats.avg_payment_time'),
                round($avgPaymentTime, 1) . ' ' . __('translate.payment.stats.days_short')
            ),
            Stat::make(__('translate.payment.stats.paid_last_month'), $fmt($paidLastMonth) . ' ' . $currencyCode),
        ];
    }
}
