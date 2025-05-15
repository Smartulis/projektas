<?php

namespace App\Filament\Resources;

namespace App\Filament\Resources;

use App\Filament\Resources\UserSettingResource\Pages;
use App\Models\UserSetting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\Facades\Auth;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Set;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;

class UserSettingResource extends Resource
{
    protected static ?string $model = UserSetting::class;
    protected static ?string $navigationIcon = 'heroicon-o-cog';
    protected static ?int    $navigationSort = 100;
    protected static bool $shouldRegisterNavigation = true;

    public static function getNavigationUrl(): string
    {

        $settings = UserSetting::firstOrCreate([
            'user_id' => Auth::user()->id,
        ]);

        return static::getUrl('edit', [
            'record' => $settings->getKey(),
        ]);
    }

    public static function getNavigationLabel(): string
    {
        return __('translate.navigation_label');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('SettingsTabs')
                    ->columnSpanFull()
                    ->extraAttributes(['class' => 'p-6 space-y-8'])
                    ->tabs([
                        Tab::make('General')
                            ->icon('heroicon-o-cog')
                            ->schema([
                                Section::make('Core Settings')
                                    ->schema([
                                        Forms\Components\Hidden::make('user_id')
                                            ->default(Auth::id()),

                                        Grid::make(2)
                                            ->schema([
                                                Select::make('currency')
                                                    ->label(__('translate.default_currency'))
                                                    ->selectablePlaceholder(false)
                                                    ->options(__('translate.options.currencies'))
                                                    ->default('EUR')
                                                    ->required(),

                                                Select::make('language')
                                                    ->label(__('translate.language'))
                                                    ->selectablePlaceholder(false)
                                                    ->options(__('translate.options.languages'))
                                                    ->default(fn() => Auth::user()?->settings->language ?? 'en')
                                                    ->required(),
                                            ])
                                            ->extraAttributes(['class' => 'gap-6']),

                                        Grid::make(2)
                                            ->schema([
                                                Select::make('payment_terms')
                                                    ->label(__('translate.payment_terms'))
                                                    ->options(__('translate.options.payment_terms'))
                                                    ->nullable()
                                                    ->required()
                                                    ->selectablePlaceholder(false)
                                                    ->default('100_0'),

                                                Select::make('default_valid_until')
                                                    ->label(__('translate.default_valid_until'))
                                                    ->options(__('translate.options.days'))
                                                    ->selectablePlaceholder(false)
                                                    ->default(fn() => Auth::user()?->settings->default_valid_until ?? 7)
                                                    ->required(),
                                            ])
                                            ->extraAttributes(['class' => 'gap-6']),

                                        Grid::make(2)
                                            ->schema([
                                                Select::make('default_due_date')
                                                    ->label(__('translate.default_due_date'))
                                                    ->options(__('translate.options.days'))
                                                    ->selectablePlaceholder(false)
                                                    ->default(fn() => Auth::user()?->settings->default_due_date ?? 14)
                                                    ->required(),

                                                Select::make('default_tax_rate')
                                                    ->label(__('translate.offer.form.tax'))
                                                    ->selectablePlaceholder(false)
                                                    ->required()
                                                    ->options(function () {
                                                        $settings = Auth::user()->settings;
                                                        $rawRates = $settings->tax_rates ?? [];
                                                        if (is_string($rawRates)) {
                                                            $rawRates = json_decode($rawRates, true) ?: [];
                                                        }
                                                        $normalized = [];
                                                        foreach ($rawRates as $key => $label) {
                                                            if ((float)$key === 0.0) {
                                                                $normalized['0'] = $label;
                                                            } else {
                                                                $normalized[(string)$key] = $label;
                                                            }
                                                        }
                                                        if (! array_key_exists('0', $normalized)) {
                                                            $normalized['0'] = '0%';
                                                        }
                                                        ksort($normalized, SORT_NUMERIC);
                                                        return $normalized;
                                                    })
                                                    ->default(fn() => Auth::user()?->settings->default_tax_rate ?? '0')
                                                    ->afterStateHydrated(fn($state, Set $set) => $state === null ? $set('default_tax_rate', '0') : null)
                                                    ->searchable()
                                                    ->createOptionForm([
                                                        TextInput::make('value')
                                                            ->label(__('translate.user.tax_rate_value'))
                                                            ->numeric()
                                                            ->required()
                                                            ->minValue(0)
                                                            ->maxValue(100)
                                                            ->suffix('%'),
                                                    ])
                                                    ->createOptionUsing(function (array $data) {
                                                        $settings = Auth::user()->settings;
                                                        $rates    = $settings->tax_rates ?? [];
                                                        $value    = floatval($data['value']) / 100;
                                                        $label    = $data['value'] . '%';
                                                        $rates[(string)$value] = $label;
                                                        ksort($rates, SORT_NUMERIC);
                                                        $settings->update(['tax_rates' => $rates]);
                                                        return (string)$value;
                                                    })
                                                    ->createOptionAction(fn($action) => $action->icon('heroicon-o-plus')->label('')),
                                            ])
                                            ->extraAttributes(['class' => 'gap-6']),
                                    ]),

                                Section::make('Estimate Numbering')
                                    ->schema([
                                        Grid::make(3)
                                            ->schema([
                                                TextInput::make('estimate_prefix')
                                                    ->label(__('translate.estimate_prefix'))
                                                    ->default('EST')
                                                    ->required(),

                                                Select::make('estimate_number_format')
                                                    ->label(__('translate.estimate_number_format'))
                                                    ->options([
                                                        '{prefix}-{date}-{counter}' => 'EST-YYYYMMDD-001',
                                                        '{prefix}-{random}-{date}'  => 'INV-RANDOM-YYYYMMDD',
                                                        '{prefix}-{counter}-{date}' => 'EST-001-YYYYMMDD',
                                                        '{prefix}-{counter}'        => 'EST-001',
                                                    ])
                                                    ->selectablePlaceholder(false)
                                                    ->required(),

                                                TextInput::make('estimate_counter')
                                                    ->label(__('translate.estimate_counter'))
                                                    ->numeric()
                                                    ->default(1)
                                                    ->required(),
                                            ])
                                            ->extraAttributes(['class' => 'gap-6']),
                                    ]),

                                Section::make('Invoice Numbering')
                                    ->schema([
                                        Grid::make(3)
                                            ->schema([
                                                TextInput::make('invoice_prefix')
                                                    ->label(__('translate.invoice.prefix'))
                                                    ->default('INV')
                                                    ->required(),

                                                Select::make('invoice_number_format')
                                                    ->label(__('translate.invoice.number_format'))
                                                    ->options([
                                                        '{prefix}-{date}-{counter}' => 'INV-YYYYMMDD-001',
                                                        '{prefix}-{random}-{date}'  => 'INV-RANDOM-YYYYMMDD',
                                                        '{prefix}-{counter}-{date}' => 'INV-001-YYYYMMDD',
                                                        '{prefix}-{counter}'        => 'INV-001',
                                                    ])
                                                    ->selectablePlaceholder(false)
                                                    ->required(),

                                                TextInput::make('invoice_counter')
                                                    ->label(__('translate.invoice.counter'))
                                                    ->numeric()
                                                    ->default(1)
                                                    ->required(),
                                            ])
                                            ->extraAttributes(['class' => 'gap-6']),
                                    ]),
                            ]),

                        Tab::make('Company Details')
                            ->icon('heroicon-o-building-office')
                            ->schema([
                                Section::make('Company Information')
                                    ->relationship('companyDetail')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                TextInput::make('company_name')
                                                    ->label('Company Name')
                                                    ->required(),

                                                TextInput::make('company_address')
                                                    ->label('Address')
                                                    ->required(),

                                                TextInput::make('vat_code')
                                                    ->label('VAT Code')
                                                    ->required(),

                                                TextInput::make('company_code')
                                                    ->label('Company Code')
                                                    ->required(),

                                                TextInput::make('bank_account')
                                                    ->label('Bank Account')
                                                    ->required(),

                                                TextInput::make('phone_number')
                                                    ->label('Phone Number')
                                                    ->required(),
                                            ]),
                                    ]),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('ID')->sortable(),
                Tables\Columns\TextColumn::make('user_id')->label('User ID')->sortable(),
                Tables\Columns\TextColumn::make('created_at')->label('Created At')->dateTime()->sortable(),
            ])
            ->filters([])
            ->actions([]);
    }

    public static function getPages(): array
    {
        return [
            'edit'  => Pages\EditUserSetting::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getSettings(): ?\App\Models\UserSetting
    {
        return \App\Models\UserSetting::where('user_id', \Illuminate\Support\Facades\Auth::id())->first();
    }

    public function companyDetail()
    {
        return $this->hasOne(\App\Models\CompanyDetail::class, 'user_id', 'user_id');
    }
}
