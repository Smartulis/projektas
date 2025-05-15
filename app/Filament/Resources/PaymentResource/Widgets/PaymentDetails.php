<?php

namespace App\Filament\Resources\PaymentResource\Widgets;

use App\Filament\Resources\PaymentResource;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Carbon\Carbon;

class PaymentDetails extends BaseWidget
{
    protected static string $resource = PaymentResource::class;
public static function getNavigationLabel(): string
    {
        return __('translate.payment.navigation_label');
    }
    public $record;

    public function getCards(): array
    {
        $payment    = $this->record;
        $amount     = number_format($payment->amount, 2, '.', '');
        $paidAt = $payment->paid_at
            ? Carbon::parse($payment->paid_at)
                ->locale('lt')
                ->isoFormat('L')
            : '—';
        $status     = ucfirst($payment->status);
        $invoiceNum = $payment->invoice?->invoice_number;
        $createdAt = Carbon::parse($payment->created_at)
            ->locale('lt')
            ->isoFormat('L');
        $currency   = $payment->invoice?->currency;

        if ($payment->method === 'card') {
            $last4       = $payment->card_last_four;
            $paymentInfo = __('translate.payment.details.method_card', ['last4' => $last4]);
        } else {
            $acct        = $payment->bank_account ?? '';
            $last4       = substr($acct, -4);
            $paymentInfo = __('translate.payment.details.method_bank', ['last4' => $last4]);
        }

        return [
            Stat::make(__('translate.payment.details.invoice'),      $invoiceNum),
            Stat::make(__('translate.payment.details.status'),       $status)->color($payment->status === 'paid' ? 'success' : 'warning'),
            Stat::make(__('translate.payment.details.payment_method'), $paymentInfo),
            Stat::make(__('translate.payment.details.created_at'),   $createdAt),
            Stat::make(__('translate.payment.details.paid_at'),      $paidAt),
            Stat::make(__('translate.payment.details.amount'),       "{$amount} {$currency}"),
        ];
    }
}
