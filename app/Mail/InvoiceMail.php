<?php

namespace App\Mail;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;

class InvoiceMail extends Mailable
{
    use Queueable, SerializesModels;

    public Invoice $invoice;
    public string  $signedUrl;
    public $settings;

    public function __construct(Invoice $invoice, string $signedUrl)
    {
        $this->invoice   = $invoice;
        $this->signedUrl = $signedUrl;
        $this->settings  = Auth::user()->settings;
    }

    public function build()
    {
        return $this
            ->subject('Sąskaita faktūra ' . $this->invoice->invoice_number)
            ->markdown('emails.invoice', [
                'invoice'   => $this->invoice,
                'signedUrl' => $this->signedUrl,
                'settings'  => $this->settings,
            ]);
    }
}
