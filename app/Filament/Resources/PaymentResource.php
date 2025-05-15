<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentResource\Pages;
use App\Filament\Resources\PaymentResource\Widgets\PaymentStats;
use App\Models\Payment;
use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;

class PaymentResource extends Resource
{

    public static function getNavigationLabel(): string
    {
        return __('translate.payment.navigation_label');
    }

    protected static ?string $model = Payment::class;
    protected static ?string $navigationIcon = 'heroicon-o-currency-euro';
    protected static ?int $navigationSort = 4;

    public static function getWidgets(): array
    {
        return [
            PaymentStats::class,
        ];
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('status')
                ->label(__('translate.payment.status'))
                ->badge()
                ->formatStateUsing(fn(string $state) => ucfirst(__("translate.payment.statuses.$state", [], app()->getLocale())))
                ->color(fn(string $state) => match ($state) {
                    'pending'   => 'warning',
                    'completed' => 'success',
                    'cancelled' => 'danger',
                    default     => 'secondary',
                }),

            TextColumn::make('invoice.invoice_number')
                ->label(__('translate.payment.invoice')),

            TextColumn::make('paid_at')
                ->label(__('translate.payment.paid_at'))
                ->dateTime(),

            TextColumn::make('payment_info')
                ->label(__('translate.payment.method_last4'))
                ->getStateUsing(
                    fn(Payment $record): string =>
                    $record->method === 'card'
                        ? __('translate.payment.method_card', ['last4' => $record->card_last_four])
                        : __('translate.payment.method_bank', ['last4' => substr($record->bank_account ?? '', -4)])
                ),

            TextColumn::make('amount')
                ->label(__('translate.payment.amount'))
                ->money('eur'),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListPayments::route('/'),
            'view'   => Pages\ViewPayment::route('/{record}'),
            'create' => Pages\CreatePayment::route('/create'),
            'edit'   => Pages\EditPayment::route('/{record}/edit'),
        ];
    }
}
