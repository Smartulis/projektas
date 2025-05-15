<?php


namespace App\Filament\Resources\CustomerResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class OffersRelationManager extends RelationManager
{
    protected static string $relationship = 'offers';
    protected static ?string $recordTitleAttribute = 'number';
    protected static ?string $label = 'Offer';
    protected static null|string $pluralLabel = 'Offers';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('status'),
                Tables\Columns\TextColumn::make('estimate_number'),
                Tables\Columns\TextColumn::make('date')->date()
                ->label('Issue date'),
                Tables\Columns\TextColumn::make('valid_until')->date(),
                Tables\Columns\TextColumn::make('total_with_vat')->money('usd'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }
}
