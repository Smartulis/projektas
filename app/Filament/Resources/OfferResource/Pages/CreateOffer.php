<?php

namespace App\Filament\Resources\OfferResource\Pages;

use App\Filament\Resources\OfferResource;
use App\Filament\Resources\UserSettingResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;

class CreateOffer extends CreateRecord
{
    protected static string $resource = OfferResource::class;

    public function getBreadcrumbs(): array
    {
        return [];
    }

    public function getTitle(): string|Htmlable
    {
        return __('translate.offer.pages.create');
    }

    /**
     * Persist the new Offer, then bump the user's estimate_counter.
     *
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        // 1) Let Filament save the Offer
        $offer = parent::handleRecordCreation($data);

        // 2) Increment your counter
        if ($settings = UserSettingResource::getSettings()) {
            $settings->increment('estimate_counter');
        }

        // 3) Return the created Offer so Filament can finish up
        return $offer;
    }
}
