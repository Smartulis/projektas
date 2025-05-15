<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;
use NumberToWords\NumberToWords;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;

class Offer extends Model
{
    use HasFactory;

    protected $primaryKey = 'offer_id';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'customer_id',
        'estimate_number',
        'date',
        'valid_until',
        'subtotal',
        'tax_amount',
        'total_with_vat',
        'currency',
        'status',
        'notes',
        'customer_comment',
    ];

    protected $casts = [
        'date' => 'date',
        'valid_until' => 'date',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_with_vat' => 'decimal:2',
    ];

    protected $attributes = [
        'status' => 'Created',
        'currency' => 'EUR',
    ];

    public function items()
    {
        return $this->hasMany(OfferItem::class, 'offer_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function offerItems()
    {
        return $this->hasMany(OfferItem::class, 'offer_id', 'offer_id');
    }

    public function calculateTotals(): void
    {
        $this->load('items');

        $this->subtotal = $this->items->sum(function ($item) {
            return $item->price * $item->quantity;
        });

        $this->tax_amount = $this->items->sum(function ($item) {
            $taxRate = $item->tax_rate === '-' ? 0 : (float)$item->tax_rate;
            return $item->price * $item->quantity * $taxRate;
        });

        $total = $this->subtotal + $this->tax_amount;

        if ($this->has_discount) {
            if ($this->discount_type === 'percent') {
                $total *= (1 - ($this->discount_value / 100));
            } else {
                $total -= $this->discount_value;
            }
        }

        $this->total_with_vat = round($total, 2);
    }

    public function convertToInvoice()
    {
        DB::beginTransaction();
        try {
            $invoice = Invoice::create([
                'customer_id'     => $this->customer_id,
                'offer_id' => $this->offer_id,
                'invoice_number'  => Invoice::generateNextInvoiceNumber(),
                'issue_date'      => now(),
                'due_date'        => now()->addDays(14), // ar pagal tavo logiką
                'status'          => 'draft',
                'currency'        => $this->currency ?? 'EUR',
                'notes'           => 'Generated from offer: ' . $this->estimate_number,
            ]);

            $subtotal = 0;
            $taxTotal = 0;
            $grandTotal = 0;

            // Sukuriam InvoiceItem ir teisingai paskaičiuojam total_price
            foreach ($this->offerItems as $offerItem) {
                $data = $offerItem->toArray();

                // Skaičiuojam total_price (šita funkcija skaičiuoja kaip formoje)
                $data['total_price'] = $this->calculateTotalPrice($data);
                if (!isset($data['unit_code']) && method_exists($offerItem, 'unit')) {
                    $data['unit_code'] = $offerItem->unit?->code ?? null;
                }
                // Priskirk invoice_id (arba naudok hasMany ryšį)
                $data['invoice_id'] = $invoice->id;

                $invoiceItem = InvoiceItem::create($data);

                $subtotal += $this->calculateNetSubtotal($data);
                $taxTotal += $this->calculateTaxAmount($data);
                $grandTotal += $data['total_price'];
            }
            $invoice->subtotal = round($subtotal, 2);
            $invoice->tax_amount = round($taxTotal, 2);
            $invoice->total_with_vat = round($grandTotal, 2);
            $invoice->save();
            $this->invoice_id = $invoice->id;
            $this->status = 'converted';
            $this->save();

            DB::commit();
            return $invoice;
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }
    public function calculateTotalPrice(array $item): float
    {
        $price = (float)($item['price'] ?? 0);
        $quantity = (int)($item['quantity'] ?? 1);
        $discountValue = (float)($item['discount_value'] ?? 0);
        $discountType = $item['discount_type'] ?? 'percent';
        $taxRate = (float)($item['tax_rate'] ?? 0);

        $net = $price * $quantity;
        if ($discountType === 'percent') {
            $net *= (1 - $discountValue / 100);
        } else {
            $net -= $discountValue;
        }
        $net = max(0, $net);

        return round($net * (1 + $taxRate), 2);
    }

    public function calculateNetSubtotal(array $item): float
    {
        $price = (float)($item['price'] ?? 0);
        $quantity = (int)($item['quantity'] ?? 1);
        $discountValue = (float)($item['discount_value'] ?? 0);
        $discountType = $item['discount_type'] ?? 'percent';

        $net = $price * $quantity;
        if ($discountType === 'percent') {
            $net *= (1 - $discountValue / 100);
        } else {
            $net -= $discountValue;
        }
        return max(0, $net);
    }

    public function calculateTaxAmount(array $item): float
    {
        $net = $this->calculateNetSubtotal($item);
        $taxRate = (float)($item['tax_rate'] ?? 0);

        return round($net * $taxRate, 2);
    }

    public function generatePdf()
    {
        $this->load('user', 'offerItems');

        $locale = Auth::user()?->settings?->language ?? 'en';

        App::forgetInstance('translator');
        App::setLocale($locale);
        \Carbon\Carbon::setLocale($locale);

        return Pdf::loadView('offers.pdf', [
            'offer'        => $this,
            'forcedLocale' => $locale,
        ]);
    }

    public function getPdfPreviewUrl(): string
    {
        return route('offer.pdf.preview', $this);
    }

    protected static function booted(): void
    {
        static::creating(function (Offer $offer) {
            if (empty($offer->public_token)) {
                $offer->public_token = Str::random(32);
            }
        });
    }

    public function getCurrencySymbolAttribute(): string
    {
        $map = [
            'EUR' => '€',
            'USD' => '$',
            'GBP' => '£',
        ];

        return $map[$this->currency] ?? $this->currency;
    }

    public function getTotalInWordsAttribute(): string
    {
        $locale = $this->user?->settings->language ?? app()->getLocale();

        $numberToWords = new NumberToWords();
        $transformer = $numberToWords->getNumberTransformer($locale === 'lt' ? 'lt' : 'en');

        $euros = floor($this->total_with_vat);
        $cents = round(($this->total_with_vat - $euros) * 100);

        if ($locale === 'lt') {
            $words = $transformer->toWords($euros) . ' eurų';
            if ($cents > 0) {
                $words .= ' ' . $transformer->toWords($cents) . ' centų';
            }
        } else {
            $words = $transformer->toWords($euros) . ' euros';
            if ($cents > 0) {
                $words .= ' and ' . $transformer->toWords($cents) . ' cents';
            }
        }

        return ucfirst($words);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'customer_id');
    }

    public function getRouteKeyName()
    {
        return 'offer_id';
    }
}
