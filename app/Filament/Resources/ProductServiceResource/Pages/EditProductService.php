<?php

namespace App\Filament\Resources\ProductServiceResource\Pages;

use App\Filament\Resources\ProductServiceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;

class EditProductService extends EditRecord
{
    protected static string $resource = ProductServiceResource::class;


    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    public function getTitle(): string|Htmlable
    {
        return __('translate.product_service.pages.edit');
    }

    public function getBreadcrumbs(): array
    {
        return [];
    }
}
