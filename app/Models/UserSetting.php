<?php

namespace App\Models;

use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'currency',
        'estimate_prefix',
        'estimate_number_format',
        'estimate_counter',
        'invoice_prefix',
        'invoice_number_format',
        'invoice_counter',
        'language',
        'payment_terms',
        'default_valid_until',
        'default_due_date',
        'tax_rates',
        'default_tax_rate',
    ];

    protected $casts = [
        'tax_rates'      => 'array',
        'estimate_counter' => 'integer',
        'invoice_counter'  => 'integer',
    ];


    public function generateEstimateNumber(): string
    {
        $number = $this->replaceTokens(
            $this->estimate_prefix,
            $this->estimate_number_format,
            $this->estimate_counter,
        );

        if (str_contains($this->estimate_number_format, '{counter}')) {
            $this->increment('estimate_counter');
        }

        return $number;
    }

    public function generateInvoiceNumber(): string
    {
        $number = $this->replaceTokens(
            $this->invoice_prefix,
            $this->invoice_number_format,
            $this->invoice_counter,
        );

        if (str_contains($this->invoice_number_format, '{counter}')) {
            $this->increment('invoice_counter');
        }

        return $number;
    }

    public function generatePdf()
    {
        $this->load('user', 'offerItems');

        $locale = Auth::user()?->settings?->language ?? 'en';

        app()->setLocale($locale);

        $pdf = Pdf::loadView('offers.pdf', [
            'offer' => $this,
        ]);

        return $pdf;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function companyDetail()
    {
        return $this->hasOne(CompanyDetail::class, 'user_id', 'user_id');
    }
}
