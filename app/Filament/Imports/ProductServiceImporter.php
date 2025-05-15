<?php

namespace App\Filament\Imports;

use App\Models\ProductService;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Models\Import;

class ProductServiceImporter extends Importer
{
    protected static ?string $model = ProductService::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('name')
                ->label('Pavadinimas')
                ->requiredMapping()
                ->rules(['required', 'max:255']),

            ImportColumn::make('description')
                ->label('Aprašymas')
                ->nullable()
                ->rules(['max:65535']),

            ImportColumn::make('unit_id')
                ->label('Matavimo vieneto ID')
                ->requiredMapping()
                ->rules(['required', 'exists:measurement_units,id']),

            ImportColumn::make('price_without_vat')
                ->label('Kaina be PVM')
                ->requiredMapping()
                ->rules(['required', 'numeric', 'min:0']),

            ImportColumn::make('vat_rate')
                ->label('PVM tarifas (%)')
                ->requiredMapping()
                ->rules(['required', 'numeric']),

            ImportColumn::make('price_with_vat')
                ->label('Kaina su PVM')
                ->requiredMapping()
                ->rules(['required', 'numeric']),

            ImportColumn::make('currency')
                ->label('Valiuta')
                ->requiredMapping()
                ->rules(['required', 'in:EUR,USD,GBP']),

            ImportColumn::make('stock_quantity')
                ->label('Sandėlio kiekis')
                ->requiredMapping()
                ->rules(['required', 'integer', 'min:0']),

            ImportColumn::make('sku')
                ->label('SKU / Kodas')
                ->nullable()
                ->rules(['max:50']),

            ImportColumn::make('status')
                ->label('Statusas')
                ->requiredMapping()
                ->rules(['required', 'in:Active,Expired,Not Available']),

            ImportColumn::make('image')
                ->label('Nuotrauka (kelias)')
                ->nullable(),
        ];
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $successful = number_format($import->successful_rows);
        $body = "Jūsų prekių/paslaugų importas baigtas: sėkmingai importuota {$successful} " .
            str('eilutė')->plural($import->successful_rows) . '.';

        if ($failed = $import->getFailedRowsCount()) {
            $body .= ' Nepavyko importuoti ' .
                number_format($failed) . ' ' .
                str('eilutė')->plural($failed) . '.';
        }

        return $body;
    }
}
