<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Customer;
use App\Models\ProductService;
use App\Models\Payment;
use App\Filament\Resources\UserSettingResource;
use App\Filament\Resources\InvoiceResource;
use Carbon\Carbon;

class ClientsCountWidget extends BaseWidget
{
    protected function getCards(): array
    {
        $settings     = UserSettingResource::getSettings();
        $currencyCode = $settings->currency ?? 'EUR';
        $symbol       = InvoiceResource::getCurrencySymbol($currencyCode);
        $totalRevenue = Payment::where('status', 'completed')->sum('amount');
        $todayStart     = Carbon::today();
        $todayEnd       = Carbon::now();
        $yesterdayStart = Carbon::yesterday()->startOfDay();
        $yesterdayEnd   = Carbon::yesterday()->endOfDay();
        $revenueToday     = Payment::where('status', 'completed')
            ->whereBetween('paid_at', [$todayStart, $todayEnd])
            ->sum('amount');

        $revenueYesterday = Payment::where('status', 'completed')
            ->whereBetween('paid_at', [$yesterdayStart, $yesterdayEnd])
            ->sum('amount');

        $delta     = $revenueToday - $revenueYesterday;
        $sign      = $delta >= 0 ? '+' : '';
        $deltaText = "{$sign}" . number_format($delta, 2, '.', '') . " {$symbol}";

        $clientsCount = Customer::count();
        $productsCount = ProductService::count();

        return [
            Stat::make(__('translate.dashboard.total_revenue'), number_format($totalRevenue, 2, '.', '') . " {$symbol}")
                ->description(__('translate.dashboard.change_today', ['amount' => $deltaText]))
                ->descriptionIcon(
                    $delta >= 0
                        ? 'heroicon-m-arrow-trending-up'
                        : 'heroicon-m-arrow-trending-down'
                )
                ->chart([$revenueYesterday, $revenueToday])
                ->color($delta >= 0 ? 'success' : 'danger'),

            Stat::make(__('translate.dashboard.total_clients'), $clientsCount)
                ->description(__('translate.dashboard.new_clients'))
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->chart([0, 5, 15, 30, $clientsCount])
                ->color('success'),

            Stat::make(__('translate.dashboard.total_products'), $productsCount)
                ->description(__('translate.dashboard.active_items'))
                ->descriptionIcon('heroicon-m-cube')
                ->chart([0, 10, 20, 40, $productsCount])
                ->color('primary'),
        ];
    }
}
