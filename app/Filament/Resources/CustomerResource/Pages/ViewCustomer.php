<?php

namespace App\Filament\Resources\CustomerResource\Pages;

use App\Filament\Resources\CustomerResource;
use App\Filament\Resources\CustomerResource\Widgets\CustomerPaymentStats;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions;

class ViewCustomer extends ViewRecord
{
    protected static string $resource = CustomerResource::class;

    public function getBreadcrumbs(): array
    {
        return [];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            CustomerPaymentStats::make([
                'record' => $this->getRecord(),
            ]),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label(__('translate.customer.actions.edit')),
            Actions\DeleteAction::make()
                ->label(__('translate.customer.actions.delete')),
        ];
    }
}
