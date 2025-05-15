<?php

namespace App\Filament\Resources\PaymentResource\Pages;

use App\Filament\Resources\PaymentResource;
use Filament\Actions;
use App\Filament\Resources\PaymentResource\Widgets\PaymentStats;
use Filament\Resources\Pages\ListRecords;

class ListPayments extends ListRecords
{
    protected static string $resource = PaymentResource::class;
    protected function getHeaderWidgets(): array
    {
        return [
            PaymentStats::class,
        ];
    }

    public function getTitle(): string
    {
        return __('translate.payment.pages.index');
    }
    public function getBreadcrumbs(): array
    {
        return [];
    }
}
