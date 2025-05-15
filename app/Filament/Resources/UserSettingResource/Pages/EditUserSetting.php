<?php

namespace App\Filament\Resources\UserSettingResource\Pages;

use App\Filament\Resources\UserSettingResource;
use App\Models\UserSetting;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Contracts\Support\Htmlable;

class EditUserSetting extends EditRecord
{
    protected static string $resource = UserSettingResource::class;

    public function mount($record): void
    {
        $user = Auth::user();

        if (! $user) {
            abort(403);
        }

        $record = UserSetting::firstOrCreate(
            ['user_id' => $user->id]
        );

        parent::mount($record->getKey());
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('edit', ['record' => $this->record->getKey()]);
    }

    protected function getCancelRedirectUrl(): string
    {
        return static::getResource()::getUrl('edit', ['record' => $this->record->getKey()]);
    }

    public function getBreadcrumbs(): array
    {
        return [];
    }

    public function getTitle(): string|Htmlable
    {
        return __('translate.edit_title');
    }
}
