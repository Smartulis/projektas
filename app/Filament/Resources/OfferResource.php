<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OfferResource\Pages;
use App\Models\Offer;
use Filament\Forms\Components\Placeholder;
use App\Models\MeasurementUnit;
use App\Models\Customer;
use Filament\Forms\Form;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use App\Filament\Resources\OfferResource\Pages\EditOffer;
use Filament\Resources\Resource;
use Filament\Tables;
use Illuminate\Support\Facades\DB;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\ActionGroup;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Actions as FormActions;
use Filament\Forms\Components\Actions\Action as FormAction;
use Filament\Notifications\Notification;
use App\Mail\OfferMail;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use App\Models\ProductService;
use Illuminate\Support\Facades\URL;


class OfferResource extends Resource
{
    protected static ?string $model = Offer::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?int $navigationSort = 2;
    protected static ?array $userSettings = null;
    public static function getNavigationLabel(): string
    {
        return __('translate.offer.navigation_label');
    }

    public static function getModel(): string
    {
        return static::$model;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                FormActions::make([

                    FormAction::make('send')
                        ->label(__('translate.offer.actions.send'))
                        ->icon('heroicon-o-envelope')
                        ->requiresConfirmation()
                        ->visible(fn($record) => $record !== null)
                        ->disabled(fn(Offer $record): bool => $record->status !== 'created')
                        ->tooltip(
                            fn(Offer $record) => $record->status === 'created'
                                ? __('translate.offer.actions.send_tooltip')
                                : __('translate.offer.actions.already_sent_tooltip')
                        )
                        ->action(function ($livewire, Offer $record) {
                            $record->update(['status' => 'sent']);

                            $record->load('customer');

                            $publicUrl = route('offers.public-view', [
                                $record->getKey(),
                                $record->public_token,
                            ]);
                            Mail::to($record->customer->email)
                                ->send(new OfferMail($record, $publicUrl));

                            Notification::make()
                                ->title(__('translate.offer.notifications.sent'))
                                ->success()
                                ->send();

                            redirect()->route('filament.admin.resources.offers.edit', ['record' => $record]);
                        }),

                    FormAction::make('convert_to_invoice')
                        ->label(__('translate.offer.actions.convert_to_invoice'))
                        ->icon('heroicon-o-document-currency-dollar')
                        ->requiresConfirmation()
                        ->action(function ($livewire, Offer $record) {
                            $record->convertToInvoice();

                            if ($record->invoice_id) {
                                Notification::make()
                                    ->title(__('translate.offer.notifications.converted_to_invoice'))
                                    ->success()
                                    ->send();

                                redirect()->route('filament.admin.resources.invoices.edit', ['record' => $record->invoice_id]);
                            } else {
                                Notification::make()
                                    ->title(__('translate.offer.notifications.invoice_conversion_failed'))
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->visible(fn($record) => $record !== null)
                        ->disabled(fn($record) => $record === null || $record->status === 'converted'),

                    FormAction::make('duplicate')
                        ->label(__('translate.offer.actions.duplicate'))
                        ->icon('heroicon-o-document-duplicate')
                        ->requiresConfirmation()
                        ->visible(fn($record) => $record !== null)
                        ->action(function ($livewire, Offer $record) {
                            $record->load('offerItems');
                            DB::beginTransaction();
                            try {
                                $originalNumber = $record->estimate_number;
                                $newNumber = self::generateNextOfferNumber($originalNumber);

                                $newOffer = $record->replicate();
                                $newOffer->status = 'created';
                                $newOffer->estimate_number = $newNumber;
                                $newOffer->public_token = Str::uuid();
                                $newOffer->save();

                                foreach ($record->offerItems as $item) {
                                    $clone = $item->replicate();
                                    $clone->offer_id = $newOffer->offer_id;
                                    $clone->save();
                                }

                                DB::commit();

                                Notification::make()
                                    ->title(__('translate.offer.actions.duplicate_success'))
                                    ->success()
                                    ->send();

                                return redirect()->to(static::getUrl('edit', ['record' => $newOffer->offer_id]));
                            } catch (\Exception $e) {
                                DB::rollBack();

                                Notification::make()
                                    ->title(__('translate.offer.actions.duplicate_error'))
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->send();

                                report($e);
                            }
                        }),

                    FormAction::make('public_view')
                        ->label(__('translate.offer.actions.public_view'))
                        ->icon('heroicon-o-link')
                        ->url(
                            fn(Offer $record) =>
                            URL::temporarySignedRoute(
                                'offers.public-view',
                                now()->addDays(7),
                                [
                                    'offer' => $record,
                                    'token' => $record->public_token,
                                ],
                            )
                        )
                        ->openUrlInNewTab()
                        ->visible(fn(?Offer $record) => $record !== null && $record->public_token),

                    FormAction::make('preview_pdf')
                        ->label(__('translate.offer.actions.pdf'))
                        ->icon('heroicon-o-eye')
                        ->url(fn($record) => $record ? $record->getPdfPreviewUrl() : '#')
                        ->openUrlInNewTab()
                        ->visible(fn($record) => $record !== null),

                    FormAction::make('delete')
                        ->label(__('translate.offer.actions.delete'))
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->visible(fn(?Offer $record) => $record !== null)
                        ->action(function ($livewire, Offer $record) {
                            $record->delete();

                            Notification::make()
                                ->title(__('translate.offer.notifications.deleted'))
                                ->success()
                                ->send();

                            redirect()->route('filament.admin.resources.offers.index');
                        }),

                ])->columnSpan('full')
                    ->fullWidth(),

                Grid::make(12)
                    ->schema([
                        Select::make('status')
                            ->label(__('translate.offer.form.status'))
                            ->options([
                                'created'   => __('translate.offer.statuses.created'),
                                'sent'      => __('translate.offer.statuses.sent'),
                                'viewed'    => __('translate.offer.statuses.viewed'),
                                'accepted'  => __('translate.offer.statuses.accepted'),
                                'rejected'  => __('translate.offer.statuses.rejected'),
                                'converted' => __('translate.offer.statuses.converted'),
                            ])
                            ->reactive()
                            ->selectablePlaceholder(false)
                            ->afterStateUpdated(fn($state, callable $set) => $set('status', $state))
                            ->visible(fn($livewire) => $livewire instanceof EditOffer)
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

                        TextInput::make('estimate_number')
                            ->label(__('translate.offer.form.estimate_number'))
                            ->default(function () {
                                $settings = UserSettingResource::getSettings();
                                if (isset($settings['estimate_prefix'], $settings['estimate_number_format'])) {
                                    $replacements = [
                                        '{prefix}' => $settings['estimate_prefix'],
                                        '{random}' => strtoupper(Str::random(6)),
                                        '{date}'   => now()->format('Ymd'),
                                        '{counter}' => str_pad($settings['estimate_counter'] ?? 1, 3, '0', STR_PAD_LEFT),
                                    ];

                                    return str_replace(
                                        array_keys($replacements),
                                        array_values($replacements),
                                        $settings['estimate_number_format']
                                    );
                                }
                                return 'EST-' . strtoupper(Str::random(6)) . '-' . now()->format('Ymd');
                            }),

                        DatePicker::make('date')
                            ->label(__('translate.offer.form.date'))
                            ->required()
                            ->default(now())
                            ->displayFormat('Y-m-d')
                            ->format('Y-m-d')
                            ->native(false),

                        DatePicker::make('valid_until')
                            ->label(__('translate.offer.form.valid_until'))
                            ->required()
                            ->default(
                                fn() => now()
                                    ->addDays(
                                        Auth::user()?->settings->default_valid_until
                                            ?? config('offer.default_valid_until', 14)
                                    )
                                    ->format('Y-m-d')
                            )
                            ->displayFormat('Y-m-d')
                            ->format('Y-m-d')
                            ->native(false),
                    ])
                    ->columnSpan('full'),

                Repeater::make('offerItems')
                    ->relationship('offerItems')
                    ->live()
                    ->reactive()
                    ->afterStateHydrated(function ($state, Set $set, Get $get) {
                        if (filled($state)) {
                            app(\App\Services\OfferService::class)->calculateFormTotals($set, $get);
                        }
                    })

                    ->label(__('translate.offer.form.items'))
                    ->addActionLabel(__('translate.offer.form.add_item'))
                    ->afterStateHydrated(function ($state, Set $set, Get $get) {
                        if (filled($state)) {
                            app(\App\Services\OfferService::class)->calculateFormTotals($set, $get);
                        }
                    })
                    ->schema([
                        Grid::make(24)->schema([
                            Hidden::make('id'),
                            Hidden::make('name'),
                            Select::make('product_service_id')
                                ->label(__('translate.offer.form.product_service'))
                                ->relationship('productService', 'name')
                                ->searchable()
                                ->required()
                                ->preload()
                                ->reactive()
                                ->afterStateUpdated(function ($state, $set, $get) {
                                    if (!$state) {
                                        $set('name', '');
                                        $set('description', '');
                                        $set('price', 0);
                                        $set('tax_rate', static::getUserDefaultTaxRate());
                                        $set('total_price', 0);
                                        $set('unit_code', '');
                                        return;
                                    }

                                    $productService = ProductService::find($state);
                                    if ($productService) {
                                        $set('name', $productService->name);
                                        $set('description', $productService->description);
                                        $set('price', $productService->price_without_vat);
                                        $set('unit_id', $productService->unit_id);
                                        $taxRate = $productService->tax_rate ?? static::getUserDefaultTaxRate();
                                        $set('tax_rate', $taxRate);
                                        $set('unit_code', $productService->unit?->code ?? '');
                                        $quantity = $get('quantity') ?? 1;
                                        $totalPrice = $productService->price_without_vat * $quantity * (1 + (float)$taxRate);
                                        $set('total_price', round($totalPrice, 2));

                                        app(\App\Services\OfferService::class)->calculateFormTotals($set, $get);
                                    }
                                })
                                ->columnSpan(5),

                            TextInput::make('description')->label(__('translate.offer.form.description'))->columnSpan(4)
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
                                    => __('translate.offer.item_out_of_stock'),
                                    default => __('translate.offer.item_available_stock', ['count' => $stock]),
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
                                ->suffix(fn(Get $get) => $get('unit_code')) // <-- ČIA PAKEITIMAS!
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
                                ->dehydrated(),

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
                                        // only use the calculated total if the product exists,
                                        // quantity > 0, AND the product’s stock > 0
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
                    ->mutateRelationshipDataBeforeCreateUsing(function (array $data): array {
                        $price = (float) $data['price'];
                        $quantity = (int) $data['quantity'];
                        $taxRate = $data['tax_rate'] === '-' ? 0 : (float)$data['tax_rate'];
                        $gross = $price * $quantity * (1 + $taxRate);
                        if ($data['discount_type'] === 'percent') {
                            $gross *= (1 - ((float)$data['discount_value'] / 100));
                        } else {
                            $gross -= (float)$data['discount_value'];
                        }
                        $data['total_price'] = max(0, round($gross, 2));
                        return $data;
                    })
                    ->mutateRelationshipDataBeforeSaveUsing(function (array $data): array {
                        $price = (float) $data['price'];
                        $quantity = (int) $data['quantity'];
                        $taxRate = $data['tax_rate'] === '-' ? 0 : (float)$data['tax_rate'];
                        $gross = $price * $quantity * (1 + $taxRate);
                        if ($data['discount_type'] === 'percent') {
                            $gross *= (1 - ((float)$data['discount_value'] / 100));
                        } else {
                            $gross -= (float)$data['discount_value'];
                        }
                        $data['total_price'] = max(0, round($gross, 2));
                        return $data;
                    })
                    ->columns(1)
                    ->addActionLabel('Add Item')
                    ->defaultItems(1)
                    ->reorderable()
                    ->columnSpan('full')
                    ->afterStateUpdated(function ($state, $set, $get) {
                        if (is_array($state)) {
                            foreach ($state as $index => $item) {
                                $price = (float)($item['price'] ?? 0);
                                $quantity = (int)($item['quantity'] ?? 1);
                                $taxRate = ($item['tax_rate'] ?? '-') === '-' ? 0 : (float)($item['tax_rate'] ?? 0);
                                $gross = $price * $quantity * (1 + $taxRate);
                                $discount = (float)($item['discount_value'] ?? 0);
                                $discountType = $item['discount_type'] ?? 'percent';
                                if ($discountType === 'percent') {
                                    $net = $gross * (1 - ($discount / 100));
                                } else {
                                    $net = $gross - $discount;
                                }
                                $net = max(0, round($net, 2));
                                $set("offerItems.{$index}.total_price", $net);
                            }
                        }
                        app(\App\Services\OfferService::class)->calculateFormTotals($set, $get);
                    })
                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                        if (is_array($state)) {
                            foreach ($state as $index => $item) {
                                $price = (float)($item['price'] ?? 0);
                                $quantity = (int)($item['quantity'] ?? 1);
                                $taxRate = ($item['tax_rate'] ?? '-') === '-' ? 0 : (float)($item['tax_rate'] ?? 0);
                                $gross = $price * $quantity * (1 + $taxRate);
                                $discount = (float)($item['discount_value'] ?? 0);
                                $discountType = $item['discount_type'] ?? 'percent';
                                if ($discountType === 'percent') {
                                    $net = $gross * (1 - ($discount / 100));
                                } else {
                                    $net = $gross - $discount;
                                }
                                $net = max(0, round($net, 2));
                                $set("offerItems.{$index}.total_price", $net);
                            }
                        }

                        app(\App\Services\OfferService::class)->calculateFormTotals($set, $get);

                        $set('subtotal_display', number_format((float) ($get('subtotal') ?? 0), 2));
                        $set('total_with_vat_display', number_format((float) ($get('total_with_vat') ?? 0), 2));
                    })
                    ->mutateRelationshipDataBeforeCreateUsing(function (array $data): array {
                        $data['total_price'] = $data['price'] * $data['quantity'] * (1 + ($data['tax_rate'] === '-' ? 0 : (float)$data['tax_rate']));
                        return $data;
                    }),

                Hidden::make('subtotal')
                    ->default(0)
                    ->reactive()
                    ->dehydrated(true)
                    ->dehydrateStateUsing(
                        fn(Get $get): float =>
                        collect($get('offerItems') ?? [])
                            ->sum(fn($item) => max(
                                0,
                                ($item['price'] ?? 0) * ($item['quantity'] ?? 1)
                                    - (
                                        ($item['discount_type'] ?? 'percent') === 'percent'
                                        ? ($item['price'] ?? 0) * ($item['quantity'] ?? 1) * (($item['discount_value'] ?? 0) / 100)
                                        : ($item['discount_value'] ?? 0)
                                    )
                            ))
                    )
                    ->afterStateHydrated(function (Get $get, Set $set) {
                        app(\App\Services\OfferService::class)->calculateFormTotals($set, $get);
                    }),

                Hidden::make('total_with_vat')
                    ->default(0)
                    ->reactive()
                    ->dehydrated(true)
                    ->dehydrateStateUsing(
                        fn(Get $get): float => collect($get('offerItems') ?? [])
                            ->sum(function ($item) {
                                $price    = (float) $item['price'];
                                $qty      = (int)   $item['quantity'];
                                $taxRate  = (float) $item['tax_rate'];
                                $discount = (float) $item['discount_value'];

                                $net = $price * $qty;
                                if ($item['discount_type'] === 'percent') {
                                    $net *= (1 - $discount / 100);
                                } else {
                                    $net -= $discount;
                                }
                                $gross = max(0, $net) * (1 + $taxRate);

                                return round($gross, 2);
                            })
                    ),
                Grid::make(12)
                    ->schema([
                        Grid::make(1)
                            ->schema([
                                Placeholder::make('subtotal_display')
                                    ->hiddenLabel()
                                    ->reactive()
                                    ->content(fn(Get $get): HtmlString => new HtmlString(sprintf(
                                        '<span class="text-base font-medium">%s: %s %s</span>',
                                        __('translate.offer.subtotal'),
                                        number_format(
                                            Collection::make($get('offerItems') ?? [])
                                                ->sum(
                                                    fn($item) => ($item['price'] ?? 0) * ($item['quantity'] ?? 1)
                                                ),
                                            2,
                                            '.',
                                            ''
                                        ),
                                        static::getCurrencySymbol()
                                    ))),

                                Placeholder::make('discount_amount_display')
                                    ->hiddenLabel()
                                    ->reactive()
                                    ->content(fn(Get $get): HtmlString => new HtmlString(sprintf(
                                        '<span class="text-base font-medium">%s: %s %s</span>',
                                        __('translate.offer.discount_label'),
                                        number_format(
                                            Collection::make($get('offerItems') ?? [])
                                                ->sum(function ($item) {
                                                    $price    = (float) ($item['price'] ?? 0);
                                                    $qty      = (int)   ($item['quantity'] ?? 1);
                                                    $lineTot  = $price * $qty;
                                                    $discVal  = (float) ($item['discount_value'] ?? 0);
                                                    $discType = $item['discount_type'] ?? 'percent';

                                                    return $discType === 'percent'
                                                        ? $lineTot * ($discVal / 100)
                                                        : min($discVal, $lineTot);
                                                }),
                                            2,
                                            '.',
                                            ''
                                        ),
                                        static::getCurrencySymbol()
                                    )))
                                    ->visible(
                                        fn(Get $get): bool =>
                                        Collection::make($get('offerItems') ?? [])
                                            ->sum(function ($item) {
                                                $price    = (float) ($item['price'] ?? 0);
                                                $qty      = (int)   ($item['quantity'] ?? 1);
                                                $lineTot  = $price * $qty;
                                                $discVal  = (float) ($item['discount_value'] ?? 0);
                                                $discType = $item['discount_type'] ?? 'percent';

                                                return $discType === 'percent'
                                                    ? $lineTot * ($discVal / 100)
                                                    : min($discVal, $lineTot);
                                            }) > 0
                                    ),

                                Placeholder::make('net_subtotal_display')
                                    ->hiddenLabel()
                                    ->reactive()
                                    ->content(fn(Get $get): HtmlString => new HtmlString(sprintf(
                                        '<span class="text-base font-medium">%s: %s %s</span>',
                                        __('translate.offer.subtotal_after_discount'),
                                        number_format(
                                            collect($get('offerItems') ?? [])
                                                ->sum(function ($item) {
                                                    $price = (float) ($item['price'] ?? 0);
                                                    $quantity = (int) ($item['quantity'] ?? 1);
                                                    $discountValue = (float) ($item['discount_value'] ?? 0);
                                                    $discountType = $item['discount_type'] ?? 'percent';

                                                    $lineTotal = $price * $quantity;

                                                    if ($discountType === 'percent') {
                                                        return $lineTotal * (1 - ($discountValue / 100));
                                                    }

                                                    return max(0, $lineTotal - $discountValue);
                                                }),
                                            2,
                                            '.',
                                            ''
                                        ),
                                        OfferResource::getCurrencySymbol()
                                    )))
                                    ->visible(
                                        fn(Get $get): bool =>
                                        collect($get('offerItems') ?? [])
                                            ->filter(fn($item) => (float)($item['discount_value'] ?? 0) > 0)
                                            ->isNotEmpty()
                                    ),

                                Placeholder::make('tax_amount_display')
                                    ->label(__('translate.offer.form.tax_amount'))
                                    ->reactive()
                                    ->hiddenLabel()
                                    ->content(fn(Get $get): HtmlString => new HtmlString(sprintf(
                                        '<span class="text-base font-medium">%s: %s %s</span>',
                                        __('translate.offer.tax_total'),
                                        number_format(
                                            Collection::make($get('offerItems') ?? [])
                                                ->sum(function ($item) {
                                                    $price       = (float) ($item['price'] ?? 0);
                                                    $quantity    = (int)   ($item['quantity'] ?? 1);
                                                    $discountVal = (float) ($item['discount_value'] ?? 0);
                                                    $discountTyp = $item['discount_type'] ?? 'percent';
                                                    $net = $price * $quantity;
                                                    if ($discountTyp === 'percent') {
                                                        $net *= (1 - $discountVal / 100);
                                                    } else {
                                                        $net -= $discountVal;
                                                    }
                                                    $net = max(0, $net);

                                                    $taxRate = (float) (($item['tax_rate'] ?? '-') === '-' ? 0 : $item['tax_rate']);
                                                    return $net * $taxRate;
                                                }),
                                            2,
                                            '.',
                                            ''
                                        ),
                                        static::getCurrencySymbol()
                                    ))),
                                Placeholder::make('-')

                                    ->content(fn() => new HtmlString(

                                        '<hr class="border-gray-300 my-1" />'
                                    )),

                                Placeholder::make('total_with_vat_display')
                                    ->hiddenLabel()
                                    ->reactive()
                                    ->content(fn(Get $get): HtmlString => new HtmlString(sprintf(
                                        '<span class="text-xl font-semibold">%s: %s %s</span>',
                                        __('translate.offer.grand_total'),
                                        number_format(
                                            Collection::make($get('offerItems') ?? [])
                                                ->sum(function ($item) {
                                                    $price       = (float) ($item['price'] ?? 0);
                                                    $quantity    = (int)   ($item['quantity'] ?? 1);
                                                    $discountVal = (float) ($item['discount_value'] ?? 0);
                                                    $discountTyp = $item['discount_type'] ?? 'percent';
                                                    $taxRate     = (float) (($item['tax_rate'] ?? '-') === '-' ? 0 : $item['tax_rate']);

                                                    $net = $price * $quantity;
                                                    if ($discountTyp === 'percent') {
                                                        $net *= (1 - $discountVal / 100);
                                                    } else {
                                                        $net -= $discountVal;
                                                    }
                                                    $net = max(0, $net);

                                                    return $net * (1 + $taxRate);
                                                }),
                                            2,
                                            '.',
                                            ''
                                        ),
                                        static::getCurrencySymbol()
                                    ))),
                            ])
                            ->columnSpan(6),

                        Grid::make(1)
                            ->schema([
                                \Filament\Forms\Components\Textarea::make('notes')
                                    ->label(__('translate.offer.form.notes'))
                                    ->rows(
                                        fn(Get $get): int => Collection::make($get('offerItems') ?? [])
                                            ->pluck('discount_value')
                                            ->contains(fn($value) => (float) $value > 0)
                                            ? 6 : 3
                                    )
                                    ->default(
                                        fn(Get $get) =>
                                        $get('tax_rate') === '0'
                                            ? 'PVM netaikomas pagal LR įstatymus.'
                                            : ''
                                    )
                                    ->afterStateUpdated(function ($state, $set, $get) {
                                        if ($get('tax_rate') === '0') {
                                            $set('notes', 'PVM netaikomas pagal LR įstatymus.');
                                        }
                                    }),
                            ])
                            ->columnSpan(6),
                    ])
                    ->columnSpan('full'),
                TextInput::make('customer_comment')
                    ->label(__('translate.offer.form.customer_note'))
                    ->disabled()
                    ->columnSpan('full')
                    ->visible(fn($get) => ! empty($get('customer_comment'))),
                Hidden::make('tax_amount')
                    ->default(0)
                    ->reactive()
                    ->dehydrated(true)
                    ->afterStateHydrated(function (Get $get, Set $set) {
                        app(\App\Services\OfferService::class)
                            ->calculateFormTotals($set, $get);
                    })
                    ->dehydrateStateUsing(function (Get $get) {
                        /** @var Collection<int, array> $items */
                        $items = collect($get('offerItems') ?? []);

                        return $items->sum(function (array $item): float {
                            $price       = (float) ($item['price'] ?? 0);
                            $quantity    = (int)   ($item['quantity'] ?? 1);
                            $discountVal = (float) ($item['discount_value'] ?? 0);
                            $taxRate     = (float) ($item['tax_rate'] ?? 0);
                            $discountType = $item['discount_type'] ?? 'percent';

                            $net = $price * $quantity;
                            if ($discountType === 'percent') {
                                $net *= 1 - ($discountVal / 100);
                            } else {
                                $net -= $discountVal;
                            }
                            $net = max(0, $net);

                            return round($net * $taxRate, 2);
                        });
                    }),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('customer.company_name')
                    ->label(__('translate.offer.table.client'))
                    ->searchable(),

                TextColumn::make('estimate_number')
                    ->label(__('translate.offer.table.estimate_number'))
                    ->searchable(),


                TextColumn::make('valid_until')
                    ->label(__('translate.offer.table.valid_until'))
                    ->date()
                    ->sortable()
                    ->color(fn($record) => $record->valid_until < now() ? 'danger' : 'gray'),
                TextColumn::make('status')
                    ->label(__('translate.offer.table.status'))
                    ->sortable()
                    ->formatStateUsing(
                        fn(string $state): string =>
                        __('translate.offer.table.filters.options.' . $state)
                    )
                    ->badge()
                    ->formatStateUsing(
                        fn(string $state, Offer $record): string =>
                        __('translate.offer.table.filters.options.' . $state)
                            . ' ' . $record->updated_at->format('Y-m-d')
                    )

                    ->color(fn(string $state): string => match ($state) {
                        'created'   => 'gray',
                        'accepted'  => 'success',
                        'rejected'  => 'danger',
                        'converted' => 'primary',
                        'viewed'    => 'warning',
                        'sent'      => 'info',
                        default     => 'gray',
                    }),
                TextColumn::make('total_with_vat')
                    ->label(__('translate.offer.table.total'))
                    ->sortable()
                    ->formatStateUsing(function ($state) {
                        $settings = UserSettingResource::getSettings();
                        $currency = $settings['currency'] ?? 'EUR';
                        $symbol = OfferResource::getCurrencySymbol($currency);
                        return number_format($state, 2, '.', '') . ' ' . $symbol;
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('translate.offer.table.filters.status'))
                    ->options(__('translate.offer.table.filters.options'))
                    ->placeholder(__('translate.offer.table.filters.placeholder')),
            ])
            ->actions([
                ActionGroup::make([
                    Action::make('convert_to_invoice')
                        ->label(__('translate.offer.actions.convert_to_invoice'))
                        ->icon('heroicon-o-document-duplicate')
                        ->action(function (\App\Models\Offer $record, $livewire) {
                            $record->convertToInvoice();
                            $livewire->resetTable();
                        })
                        ->disabled(fn(Offer $record): bool => $record->status === 'converted'),

                    Action::make('preview_pdf')
                        ->label(__('translate.offer.actions.pdf'))
                        ->icon('heroicon-o-document-arrow-down')
                        ->url(fn($record) => $record->getPdfPreviewUrl())
                        ->openUrlInNewTab(),

                    Action::make('send')
                        ->label(__('translate.offer.actions.send'))
                        ->icon('heroicon-o-envelope')
                        ->action(function (Offer $record) {
                            $record->update(['status' => 'sent']);
                            try {
                                $record->load('customer');
                                Mail::to($record->customer->email)
                                    ->send(new OfferMail(
                                        $record,
                                        route('offers.public-view', [
                                            'offer' => $record->offer_id,
                                            'token' => $record->public_token,
                                        ])
                                    ));
                                Notification::make()
                                    ->title(__('translate.offer.notifications.sent'))
                                    ->success()
                                    ->send();
                            } catch (\Throwable $e) {
                                dd($e->getMessage());
                            }
                        })
                        ->disabled(fn(Offer $record): bool => $record->status !== 'created'),
                ])
                    ->iconButton() // ← collapse into ⋮ menu
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('date', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOffers::route('/'),
            'create' => Pages\CreateOffer::route('/create'),
            'edit' => Pages\EditOffer::route('/{record}/edit'),
        ];
    }

    public static function getCurrencySymbol($currency = null): string
    {
        if ($currency === null) {
            $settings = UserSettingResource::getSettings();
            $currency = $settings['currency'] ?? 'EUR';
        }
        $symbols = [
            'EUR' => '€',
            'USD' => '$',
            'GBP' => '£',
        ];
        return $symbols[$currency] ?? '€';
    }

    public static function getUserDefaultTaxRate(): string
    {
        return UserSettingResource::getSettings()?->default_tax_rate ?? '0.21';
    }

    public static function generateNextOfferNumber(string $currentNumber): string
    {
        if (preg_match('/^(.*?)(\d+)$/', $currentNumber, $matches)) {
            $base = $matches[1];
            $counter = (int)$matches[2];
            $newCounter = str_pad($counter + 1, strlen($matches[2]), '0', STR_PAD_LEFT);
            return $base . $newCounter;
        }

        return $currentNumber . '-001';
    }
}
