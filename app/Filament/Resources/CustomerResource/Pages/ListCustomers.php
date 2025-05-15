<?php

namespace App\Filament\Resources\CustomerResource\Pages;

use App\Filament\Resources\CustomerResource;
use App\Filament\Imports\CustomerImporter;
use App\Filament\Exports\CustomerExporter;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\ImportAction;
use Filament\Actions\ExportAction;
use Filament\Actions\CreateAction;

class ListCustomers extends ListRecords
{
    protected static string $resource = CustomerResource::class;

    public function getBreadcrumbs(): array
    {
        return [];
    }

    protected function getHeaderActions(): array
    {
        return [
            // Importuoti CSV
            ImportAction::make()
                 ->label(__('translate.customer.actions.import'))
                ->importer(CustomerImporter::class),

            // Eksportuoti
            ExportAction::make()
                ->label(__('translate.customer.actions.export'))
                ->exporter(CustomerExporter::class),

            // Sukurti klientą
            CreateAction::make()
                ->label(__('translate.customer.actions.create'))
                ->icon('heroicon-o-plus'),
        ];
    }
}
