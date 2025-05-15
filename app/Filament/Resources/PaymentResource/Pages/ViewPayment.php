<?php
// app/Filament/Resources/PaymentResource/Pages/ViewPayment.php

namespace App\Filament\Resources\PaymentResource\Pages;

use App\Filament\Resources\PaymentResource;
use App\Filament\Resources\PaymentResource\Widgets\PaymentDetails;
use Filament\Resources\Pages\ViewRecord;

class ViewPayment extends ViewRecord
{
    protected static string $resource = PaymentResource::class;
    public function getHeaderWidgets(): array
    {
        return [
            PaymentDetails::class,
        ];
    }

    public function getBreadcrumbs(): array
    {
        return [];
    }

    public function getTitle(): string
    {
        // LT: 'Apmokėjimo peržiūra', EN: 'View Payment'
        return __('translate.payment.pages.view');
    }
}
