<?php

namespace App\Filament\Resources\ProductServiceResource\Pages;

use App\Filament\Resources\ProductServiceResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProductService extends CreateRecord
{
    protected static string $resource = ProductServiceResource::class;

    public function getBreadcrumbs(): array
    {
        return [];
    }
    
}
