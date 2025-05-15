<?php

namespace App\Mail;

use App\Models\Offer;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;

class OfferMail extends Mailable
{
    use Queueable, SerializesModels;

    public Offer $offer;
    public string $signedUrl;
    public $settings;

    public function __construct(Offer $offer, string $signedUrl)
    {
        $this->offer     = $offer;
        $this->signedUrl = $signedUrl;
        $this->settings  = Auth::user()->settings;
    }

    public function build()
    {
        return $this
            ->to($this->offer->customer->email)
            ->subject("Pasiūlymas № {$this->offer->estimate_number}")
            ->markdown('emails.offer', [
                'offer'     => $this->offer,
                'signedUrl' => $this->signedUrl,
                'settings'  => $this->settings,
            ]);
    }
}
