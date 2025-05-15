<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InvoiceResource\Pages;
use App\Models\Invoice;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use App\Mail\InvoiceMail;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Forms\Components\Actions as FormActions;
use Filament\Forms\Components\Actions\Action as FormAction;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextArea;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Actions\DeleteBulkAction;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Get;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;
use App\Models\ProductService;
use App\Models\MeasurementUnit;
use App\Models\Customer;
use Filament\Forms\Set;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = null;
    protected static ?int    $navigationSort  = 1;

    public static function getNavigationLabel(): string
    {
        return __('translate.invoice.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('translate.invoice.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('translate.invoice.model_label_plural');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                FormActions::make([
                    FormAction::make('send')
                        ->label(__('translate.invoice.actions.send'))
                        ->icon('heroicon-o-envelope')
                        ->action(function (Invoice $record) {
                            if (!$record->public_token) {
                                $record->public_token = Str::random(32);
                                $record->save();
                            }
                            $record->update(['status' => 'sent']);

                            $email = $record->customer->email ?? null;
                            if ($email) {
                                Mail::to($email)
                                    ->send(new InvoiceMail(
                                        $record,
                                        route('invoices.public-view', ['invoice' => $record->id, 'token' => $record->public_token])
                                    ));
                                Notification::make()
                                    ->title(__('translate.invoice.notifications.sent'))
                                    ->success()
                                    ->send();
                            } else {
                                Notification::make()
                                    ->title('Nepavyko išsiųsti: kliento el. paštas nerastas')
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->requiresConfirmation()
                        ->visible(fn($record) => $record && $record->status === 'draft'),

                    FormAction::make('public_view')
                        ->label('URL')
                        ->icon('heroicon-o-link')
                        ->url(
                            fn(Invoice $record) =>
                            $record && $record->public_token
                                ? route('invoices.public-view', [
                                    'invoice' => $record->id,
                                    'token' => $record->public_token,
                                ])
                                : '#'
                        )
                        ->openUrlInNewTab()
                        ->visible(fn($record) => $record && $record->public_token),
                    FormAction::make('preview_pdf')
                        ->label(__('translate.invoice.actions.pdf'))
                        ->icon('heroicon-o-eye')
                        ->url(fn($record) => $record ? route('invoices.pdf', ['invoice' => $record->id]) : '#')
                        ->openUrlInNewTab()
                        ->visible(fn($record) => $record !== null),
                ])->columnSpan('full'),

                Grid::make(12)
                    ->schema([
                        Select::make('status')
                            ->label(__('translate.invoice.form.status'))
                            ->options(__('translate.invoice.form.status_options'))
                            ->default('draft')
                            ->visible(fn($record) => $record !== null)
                            ->required()
                            ->columnSpan(2),
                    ])
                    ->columnSpan('full'),
                Grid::make(4)
                    ->schema([
                        Select::make('customer_id')
                            ->label(__('translate.offer.form.customer'))
                            ->required()
                            ->searchable()
                            ->preload()
                            ->options(fn() => \App\Models\Customer::orderByDesc('customer_id')->limit(50)->pluck('company_name', 'customer_id')->toArray())
                            ->getSearchResultsUsing(
                                function (string $search) {
                                    return \App\Models\Customer::query()
                                        ->where('company_name', 'like', "%{$search}%")
                                        ->orWhere('company_name', 'like', "%{$search}%")
                                        ->limit(50)
                                        ->pluck('company_name', 'customer_id')
                                        ->toArray();
                                }
                            )
                            ->getOptionLabelUsing(
                                fn($value): string => \App\Models\Customer::find($value)?->company_name ?? ''
                            ),

                        TextInput::make('invoice_number')
                            ->label(__('translate.invoice.form.invoice_number'))
                            ->default(function () {
                                $settings = UserSettingResource::getSettings();

                                if (isset(
                                    $settings->invoice_prefix,
                                    $settings->invoice_number_format,
                                    $settings->invoice_counter
                                )) {
                                    $replacements = [
                                        '{prefix}'  => $settings->invoice_prefix,
                                        '{random}'  => strtoupper(Str::random(6)),
                                        '{date}'    => now()->format('Ymd'),
                                        '{counter}' => str_pad($settings->invoice_counter, 3, '0', STR_PAD_LEFT),
                                    ];

                                    return str_replace(
                                        array_keys($replacements),
                                        array_values($replacements),
                                        $settings->invoice_number_format
                                    );
                                }

                                return 'INV-' . now()->format('Ymd') . '-' . strtoupper(Str::random(4));
                            })
                            ->dehydrated(),

                        DatePicker::make('issue_date')
                            ->label(__('translate.invoice.form.issue_date'))
                            ->required()
                            ->default(now())
                            ->displayFormat('Y-m-d')
                            ->format('Y-m-d')
                            ->native(false),

                        DatePicker::make('due_date')
                            ->label(__('translate.invoice.form.due_date'))
                            ->required()
                            ->default(
                                fn() => now()
                                    ->addDays(Auth::user()?->settings->default_due_date ?? config('invoice.default_due_date', 14))
                                    ->format('Y-m-d')
                            )
                            ->displayFormat('Y-m-d')
                            ->format('Y-m-d')
                            ->native(false),
                    ])
                    ->columnSpan('full'),

                Repeater::make('items')
                    ->relationship()
                    ->live()
                    ->reactive()
                    ->defaultItems(1)
                    ->label(__('translate.invoice.form.items'))
                    ->addActionLabel(__('translate.invoice.form.add_item'))
                    ->schema([
                        Grid::make(24)->schema([
                            Hidden::make('item_id'),
                            Hidden::make('name'),
                            Select::make('product_service_id')
                                ->label(__('translate.offer.form.product_service'))
                                ->options(ProductService::query()->pluck('name', 'product_service_id'))
                                ->searchable()
                                ->preload()
                                ->reactive()
                                ->afterStateUpdated(function ($state, $set, $get) {
                                    if (!$state) {
                                        $set('name', '');
                                        $set('description', '');
                                        $set('price', 0);
                                        $set('tax_rate', OfferResource::getUserDefaultTaxRate());
                                        $set('total_price', 0);
                                        $set('unit_id', null);
                                        return;
                                    }
                                    $p = ProductService::find($state);
                                    if ($p) {
                                        $set('name', $p->name);
                                        $set('description', $p->description);
                                        $set('price', $p->price_without_vat);
                                        $tax  = $p->tax_rate ?? OfferResource::getUserDefaultTaxRate();
                                        $set('tax_rate', $tax);
                                        $set('unit_code', $p->unit?->code ?? '');
                                        $qty  = $get('quantity') ?: 1;
                                        $gross = $p->price_without_vat * $qty * (1 + (float)$tax);
                                        $set('total_price', round($gross, 2));

                                        app(\App\Services\OfferService::class)
                                            ->calculateFormTotals($set, $get);
                                    }
                                })
                                ->columnSpan(6),

                            TextInput::make('description')->label(__('translate.offer.form.description'))->columnSpan(3)
                                ->default(fn(Get $get) => $get('description'))
                                ->disabled(fn(Get $get): bool => ! (bool) $get('product_service_id')),

                            Hidden::make('unit_code')
                                ->dehydrated()
                                ->dehydrateStateUsing(
                                    fn(Get $get): ?string =>
                                    ProductService::find($get('product_service_id'))?->unit?->code
                                ),

                            TextInput::make('quantity')
                                ->label(__('translate.offer.form.quantity'))
                                ->numeric()
                                ->lazy()
                                ->default(fn(Get $get) => $get('quantity') ?? 1)
                                ->minValue(1)
                                ->maxValue(fn(Get $get) => ProductService::find($get('product_service_id'))?->stock_quantity ?? 0)
                                ->disabled(fn(Get $get) => ! filled($get('product_service_id')))
                                ->helperText(fn(Get $get) => match (true) {
                                    ! filled($get('product_service_id')) => null,
                                    ($stock = ProductService::find($get('product_service_id'))?->stock_quantity) <= 0
                                    => __('This item is out of stock.'),
                                    default => __(':count Available.', ['count' => $stock]),
                                })
                                ->rules(fn(Get $get) => [
                                    'required',
                                    'numeric',
                                    'min:1',
                                    'max:' . (
                                        ProductService::find($get('product_service_id'))?->stock_quantity
                                        ?? 0
                                    ),
                                ])
                                ->suffix(fn(Get $get) => $get('unit_code') ?? '')
                                ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                    $quantity = is_numeric($state) ? max(1, (int) $state) : 1;
                                    $set('quantity', $quantity);

                                    $price   = $get('price') ?? 0;
                                    $taxRate = $get('tax_rate') === '-' ? 0 : (float) $get('tax_rate');
                                    $total   = $price * $quantity * (1 + $taxRate);
                                    $set('total_price', round($total, 2));

                                    app(\App\Services\OfferService::class)->calculateFormTotals($set, $get);
                                })
                                ->columnSpan(3),
                            Hidden::make('unit_code')
                                ->dehydrated()
                                ->dehydrateStateUsing(
                                    fn(Get $get): ?string =>
                                    ProductService::find($get('product_service_id'))?->unit?->code
                                ),

                            TextInput::make('price')
                                ->label(__('translate.offer.form.price'))
                                ->numeric()
                                ->lazy()
                                ->rules(['numeric', 'min:0'])
                                ->default(fn(Get $get) => $get('price') ?? 0)
                                ->suffix(fn() => static::getCurrencySymbol(
                                    UserSettingResource::getSettings()['currency'] ?? 'EUR'
                                ))
                                ->disabled(
                                    fn(Get $get): bool =>
                                    ! $get('product_service_id')
                                )
                                ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                    $price = is_numeric($state) ? max(0, (float) $state) : 0;
                                    $set('price', $price);
                                    $quantity = $get('quantity') ?? 1;
                                    $taxRate  = $get('tax_rate') === '-' ? 0 : (float) $get('tax_rate');
                                    $total    = $price * $quantity * (1 + $taxRate);
                                    $set('total_price', round($total, 2));
                                    app(\App\Services\OfferService::class)->calculateFormTotals($set, $get);
                                })
                                ->columnSpan(3),

                            TextInput::make('discount_value')
                                ->label(__('translate.offer.form.discount_value'))
                                ->numeric()
                                ->disabled(fn(Get $get): bool => ! (bool) $get('product_service_id'))
                                ->minValue(0)
                                ->maxValue(100)
                                ->reactive()
                                ->lazy()
                                ->default(0)
                                ->rules(['numeric', 'min:0', 'max:100'])
                                ->suffix(
                                    fn(Get $get): string =>
                                    $get('discount_type') === 'percent'
                                        ? '%'
                                        : OfferResource::getCurrencySymbol()
                                )
                                ->afterStateUpdated(function ($state, $set, $get) {
                                    $value   = is_numeric($state) ? (float) $state : 0;
                                    $clamped = min(max($value, 0), 100);
                                    $set('discount_value', $clamped);
                                    app(\App\Services\OfferService::class)
                                        ->calculateFormTotals($set, $get);
                                })
                                ->columnSpan(3),

                            Select::make('discount_type')
                                ->label(__('translate.offer.form.discount_type'))
                                ->disabled(fn(Get $get): bool => ! (bool) $get('product_service_id'))
                                ->reactive()
                                ->selectablePlaceholder(false)
                                ->options([
                                    'percent' => '%',
                                    'fixed'   => OfferResource::getCurrencySymbol(),
                                ])
                                ->default('percent')
                                ->afterStateHydrated(function ($state, Set $set, Get $get) {
                                    app(\App\Services\OfferService::class)->calculateFormTotals($set, $get);
                                })
                                ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                    app(\App\Services\OfferService::class)->calculateFormTotals($set, $get);
                                })
                                ->columnSpan(2),

                            Select::make('tax_rate')
                                ->label(__('translate.offer.form.tax'))
                                ->disabled(fn(Get $get): bool => ! (bool) $get('product_service_id'))
                                ->selectablePlaceholder(false)
                                ->options(function () {
                                    $settings = UserSettingResource::getSettings();
                                    $rates = $settings?->tax_rates ?? ['0.21' => '21%'];
                                    $default = (string) ($settings?->default_tax_rate ?? '0.21');

                                    if (! array_key_exists($default, $rates)) {
                                        $rates[$default] = ((float) $default * 100) . '%';
                                    }

                                    ksort($rates, SORT_NUMERIC);


                                    return collect($rates)
                                        ->mapWithKeys(fn($label, $value) => [(string) $value => $label])
                                        ->unique()
                                        ->toArray();
                                })
                                ->default(fn() => OfferResource::getUserDefaultTaxRate())
                                ->reactive()
                                ->afterStateUpdated(function ($state, $set, $get) {
                                    $price = (float)($get('price') ?? 0);
                                    $quantity = (int)($get('quantity') ?? 1);
                                    $taxRate = $state === '-' ? 0 : (float)$state;
                                    $gross = $price * $quantity * (1 + $taxRate);

                                    $discount = (float)($get('discount_value') ?? 0);
                                    $discountType = $get('discount_type') ?? 'percent';
                                    if ($discountType === 'percent') {
                                        $net = $gross * (1 - ($discount / 100));
                                    } else {
                                        $net = $gross - $discount;
                                    }
                                    $set('total_price', max(0, round($net, 2)));
                                    app(\App\Services\OfferService::class)->calculateFormTotals($set, $get);
                                })
                                ->columnSpan(2),

                            Placeholder::make('total_price')
                                ->hiddenLabel()
                                ->content(fn(Get $get): HtmlString => new HtmlString(sprintf(
                                    '<div class="flex flex-col items-start gap-4">
            <span class="font-medium">%s</span>
            <span class="text-sm">%s&nbsp;%s</span>
        </div>',
                                    __('translate.offer.form.amount'),
                                    number_format(
                                        (
                                            $id = $get('product_service_id')
                                            and ($qty = ($get('quantity') ?? 0)) > 0
                                            and ($stock = ProductService::find($id)?->stock_quantity ?? 0) > 0
                                        )
                                            ? (float) $get('total_price')
                                            : 0,
                                        2,
                                        '.',
                                        ''
                                    ),
                                    OfferResource::getCurrencySymbol(),
                                )))
                                ->columnSpan(2),
                        ]),
                    ])
                    ->columns(1)
                    ->defaultItems(1)
                    ->columnSpanFull(),

                Hidden::make('subtotal')
                    ->default(0)
                    ->reactive()
                    ->dehydrated(true),

                Hidden::make('tax_amount')
                    ->default(0)
                    ->reactive()
                    ->dehydrated(true),

                Hidden::make('total_with_vat')
                    ->default(0)
                    ->reactive()
                    ->dehydrated(true),

                Grid::make(12)
                    ->schema([
                        Grid::make(1)
                            ->schema([
                                Placeholder::make('subtotal_display')
                                    ->hiddenLabel()
                                    ->reactive()
                                    ->content(fn(Get $get): HtmlString => new HtmlString(sprintf(
                                        '<span class="text-base font-medium">%s: %s %s</span>',
                                        __('translate.invoice.form.subtotal'),
                                        number_format(
                                            Collection::make($get('items') ?? [])
                                                ->sum(fn($item) => ($item['price'] ?? 0) * ($item['quantity'] ?? 0)),
                                            2,
                                            '.',
                                            ''
                                        ),
                                        static::getCurrencySymbol(),
                                    ))),

                                Placeholder::make('discount_amount_display')
                                    ->hiddenLabel()
                                    ->reactive()
                                    ->content(fn(Get $get): HtmlString => new HtmlString(sprintf(
                                        '<span class="text-base font-medium">%s: %s %s</span>',
                                        __('translate.invoice.form.discounts'),
                                        number_format(
                                            Collection::make($get('items') ?? [])
                                                ->sum(function ($item) {
                                                    $line = ($item['price'] ?? 0) * ($item['quantity'] ?? 0);
                                                    return ($item['discount_type'] ?? 'percent') === 'percent'
                                                        ? $line * (($item['discount_value'] ?? 0) / 100)
                                                        : min($item['discount_value'] ?? 0, $line);
                                                }),
                                            2,
                                            '.',
                                            ''
                                        ),
                                        static::getCurrencySymbol(),
                                    )))
                                    ->visible(
                                        fn(Get $get): bool =>
                                        Collection::make($get('items') ?? [])
                                            ->sum(fn($item) => (
                                                ($item['discount_type'] ?? 'percent') === 'percent'
                                                ? ($item['price'] ?? 0) * ($item['quantity'] ?? 0) * (($item['discount_value'] ?? 0) / 100)
                                                : ($item['discount_value'] ?? 0)
                                            )) > 0
                                    ),

                                Placeholder::make('net_subtotal_display')
                                    ->hiddenLabel()
                                    ->reactive()
                                    ->content(fn(Get $get): HtmlString => new HtmlString(sprintf(
                                        '<span class="text-base font-medium">%s: %s %s</span>',
                                        __('translate.invoice.form.subtotal_after_discount'),
                                        number_format(
                                            Collection::make($get('items') ?? [])
                                                ->sum(function ($item) {
                                                    $line = ($item['price'] ?? 0) * ($item['quantity'] ?? 0);
                                                    return ($item['discount_type'] ?? 'percent') === 'percent'
                                                        ? $line * (1 - ($item['discount_value'] ?? 0) / 100)
                                                        : max(0, $line - ($item['discount_value'] ?? 0));
                                                }),
                                            2,
                                            '.',
                                            ''
                                        ),
                                        static::getCurrencySymbol(),
                                    )))
                                    ->visible(
                                        fn(Get $get): bool =>
                                        Collection::make($get('items') ?? [])
                                            ->pluck('discount_value')
                                            ->contains(fn($v) => (float)$v > 0)
                                    ),

                                Placeholder::make('tax_amount_display')
                                    ->hiddenLabel()
                                    ->reactive()
                                    ->content(fn(Get $get): HtmlString => new HtmlString(sprintf(
                                        '<span class="text-base font-medium">%s: %s %s</span>',
                                        __('translate.invoice.form.tax_amount'),
                                        number_format(
                                            Collection::make($get('items') ?? [])
                                                ->sum(fn($item) => max(
                                                    0,
                                                    (($item['price'] ?? 0) * ($item['quantity'] ?? 0))
                                                        * (1 - (($item['discount_value'] ?? 0) / 100))
                                                ) * ($item['tax_rate'] ?? 0)),
                                            2,
                                            '.',
                                            ''
                                        ),
                                        static::getCurrencySymbol(),
                                    ))),

                                Placeholder::make('-')
                                    ->content(fn() => new HtmlString('<hr class="border-gray-300 my-1" />')),

                                Placeholder::make('total_with_vat_display')
                                    ->hiddenLabel()
                                    ->reactive()
                                    ->content(fn(Get $get): HtmlString => new HtmlString(sprintf(
                                        '<span class="text-xl font-semibold">%s: %s %s</span>',
                                        __('translate.invoice.form.grand_total'),
                                        number_format(
                                            Collection::make($get('items') ?? [])
                                                ->sum(function ($item) {
                                                    $price       = (float) ($item['price'] ?? 0);
                                                    $quantity    = (float) ($item['quantity'] ?? 0);
                                                    $discountVal = (float) ($item['discount_value'] ?? 0);
                                                    $discountTyp = $item['discount_type'] ?? 'percent';
                                                    $taxRate     = (float) ($item['tax_rate'] ?? 0);
                                                    $net = $price * $quantity;
                                                    if ($discountTyp === 'percent') {
                                                        $net *= 1 - ($discountVal / 100);
                                                    } else {
                                                        $net -= $discountVal;
                                                    }

                                                    return max(0, $net) * (1 + $taxRate);
                                                }),
                                            2,
                                            '.',
                                            ''
                                        ),
                                        static::getCurrencySymbol()
                                    )))
                            ])
                            ->columnSpan(6),

                        Grid::make(1)
                            ->schema([
                                Textarea::make('notes')
                                    ->label(__('translate.offer.form.notes'))
                                    ->rows(
                                        fn(Get $get): int => Collection::make($get('offerItems') ?? [])
                                            ->pluck('discount_value')
                                            ->contains(fn($value) => (float) $value > 1)
                                            ? 6 : 3
                                    )
                                    ->default(
                                        fn(Get $get) =>
                                        $get('tax_rate') === '0'
                                            ? __('translate.invoice.form.tax_exempt')
                                            : ''
                                    )
                                    ->afterStateUpdated(function ($state, $set, $get) {
                                        if ($get('tax_rate') === '0') {
                                            $set('notes', __('translate.invoice.form.tax_exempt'));
                                        }
                                    }),
                            ])
                            ->columnSpan(6),
                    ])
                    ->columnSpan('full'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('customer.company_name')
                    ->label(__('translate.invoice.table.client'))
                    ->url(fn(\App\Models\Invoice $record) => CustomerResource::getUrl(
                        'view',
                        ['record' => $record->customer_id],
                    ))
                    ->openUrlInNewTab(),
                TextColumn::make('invoice_number')
                    ->label(__('translate.invoice.table.invoice_number'))
                    ->searchable(),
                TextColumn::make('due_date')
                    ->label(__('translate.invoice.table.issue_date'))
                    ->date()
                    ->sortable(),
                TextColumn::make('status')
                    ->label(__('translate.invoice.table.status'))
                    ->badge()
                    ->formatStateUsing(fn(string $state) => __('translate.invoice.form.status_options.' . $state))
                    ->color(fn(string $state): string => match ($state) {
                        'draft'     => 'gray',
                        'sent'      => 'info',
                        'paid'      => 'success',
                        'cancelled' => 'danger',
                        default     => 'gray',
                    }),
                TextColumn::make('total_with_vat')
                    ->label(__('translate.invoice.table.total_amount'))
                    ->numeric()
                    ->money(fn(Invoice $record) => $record->currency)
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('translate.invoice.table.status'))
                    ->options(__('translate.invoice.form.status_options')),
            ])
            ->actions([
                ActionGroup::make([
                    Action::make('view_public')
                        ->label(__('translate.invoice.actions.view_public'))
                        ->icon('heroicon-o-eye')
                        ->before(fn(Invoice $record) => $record->public_token ?? $record->update([
                            'public_token' => Str::random(32),
                        ]))
                        ->url(fn(Invoice $record) => route('invoices.public-view', [
                            'invoice' => $record->id,
                            'token'   => $record->public_token,
                        ]))
                        ->openUrlInNewTab(),

                    Action::make('pdf')
                        ->label(__('translate.invoice.actions.pdf'))
                        ->icon('heroicon-o-document-arrow-down')
                        ->url(fn(Invoice $record) => route('invoices.pdf', ['invoice' => $record->id]))
                        ->openUrlInNewTab(),

                    Action::make('send')
                        ->label(__('translate.invoice.actions.send'))
                        ->action(fn(Invoice $record) => $record->update(['status' => 'sent']))
                        ->visible(fn(Invoice $record) => $record->status === 'draft')
                        ->after(
                            fn(Invoice $record) => Notification::make()
                                ->title(__('translate.invoice.notifications.sent'))
                                ->success()
                                ->send()
                        ),
                ])
                    ->iconButton()
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'       => Pages\ListInvoices::route('/'),
            'create'      => Pages\CreateInvoice::route('/create'),
            'edit'        => Pages\EditInvoice::route('/{record}/edit'),
            'public-view' => Pages\PublicInvoiceView::route('/public/{record}/{token}'),
        ];
    }

    public static function getCurrencySymbol(?string $currency = null): string
    {
        if ($currency === null) {
            $settings = \App\Filament\Resources\UserSettingResource::getSettings();
            $currency = $settings['currency'] ?? 'EUR';
        }

        return match ($currency) {
            'EUR' => '€',
            'USD' => '$',
            'GBP' => '£',
            default => $currency,
        };
    }
}
