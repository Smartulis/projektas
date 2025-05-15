<?php

namespace App\Filament\Resources\CustomerResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class InvoicesRelationManager extends RelationManager
{
    protected static string $relationship = 'invoices';
    protected static ?string $recordTitleAttribute = 'number';
    protected static ?string $label = 'Invoice';
    protected static ?string $pluralLabel = 'Invoices';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('status')
                    ->colors([
                        'success' => 'paid',
                        'danger'  => 'overdue',
                        'warning' => 'draft',
                        'primary' => 'sent',
                    ]),
                    Tables\Columns\TextColumn::make('invoice_number')
                    ->label('Invoice Number'),
                Tables\Columns\TextColumn::make('issue_date')->date(),
                Tables\Columns\TextColumn::make('due_date')->date(),
              
                Tables\Columns\TextColumn::make('total_with_vat')->money('usd')
                ->label('Total'),
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