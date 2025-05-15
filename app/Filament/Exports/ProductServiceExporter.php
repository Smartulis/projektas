<?php

namespace App\Filament\Exports;

use App\Models\ProductService;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class ProductServiceExporter extends Exporter
{
    protected static ?string $model = ProductService::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('product_service_id')
                ->label('ID'),
            ExportColumn::make('name')
                ->label('Pavadinimas'),
            ExportColumn::make('description')
                ->label('Aprašymas'),
            ExportColumn::make('unit.code')
                ->label('Vienetas (kodas)'),
            ExportColumn::make('unit.lt_name')
                ->label('Vienetas (pavadinimas)'),
            ExportColumn::make('price_without_vat')
                ->label('Kaina be PVM'),
            ExportColumn::make('vat_rate')
                ->label('PVM tarifas (%)'),
            ExportColumn::make('price_with_vat')
                ->label('Kaina su PVM'),
            ExportColumn::make('currency')
                ->label('Valiuta'),
            ExportColumn::make('stock_quantity')
                ->label('Sandėlio kiekis'),
            ExportColumn::make('sku')
                ->label('SKU / Kodas'),
            ExportColumn::make('status')
                ->label('Statusas'),
            ExportColumn::make('image')
                ->label('Nuotrauka (kelias)'),
            ExportColumn::make('created_at')
                ->label('Sukurta'),
            ExportColumn::make('updated_at')
                ->label('Atnaujinta'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Jūsų produktų/paslaugų eksportas baigtas: sėkmingai eksportuota '
            . number_format($export->successful_rows) . ' '
            . str('eilutė')->plural($export->successful_rows) . '.';

        if ($failed = $export->getFailedRowsCount()) {
            $body .= ' Nepavyko eksportuoti '
                . number_format($failed) . ' '
                . str('eilutė')->plural($failed) . '.';
        }

        return $body;
    }
}
