@php
use Illuminate\Support\Facades\Auth;

/**
* @var \App\Models\Offer $offer
*/

if (isset($forcedLocale)) {
app()->setLocale($forcedLocale);
\Carbon\Carbon::setLocale($forcedLocale);
}

$issuerSettings = Auth::user()?->settings;
$company = $issuerSettings?->companyDetail;

$currencyCode = $issuerSettings->currency ?? $offer->currency;
$currencySymbols = ['EUR' => '€', 'USD' => '$', 'GBP' => '£'];
$symbol = $currencySymbols[$currencyCode] ?? $currencyCode;
@endphp

<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">

<head>
    <meta charset="utf-8">
    <title>{{ $offer->estimate_number }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', Arial, Helvetica, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #000000;
            margin: 0;
            padding: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .header h1 {
            font-size: 16px;
            font-weight: bold;
            margin: 5px 0;
            padding: 0;
        }

        .header h2 {
            font-size: 14px;
            font-weight: normal;
            margin-top: 0;
        }

        .companies-container {
            width: 100%;
            margin-bottom: 15px;
        }

        .companies-container table {
            width: 100%;
        }

        .companies-container td {
            width: 50%;
            vertical-align: top;
        }

        .seller {
            text-align: left;
        }

        .buyer {
            text-align: right;
        }

        .dates {
            width: 100%;
            text-align: right;
            margin-bottom: 15px;
        }

        .invoice-table {
            table-layout: fixed;
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        .invoice-table th {
            background-color: #f2f2f2;
            padding: 6px 3px;
            border: 1px solid #ddd;
            font-weight: bold;
        }

        .invoice-table td {
            padding: 6px 3px;
            border: 1px solid #ddd;
        }

        .invoice-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        /* Column widths */
        .invoice-table th:nth-child(1),
        .invoice-table td:nth-child(1) {
            width: 20%;
            /* Product */
            text-align: left;
        }

        .invoice-table th:nth-child(2),
        .invoice-table td:nth-child(2) {
            width: 30%;
            /* Description */
            text-align: left;
        }

        .invoice-table th:nth-child(3),
        .invoice-table td:nth-child(3) {
            width: 8%;
            /* Quantity */
            text-align: center;
        }

        .invoice-table th:nth-child(4),
        .invoice-table td:nth-child(4) {
            width: 15%;
            /* Price */
            text-align: center;
        }

        .invoice-table th:nth-child(5),
        .invoice-table td:nth-child(5) {
            width: 10%;
            /* Discount */
            text-align: center;
        }

        .invoice-table th:nth-child(6),
        .invoice-table td:nth-child(6) {
            width: 10%;
            /* Tax */
            text-align: center;
        }

        .invoice-table th:nth-child(7),
        .invoice-table td:nth-child(7) {
            width: 15%;
            /* Total */
            text-align: center;
        }

        .invoice-table td:nth-child(4),
        /* Price */
        .invoice-table td:nth-child(5),
        /* Discount */
        .invoice-table td:nth-child(6),
        /* Tax */
        .invoice-table td:nth-child(7) {
            /* Total */
            white-space: nowrap;
        }

        .currency-symbol:before {
            content: '\00a0';
        }

        .percent-symbol:before {
            content: '\00a0';
        }

        .totals-table {
            width: 250px;
            float: right;
            margin-top: 10px;
            border-collapse: collapse;
        }

        .totals-table td {
            padding: 5px;
            border: none;
        }

        .totals-table tr:last-child td {
            font-weight: bold;
            border-top: 1px solid #000;
        }

        .bold-text {
            font-weight: bold;
        }

        .text-right {
            text-align: right;
        }

        .payment-method,
        .notes {
            clear: both;
            margin-top: 20px;
            margin-bottom: 30px;
        }

        .notes-title,
        .payment-method .bold-text {
            font-weight: bold;
            margin-bottom: 5px;
        }

        .signatures {
            display: flex;
            justify-content: flex-start;
            margin-top: 60px;
            width: 100%;
        }

        .signature-box {
            width: 45%;
            border-top: 1px dashed #000;
            padding-top: 5px;
            text-align: center;
        }
    </style>
</head>

<body>
    {{-- Header --}}
    <div class="header">
        <h1>{{ __('translate.offer.pdf.title') }}</h1>
        <h2>{{ $offer->estimate_number }}</h2>
    </div>

    {{-- Companies --}}
    <div class="companies-container">
        <table>
            <tr>
                <td class="seller">
                    <div>{{ __('translate.offer.pdf.supplier') }}</div>
                    <div class="bold-text">{{ $company->company_name ?? Auth::user()->name }}</div>
                    <div>Address: {{ $company->company_address ?? '—' }}</div>
                    <div>VAT: {{ $company->vat_code ?? '—' }}</div>
                    <div>Reg. No.: {{ $company->company_code ?? '—' }}</div>
                    <div>Bank: {{ $company->bank_account ?? '—' }}</div>
                    <div>Tel.: {{ $company->phone_number ?? '—' }}</div>
                </td>
                <td class="buyer">
                    <div>{{ __('translate.offer.pdf.buyer') }}</div>
                    <div class="bold-text">{{ $offer->customer->company_name }}</div>
                    <div>{{ $offer->customer->address }}</div>
                    <div>Email: {{ $offer->customer->email }}</div>

                    @if($offer->customer->phone)
                    <div>Tel.: {{ $offer->customer->phone }}</div>
                    @endif
                </td>
            </tr>
        </table>
    </div>

    {{-- Dates --}}
    <div class="dates">
        <div>
            <span class="bold-text">{{ __('translate.offer.pdf.offer_date') }}:</span>
            {{ $offer->date->format('Y-m-d') }}
        </div>
        <div>
            <span class="bold-text">{{ __('translate.offer.pdf.valid_until') }}:</span>
            {{ $offer->valid_until->format('Y-m-d') }}
        </div>
    </div>

    {{-- Items table --}}
    <table class="invoice-table">
        <thead>
            <tr>
                <th>{{ __('translate.offer.pdf.product') }}</th>
                <th>{{ __('translate.offer.pdf.description') }}</th>
                <th>{{ __('translate.offer.pdf.quantity') }}</th>
                <th>{{ __('translate.offer.pdf.price') }}</th>
                <th>{{ __('translate.offer.pdf.discount') }}</th>
                <th>{{ __('translate.offer.pdf.tax') }}</th>
                <th>{{ __('translate.offer.pdf.total') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($offer->offerItems as $item)
            @php
            $orig = $item->price * $item->quantity;
            $net = $item->discount_type === 'percent'
            ? $orig * (1 - $item->discount_value/100)
            : $orig - $item->discount_value;
            $net = max(0, $net);
            $taxRate = $item->tax_rate === '-' ? 0 : (float)$item->tax_rate;
            $taxAmount = $net * $taxRate;
            $lineTotal = $net + $taxAmount;
            @endphp
            <tr>
                <td>{{ $item->name }}</td>
                <td>{{ $item->description }}</td>
                <td>
                    {{ $item->quantity }}
                    {{ $item->unit_code ?? $item->unit?->lt_name_short }}
                </td>
                <td>{{ number_format($item->price,2,'.','') }}<span class="currency-symbol">{{ $symbol }}</span></td>
                <td>
                    @if($item->discount_value > 0)
                    @if($item->discount_type === 'percent')
                    {{ $item->discount_value }}<span class="percent-symbol">%</span>
                    @else
                    {{ number_format($item->discount_value,2,'.','') }}<span class="currency-symbol">{{ $symbol }}</span>
                    @endif
                    @else
                    &mdash;
                    @endif
                </td>
                <td>{{ (int)($taxRate * 100) }}<span class="percent-symbol">%</span></td>
                <td class="bold-text">{{ number_format($lineTotal,2,'.','') }}<span class="currency-symbol">{{ $symbol }}</span></td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Totals calculation --}}
    @php
    $lines = $offer->offerItems;
    $rawSubtotal = $lines->sum(fn($i) => $i->price * $i->quantity);
    $discountTotal = $lines->sum(fn($i) =>
    $i->discount_type === 'percent'
    ? ($i->price * $i->quantity) * ($i->discount_value/100)
    : $i->discount_value
    );
    $grandTotal = $lines->sum(function($i) {
    $orig = $i->price * $i->quantity;
    $net = $i->discount_type==='percent'
    ? $orig * (1 - $i->discount_value/100)
    : $orig - $i->discount_value;
    $taxRate = $i->tax_rate==='-' ? 0 : (float)$i->tax_rate;
    return max(0, $net) * (1 + $taxRate);
    });
    $taxAmount = $grandTotal - ($rawSubtotal - $discountTotal);
    $termKey = $issuerSettings->payment_terms ?? null;
    if ($termKey && str_contains($termKey, '_')) {
    [$adv, $del] = explode('_', $termKey);
    $advAmt = $grandTotal * ($adv/100);
    $delAmt = $grandTotal * ($del/100);
    }
    @endphp

    @php
    $subtotalAfterDiscounts = $rawSubtotal - $discountTotal;
    @endphp

    <table class="totals-table">
        <tr>
            <td>{{ __('translate.offer.pdf.subtotal') }}</td>
            <td class="text-right">{{ number_format($rawSubtotal,2,'.','') }}<span class="currency-symbol">{{ $symbol }}</span></td>
        </tr>

        @if($discountTotal > 0)
        <tr>
            <td>{{ __('translate.offer.pdf.discounts') }}</td>
            <td class="text-right">–{{ number_format($discountTotal,2,'.','') }}<span class="currency-symbol">{{ $symbol }}</span></td>
        </tr>
        <tr>
            <td>{{ __('translate.offer.pdf.subtotal_after_discounts') }}</td>
            <td class="text-right">{{ number_format($subtotalAfterDiscounts,2,'.','') }}<span class="currency-symbol">{{ $symbol }}</span></td>
        </tr>
        @endif

        <tr>
            <td>{{ __('translate.offer.pdf.vat') }}</td>
            <td class="text-right">{{ number_format($taxAmount,2,'.','') }}<span class="currency-symbol">{{ $symbol }}</span></td>
        </tr>
        <tr>
            <td class="bold-text">{{ __('translate.offer.pdf.total') }}</td>
            <td class="text-right bold-text">{{ number_format($grandTotal,2,'.','') }}<span class="currency-symbol">{{ $symbol }}</span></td>
        </tr>
    </table>

    {{-- Amount in words --}}
    @if(!empty($offer->total_in_words))
    <div style="margin-top:15px;font-style:italic;">
        <strong>{{ __('translate.offer.pdf.amount_in_words') }}</strong>
        {{ $offer->total_in_words }}
    </div>
    @endif

    {{-- Payment terms --}}
    @if(isset($adv))
    <div class="payment-method">
        <div class="bold-text">{{ __('translate.offer.pdf.payment_terms') }}</div>
        <div>{{ $adv }}% {{ __('translate.offer.pdf.advance') }}:
            {{ number_format($advAmt,2,'.','') }}<span class="currency-symbol">{{ $symbol }}</span>
        </div>
        <div>{{ $del }}% {{ __('translate.offer.pdf.on_delivery') }}:
            {{ number_format($delAmt,2,'.','') }}<span class="currency-symbol">{{ $symbol }}</span>
        </div>
    </div>
    @endif

    {{-- Customer comment --}}
    @if(!empty($offer->customer_comment))
    <div class="notes">
        <div class="notes-title">{{ __('offer.pdf.notes') }}</div>
        <div style="white-space:pre-line;">{{ $offer->customer_comment }}</div>
    </div>
    @endif

    {{-- Signature --}}
    <div class="signatures">
        <div class="signature-box">
            {{ __('translate.offer.pdf.prepared_by') }}
            {{ $offer->issuer_name ?? Auth::user()->name }}
        </div>
    </div>

</body>

</html>