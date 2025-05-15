@component('mail::message')
@slot('subject')
Pasiūlymas už prekių pristatymą / paslaugų suteikimą
@endslot

# Pasiūlymas Nr. {{ $offer->estimate_number }}

Sveiki {{ $offer->customer->first_name }} {{ $offer->customer->last_name }},

Siunčiame Jums pasiūlymą dėl prekių pristatymo / paslaugų suteikimo.

**Pasiūlymo Nr.:** {{ $offer->estimate_number }}  
**Pasiūlymo data:** {{ $offer->date->format('Y-m-d') }}  
**Galioja iki:** {{ $offer->valid_until->format('Y-m-d') }}  
**Bendra suma (su PVM):** {{ number_format($offer->total_with_vat, 2, '.', '') }} {{ $offer->currency }}

@component('mail::button', ['url' => $signedUrl])
Peržiūrėti pasiūlymą
@endcomponent

Prašome pranešti dėl sprendimo priėmimo iki {{ $offer->valid_until->format('Y-m-d') }} pagal pasiūlymo sąlygas.

Pagarbiai,<br>
{{ Auth::user()->name }}<br>
{{ $settings->companyDetail->company_name }}<br>
{{ $settings->companyDetail->company_address }}<br>
VAT kodas: {{ $settings->companyDetail->vat_code }}<br>
Įmonės kodas: {{ $settings->companyDetail->company_code }}<br>
Banko sąskaita: {{ $settings->companyDetail->bank_account }}<br>
Tel.: {{ $settings->companyDetail->phone_number }}<br>
El. paštas: {{ $settings->companyDetail->email }}
@endcomponent