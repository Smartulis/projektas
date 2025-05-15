<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Mail;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Filament\Resources\UserSettingResource;
use App\Mail\InvoiceMail;
use NumberToWords\NumberToWords;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'invoice_number',
        'issue_date',
        'due_date',
        'subtotal',
        'tax_amount',
        'total_with_vat',
        'currency',
        'status',
        'notes',
        'offer_id',
        'public_token',
    ];

    protected $casts = [
        'issue_date'   => 'date',
        'due_date'     => 'date',
        'subtotal'     => 'decimal:2',
        'tax_amount'   => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function offer()
    {
        return $this->belongsTo(Offer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(\App\Models\InvoiceItem::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function (Invoice $invoice) {
            // Pull your prefix/format/counter from user settings
            $settings = UserSettingResource::getSettings();

            $prefix  = $settings->invoice_prefix        ?? 'INV';
            $format  = $settings->invoice_number_format ?? '{prefix}-{date}-{counter}';
            $counter = $settings->invoice_counter       ?? 1;

            $replacements = [
                '{prefix}'  => $prefix,
                '{random}'  => strtoupper(Str::random(6)),
                '{date}'    => now()->format('Ymd'),
                '{counter}' => str_pad($counter, 3, '0', STR_PAD_LEFT),
            ];

            // Build the invoice_number
            $invoice->invoice_number = str_replace(
                array_keys($replacements),
                array_values($replacements),
                $format
            );

            // Increment counter for next invoice
            $settings->update([
                'invoice_counter' => $counter + 1,
            ]);
        });
    }

    public function getSignedUrlAttribute()
    {
        return URL::signedRoute(
            'filament.resources.invoices.view',
            ['invoice' => $this],
            now()->addDays(30)
        );
    }

    public function sendToCustomer()
    {
        Mail::to($this->user->email)
            ->send(new InvoiceMail(
                $this,
                route('invoices.public-view', [
                    'invoice' => $this->id,
                    'token'   => $this->public_token,
                ])
            ));
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    protected static function booted()
    {
        static::creating(function ($invoice) {
            if (empty($invoice->public_token)) {
                $invoice->public_token = Str::random(32);
            }
        });
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public static function generateNextInvoiceNumber(): string
    {
        // Pvz. imti prefix ir formatą iš UserSettingResource arba iš settings DB
        $settings = \App\Filament\Resources\UserSettingResource::getSettings();
        $prefix = $settings['invoice_prefix'] ?? 'INV';
        $format = $settings['invoice_number_format'] ?? '{prefix}-{year}-{counter}';

        // Surask didžiausią counter šiais metais
        $year = date('Y');
        $lastInvoice = self::whereYear('created_at', $year)
            ->orderByDesc('id')
            ->first();
        $nextCounter = 1;

        if ($lastInvoice && preg_match('/(\d+)$/', $lastInvoice->invoice_number, $m)) {
            $nextCounter = intval($m[1]) + 1;
        }

        $number = str_replace(
            ['{prefix}', '{year}', '{counter}'],
            [$prefix, $year, str_pad($nextCounter, 5, '0', STR_PAD_LEFT)],
            $format
        );

        return $number;
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
}
