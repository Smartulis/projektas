<?php

namespace App\Filament\Exports;

use App\Models\Customer;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class CustomerExporter extends Exporter
{
    protected static ?string $model = Customer::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('customer_id'),
            ExportColumn::make('customer_number'),
            ExportColumn::make('type'),
            ExportColumn::make('status'),
            ExportColumn::make('company_name'),
            ExportColumn::make('company_code'),
            ExportColumn::make('vat_number'),
            ExportColumn::make('payment_term'),
            ExportColumn::make('email'),
            ExportColumn::make('phone'),
            ExportColumn::make('address'),
            ExportColumn::make('website'),
            ExportColumn::make('bank_account'),
            ExportColumn::make('bank_name'),
            ExportColumn::make('payment_method'),
            ExportColumn::make('city'),
            ExportColumn::make('postal_code'),
            ExportColumn::make('country'),
            ExportColumn::make('notes'),
            ExportColumn::make('documents'),
            ExportColumn::make('created_at'),
            ExportColumn::make('updated_at'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your customer export has completed and ' . number_format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}
