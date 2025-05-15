<?php

namespace App\Filament\Resources\ProductServiceResource\Pages;

use App\Filament\Resources\ProductServiceResource;
use App\Filament\Imports\ProductServiceImporter;
use App\Filament\Exports\ProductServiceExporter;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\ImportAction;
use Filament\Actions\ExportAction;
use Filament\Actions\CreateAction;

class ListProductServices extends ListRecords
{
    protected static string $resource = ProductServiceResource::class;
    public function getTitle(): string
    {
        return __('translate.product_service.pages.index');
    }
    protected function getHeaderActions(): array
    {
        return [
            ImportAction::make()
                ->label(__('translate.product_service.actions.import'))
                ->importer(ProductServiceImporter::class),

            ExportAction::make()
                ->label(__('translate.product_service.actions.export'))
                ->exporter(ProductServiceExporter::class),

            CreateAction::make()
                ->label(__('translate.product_service.actions.create'))
                ->icon('heroicon-o-plus'),
        ];
    }
    public function getBreadcrumbs(): array
    {
        return [];
    }
}
