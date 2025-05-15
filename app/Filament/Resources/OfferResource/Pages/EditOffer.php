<?php

namespace App\Filament\Resources\OfferResource\Pages;

use App\Filament\Resources\OfferResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;

class EditOffer extends EditRecord
{
    protected static string $resource = OfferResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getBreadcrumbs(): array
    {
        return [];
    }


    public function getTitle(): string|Htmlable
    {
        return __('translate.offer.pages.edit');
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data = parent::mutateFormDataBeforeFill($data);

        // Explicitly load the relationship
        $offer = $this->getRecord();
        $offer->load('offerItems');

        // Ensure the items are properly formatted for the repeater
        $data['offerItems'] = $offer->offerItems->map(function ($item) {
            return [
                'id' => $item->id,
                'product_service_id' => $item->product_service_id,
                'name' => $item->name,
                'description' => $item->description,
                'quantity' => $item->quantity,
                'price' => $item->price,
                'tax_rate' => $item->tax_rate,
                'total_price' => $item->total_price,
                'discount_value' => $item->discount_value,
                'discount_type' => $item->discount_type,
                'unit_id' => $item->unit_id,
            ];
        })->toArray();

        return $data;
    }

    
}
