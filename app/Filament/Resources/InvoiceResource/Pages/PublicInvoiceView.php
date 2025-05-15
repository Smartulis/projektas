<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use App\Models\Invoice;
use Filament\Actions\Action;
use Filament\Resources\Pages\Page;

class PublicInvoiceView extends Page
{
    protected static string $resource = InvoiceResource::class;

    protected static string $view = 'filament.resources.invoice-resource.pages.public-invoice-view';

    protected static array $middleware = ['web'];

    public Invoice $record;

    public function mount($record, $token): void
    {
        $this->record = Invoice::where('id', $record)
            ->where('public_token', $token)
            ->firstOrFail();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('accept')
                ->label('Accept')
                ->color('success')
                ->action(fn() => $this->record->update(['status' => 'accepted']))
                ->visible(fn() => $this->record->status === 'sent'),

            Action::make('reject')
                ->label('Reject')
                ->color('danger')
                ->action(fn() => $this->record->update(['status' => 'rejected']))
                ->visible(fn() => $this->record->status === 'sent'),
        ];
    }
}
