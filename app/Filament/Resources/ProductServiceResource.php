<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductServiceResource\Pages;
use App\Models\ProductService;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\FileUpload;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;

use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;

class ProductServiceResource extends Resource
{
    protected static ?string $model = ProductService::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?int $navigationSort = 3;

    public static function getNavigationLabel(): string
    {
        return __('translate.product_service.navigation_label');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make(3)
                    ->schema([
                        TextInput::make('name')
                            ->label(__('translate.product_service.fields.name'))
                            ->required()
                            ->maxLength(255),

                        Select::make('unit_id')
                            ->label(__('translate.product_service.fields.unit'))
                            ->relationship('unit', 'code')
                            ->getOptionLabelFromRecordUsing(fn($record) => "{$record->code} - {$record->lt_name}")
                            ->required(),

                        TextInput::make('vat_rate')
                            ->label(__('translate.product_service.fields.vat_rate'))
                            ->required()
                            ->numeric()
                            ->default(21)
                            ->reactive()
                            ->afterStateUpdated(fn($state, callable $set, $get) =>
                                $set('price_with_vat', round($get('price_without_vat') * (1 + ($state / 100)), 2))
                            ),

                        TextInput::make('price_without_vat')
                            ->label(__('translate.product_service.fields.price_without_vat'))
                            ->required()
                            ->numeric()
                            ->reactive()
                            ->afterStateUpdated(fn($state, callable $set, $get) =>
                                $set('price_with_vat', round($state * (1 + ($get('vat_rate') / 100)), 2))
                            ),

                        TextInput::make('price_with_vat')
                            ->label(__('translate.product_service.fields.price_with_vat'))
                            ->disabled()
                            ->dehydrated()
                            ->numeric(),

                        Select::make('currency')
                            ->label(__('translate.product_service.fields.currency'))
                            ->options([
                                'EUR' => 'EUR',
                                'USD' => 'USD',
                                'GBP' => 'GBP',
                            ])
                            ->default('EUR')
                            ->required(),

                        TextInput::make('stock_quantity')
                            ->label(__('translate.product_service.fields.stock_quantity'))
                            ->numeric()
                            ->default(0)
                            ->reactive()
                            ->afterStateUpdated(function (int $state, callable $set): void {
                                if ($state === 0) {
                                    $set('status', 'Not Available');
                                }
                            }),

                        TextInput::make('sku')
                            ->label(__('translate.product_service.fields.sku'))
                            ->maxLength(50),

                        Select::make('status')
                            ->label(__('translate.product_service.fields.status'))
                            ->options([
                                'Active'        => __('translate.product_service.statuses.Active'),
                                'Expired'       => __('translate.product_service.statuses.Expired'),
                                'Not Available' => __('translate.product_service.statuses.Not Available'),
                            ])
                            ->default('Active')
                            ->required(),
                    ]),

                Grid::make(2)
                    ->schema([
                        Textarea::make('description')
                            ->label(__('translate.product_service.fields.description'))
                            ->rows(3)
                            ->maxLength(65535),

                        FileUpload::make('image')
                            ->label(__('translate.product_service.fields.image'))
                            ->image()
                            ->directory('product-service-images')
                            ->maxSize(1024)
                            ->nullable(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('product_service_id')
                    ->label(__('translate.product_service.fields.id'))
                    ->sortable(),

                TextColumn::make('name')
                    ->label(__('translate.product_service.fields.name'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('price_without_vat')
                    ->label(__('translate.product_service.fields.price_without_vat'))
                    ->formatStateUsing(fn($state) => number_format($state, 2, '.', ''))
                    ->sortable(),

                TextColumn::make('stock_quantity')
                    ->label(__('translate.product_service.fields.stock_quantity'))
                    ->sortable(),

                TextColumn::make('status')
                    ->label(__('translate.product_service.fields.status'))
                    ->badge()
                    ->formatStateUsing(fn(string $state): string =>
                        __('translate.product_service.statuses.' . $state . '_badge')
                    )
                    ->color(fn(string $state): string => match ($state) {
                        'Active'        => 'success',
                        'Expired'       => 'warning',
                        'Not Available' => 'danger',
                        default         => 'secondary',
                    })
                    ->sortable(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListProductServices::route('/'),
            'create' => Pages\CreateProductService::route('/create'),
            'edit'   => Pages\EditProductService::route('/{record}/edit'),
        ];
    }
}
