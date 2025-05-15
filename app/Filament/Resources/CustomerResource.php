<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomerResource\Pages\CreateCustomer;
use App\Filament\Resources\CustomerResource\Pages\EditCustomer;
use App\Filament\Resources\CustomerResource\Pages\ListCustomers;
use App\Models\Customer;
use App\Filament\Resources\CustomerResource\RelationManagers\InvoicesRelationManager;
use App\Filament\Resources\CustomerResource\RelationManagers\OffersRelationManager;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Html;
use Filament\Forms\Components\Placeholder;
use Illuminate\Support\HtmlString;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use App\Filament\Resources\CustomerResource\Pages\ViewCustomer;
use Filament\Tables\Actions\ViewAction;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;
    protected static ?string $navigationIcon = 'heroicon-o-user';
    protected static ?int $navigationSort = 4;
    public static function getNavigationLabel(): string
    {
        return __('translate.customer.navigation_label');
    }
    public static function getModelLabel(): string
    {
        return __('translate.customer.model_label');
    }
    public static function getPluralModelLabel(): string
    {
        return __('translate.customer.plural_model_label');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make(__('translate.customer.sections.general'))
                    ->visibleOn(['create', 'edit'])
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('type')
                                    ->label(__('translate.customer.fields.type'))
                                    ->options([
                                        'individual' => __('translate.customer.fields.type_individual'),
                                        'company'    => __('translate.customer.fields.type_company'),
                                    ])
                                    ->reactive()
                                    ->required()
                                    ->native(false),

                                TextInput::make('company_name')
                                    ->label(fn(callable $get) => $get('type') === 'individual'
                                        ? __('translate.customer.fields.name')
                                        : __('translate.customer.fields.company_name'))
                                    ->required()
                                    ->maxLength(255),

                                TextInput::make('company_code')
                                    ->label(fn(callable $get) => $get('type') === 'individual'
                                        ? __('translate.customer.fields.personal_code')
                                        : __('translate.customer.fields.company_code'))
                                    ->maxLength(100),

                                TextInput::make('vat_number')
                                    ->label(__('translate.customer.fields.vat_number'))
                                    ->maxLength(100),
                            ]),
                    ]),

                Section::make(__('translate.customer.sections.contact'))
                    ->visibleOn(['create', 'edit'])
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('phone')
                                    ->label(__('translate.customer.fields.phone'))
                                    ->tel()
                                    ->maxLength(20),

                                TextInput::make('email')
                                    ->label(__('translate.customer.fields.email'))
                                    ->email()
                                    ->required()
                                    ->maxLength(255),

                                TextInput::make('website')
                                    ->label(__('translate.customer.fields.website'))
                                    ->url()
                                    ->maxLength(255),

                                TextInput::make('address')
                                    ->label(__('translate.customer.fields.address'))
                                    ->maxLength(500),

                                TextInput::make('postal_code')
                                    ->label(__('translate.customer.fields.postal_code'))
                                    ->maxLength(20),

                                Select::make('country')
                                    ->label(__('translate.customer.fields.country'))
                                    ->options([
                                        'LT' => __('translate.customer.countries.lt'),
                                        'LV' => __('translate.customer.countries.lv'),
                                        'EE' => __('translate.customer.countries.ee'),
                                    ])
                                    ->native(false),
                            ]),
                    ]),

                Section::make(__('translate.customer.sections.financial'))
                    ->visibleOn(['create', 'edit'])
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('bank_name')
                                    ->label(__('translate.customer.fields.bank_name'))
                                    ->maxLength(255),

                                TextInput::make('bank_account')
                                    ->label(__('translate.customer.fields.bank_account'))
                                    ->maxLength(255),

                                Select::make('payment_method')
                                    ->label(__('translate.customer.fields.payment_method'))
                                    ->options([
                                        'bank' => __('translate.customer.fields.payment_method_bank'),
                                        'card' => __('translate.customer.fields.payment_method_card'),
                                    ])
                                    ->native(false),
                            ]),
                    ]),

                Section::make(__('translate.customer.sections.additional'))
                    ->visibleOn(['create', 'edit'])
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('responsible_person')
                                    ->label(__('translate.customer.fields.responsible_person'))
                                    ->maxLength(255),

                                TextInput::make('business_field')
                                    ->label(__('translate.customer.fields.business_field'))
                                    ->maxLength(255),
                            ]),

                        FileUpload::make('documents')
                            ->visibleOn(['create', 'edit'])
                            ->label(__('translate.customer.fields.documents'))
                            ->multiple()
                            ->directory('customer-docs')
                            ->acceptedFileTypes(['.png', '.pdf'])
                            ->maxSize(2048)
                            ->columnSpanFull(),

                        Textarea::make('notes')
                            ->label(__('translate.customer.fields.notes'))
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),

                Section::make(__('translate.customer.sections.view_general'))
                    ->visibleOn('view')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Placeholder::make('company_name')
                                    ->label(__('translate.customer.fields.name'))
                                    ->content(fn($livewire) => e($livewire->getRecord()->company_name)),
                                Placeholder::make('email')
                                    ->label(__('translate.customer.fields.email'))
                                    ->content(fn($livewire) => e($livewire->getRecord()->email)),
                                Placeholder::make('phone')
                                    ->label(__('translate.customer.fields.phone'))
                                    ->content(fn($livewire) => e($livewire->getRecord()->phone)),
                                Placeholder::make('vat_number')
                                    ->label(__('translate.customer.fields.vat_number'))
                                    ->content(fn($livewire) => new HtmlString(
                                        '<a href="' . e($livewire->getRecord()->website) . '" target="_blank" class="underline">'
                                            . e($livewire->getRecord()->website) .
                                            '</a>'
                                    )),
                            ]),
                    ]),

                Section::make(__('translate.customer.sections.view_additional'))
                    ->visibleOn('view')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Placeholder::make('address')
                                    ->label(__('translate.customer.fields.address'))
                                    ->content(fn($livewire) => e($livewire->getRecord()->address)),
                                Placeholder::make('postal_code')
                                    ->label(__('translate.customer.fields.postal_code'))
                                    ->content(fn($livewire) => e($livewire->getRecord()->postal_code)),
                                Placeholder::make('country')
                                    ->label(__('translate.customer.fields.country'))
                                    ->content(fn($livewire) => e($livewire->getRecord()->country)),
                                Placeholder::make('responsible_person')
                                    ->label(__('translate.customer.fields.responsible_person'))
                                    ->content(fn($livewire) => e($livewire->getRecord()->responsible_person)),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('company_name')
                    ->label(__('translate.customer.fields.name'))
                    ->sortable()
                    ->searchable(),
                TextColumn::make('email')
                    ->label(__('translate.customer.fields.email'))
                    ->sortable()
                    ->searchable(),
                TextColumn::make('phone')
                    ->label(__('translate.customer.fields.phone'))
                    ->searchable(),
            ])
            ->actions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    DeleteAction::make(),
                ])->iconButton()
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            InvoicesRelationManager::class,
            OffersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCustomers::route('/'),
            'create' => CreateCustomer::route('/create'),
            'edit' => EditCustomer::route('/{record}/edit'),
            'view'   => ViewCustomer::route('/{record}'),
        ];
    }
}
