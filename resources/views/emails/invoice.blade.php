@component('mail::message')
@slot('subject')
Sąskaita faktūra už suteiktas paslaugas / pristatytas prekes
@endslot

Sveiki {{ $invoice->customer->first_name }} {{ $invoice->customer->last_name }},

Siunčiame Jums sąskaitą faktūrą Nr. {{ $invoice->invoice_number }} už suteiktas paslaugas / prekes.

**Sąskaitos numeris:** {{ $invoice->invoice_number }}  
**Išrašymo data:** {{ $invoice->issue_date->format('Y-m-d') }}  
**Mokėjimo terminas:** {{ $invoice->due_date->format('Y-m-d') }}  
**Bendra suma (su PVM):** {{ number_format($invoice->total_with_vat, 2, '.', '') }} {{ $invoice->currency }}

@component('mail::button', ['url' => $signedUrl])
Peržiūrėti sąskaitą faktūrą
@endcomponent

Tai yra originali PVM sąskaita faktūra, atitinkanti  
– PVM įstatymo 79 ir 80 straipsnių nuostatas  
– LR Vyriausybės nutarimo Nr. 780 „Dėl mokesčiams apskaičiuoti naudojamų apskaitos dokumentų išrašymo ir pripažinimo taisyklių patvirtinimo“ reikalavimus.  

Rekomenduojame sąskaitą faktūrą atsispausdinti arba atsisiųsti PDF formatu.

Prašome apmokėti sąskaitą iki {{ $invoice->due_date->format('Y-m-d') }}, vadovaujantis joje nurodytomis sąlygomis.

Jei turėtumėte klausimų ar pastabų, maloniai kviečiame susisiekti.

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