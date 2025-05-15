<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>{{ $offer->estimate_number }}</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

    body {
      font-family: 'Inter', sans-serif;
    }
  </style>
</head>

<body class="bg-gray-100 text-gray-800 antialiased">
 @php
    $settings = Auth::user()->settings;
    $companyDetail = $settings->companyDetail ?? null;
    $locale = $settings->language ?? 'lt';
    App::setLocale($locale);

    $termsOptions = __('translate.options.payment_terms');
    // Galima paimti offer arba iš user settings
    $paymentTermsKey = $offer->payment_terms ?? $settings->payment_terms ?? null;
    $paymentTermsLabel = $paymentTermsKey && isset($termsOptions[$paymentTermsKey])
        ? $termsOptions[$paymentTermsKey]
        : ($paymentTermsKey ?? '—');

    $currencyCode = $settings->currency ?? $offer->currency;
    $currencySymbol = \App\Filament\Resources\OfferResource::getCurrencySymbol($currencyCode);
    $fmt = fn($value): string => number_format($value, 2, '.', '');
    $fmtPercent = fn($value): string => number_format($value, 0, '.', '') . ' %';

    $subtotal = $offer->offerItems->sum(fn($i) => $i->price * $i->quantity);
    $totalItemDiscount = $offer->offerItems->sum(fn($i) =>
        $i->discount_type === 'percent'
            ? $i->price * $i->quantity * ($i->discount_value / 100)
            : $i->discount_value * $i->quantity
    );
    $subtotalAfterDiscount = $subtotal - $totalItemDiscount;
    $taxTotal = $offer->offerItems->sum(function($i) {
        $base = $i->price * $i->quantity
                - (
                    $i->discount_type === 'percent'
                        ? $i->price * $i->quantity * ($i->discount_value / 100)
                        : $i->discount_value * $i->quantity
                );
        return $base * ($i->tax_rate === '-' ? 0 : $i->tax_rate);
    });
@endphp

@if ($offer->status === 'accepted')
  <div class="max-w-4xl mx-auto mt-8 px-6 py-4 bg-green-100 border border-green-400 text-green-800 rounded-lg shadow">
    <strong>{{ __('translate.offer.accepted') }}</strong>
  </div>
@elseif ($offer->status === 'rejected')
  <div class="max-w-4xl mx-auto mt-8 px-6 py-4 bg-red-100 border border-red-400 text-red-800 rounded-lg shadow">
    <strong>{{ __('translate.offer.rejected') }}</strong>
  </div>
@elseif ($offer->status === 'expired')
  <div class="max-w-4xl mx-auto mt-8 px-6 py-4 bg-yellow-100 border border-yellow-400 text-yellow-800 rounded-lg shadow">
    <strong>{{ __('translate.offer.expired') }}</strong>
  </div>
@endif
<div class="max-w-4xl mx-auto mt-12 bg-white shadow-xl rounded-lg overflow-hidden">

  {{-- HEADER --}}
  <div class="flex">
    <div class="w-2/3 bg-green-500 p-8 flex items-center">
      <h1 class="text-4xl font-extrabold text-white">{{ __('translate.offer.estimate') }}</h1>
    </div>
    <div class="w-1/3 bg-green-600 p-8 text-right">
      <div class="text-sm text-green-200 uppercase mb-1">{{ __('translate.offer.grand_total') }} ({{ $currencyCode }})</div>
      <div class="text-3xl font-bold text-white">{{ $fmt($offer->total_with_vat) }} {{ $currencySymbol }}</div>
    </div>
  </div>

  {{-- BILL FROM & BILL TO --}}
  <div class="px-8 pt-8 pb-4 grid grid-cols-2 gap-8">
    <div>
      <div class="text-sm text-gray-500 uppercase mb-1">{{ __('translate.offer.bill_from') }}</div>
      <div class="text-lg font-medium">{{ $companyDetail->company_name ?? Auth::user()->name }}</div>
      <div class="text-gray-600">{{ __('Address') }}: {{ $companyDetail->company_address ?? '—' }}</div>
      <div class="text-gray-600">VAT: {{ $companyDetail->vat_code ?? '—' }}</div>
      <div class="text-gray-600">Reg. No.: {{ $companyDetail->company_code ?? '—' }}</div>
      <div class="text-gray-600">Bank: {{ $companyDetail->bank_account ?? '—' }}</div>
      <div class="text-gray-600">Tel.: {{ $companyDetail->phone_number ?? '—' }}</div>
    </div>
    <div class="flex flex-col items-end">
      <div class="text-right mb-6">
        <div class="text-sm text-gray-500 uppercase mb-1">{{ __('translate.offer.bill_to') }}</div>
        <div class="text-lg font-medium">{{ $offer->customer->company_name }}</div>
        <div class="text-gray-600 whitespace-pre-line">{{ __('Address') }}: {{ $offer->customer->address ?? '-' }}</div>
        <div class="text-gray-600">Email: {{ $offer->customer->email }}</div>
        <div class="text-gray-600">Tel.: {{ $offer->customer->phone ?? '-' }}</div>
      </div>
    </div>
  </div>

  {{-- PAYMENT TERMS & ESTIMATE DETAILS --}}
  <div class="px-8 pt-4 pb-8 grid grid-cols-2 gap-8">
    <div>
      <span class="font-medium uppercase">{{ __('translate.offer.payment_terms') }}</span>
      <div>{{ $paymentTermsLabel }}</div>
    </div>
    <div class="text-right space-y-1">
      <div><span class="font-medium">{{ __('translate.offer.estimate_number') }}:</span> {{ $offer->estimate_number }}</div>
      <div><span class="font-medium">{{ __('translate.offer.date') }}:</span> {{ $offer->date->format('Y-m-d') }}</div>
      <div><span class="font-medium">{{ __('translate.offer.valid_until') }}:</span> {{ $offer->valid_until->format('Y-m-d') }}</div>
    </div>
  </div>

  {{-- ITEMS TABLE --}}
  <div class="overflow-x-auto border-b border-gray-200">
    <table class="min-w-full divide-y divide-gray-200">
      <thead class="bg-gray-50">
        <tr>
          <th class="px-8 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('translate.offer.item') }}</th>
          <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">{{ __('translate.offer.qty') }}</th>
          <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">{{ __('translate.offer.price') }}</th>
          <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">{{ __('translate.offer.tax') }}</th>
          <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">{{ __('translate.offer.discount') }}</th>
          <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">{{ __('translate.offer.amount') }}</th>
        </tr>
      </thead>
      <tbody class="bg-white divide-y divide-gray-200">
        @forelse($offer->offerItems as $item)
        <tr class="hover:bg-gray-50">
          <td class="px-8 py-4 whitespace-normal">
            <div class="font-medium">{{ $item->name }}</div>
            @if($item->description)
            <div class="text-sm text-gray-500 mt-1">{{ $item->description }}</div>
            @endif
          </td>
          <td class="px-6 py-4 text-center">
            {{ $item->quantity }} {{ $item->unit_code ?? $item->unit?->code }}
          </td>
          <td class="px-6 py-4 text-center">{{ $fmt($item->price) }} {{ $currencySymbol }}</td>
          <td class="px-6 py-4 text-center">{{ $item->tax_rate === '-' ? '0 %' : $fmtPercent($item->tax_rate * 100) }}</td>
          <td class="px-6 py-4 text-center">
            @if($item->discount_value > 0)
              @if($item->discount_type === 'percent')
                {{ $fmtPercent($item->discount_value) }}
              @else
                {{ $fmt($item->discount_value) }} {{ $currencySymbol }}
              @endif
            @else
              —
            @endif
          </td>
          <td class="px-6 py-4 text-center font-semibold">{{ $fmt($item->total_price) }} {{ $currencySymbol }}</td>
        </tr>
        @empty
        <tr>
          <td colspan="6" class="px-6 py-4 text-center text-gray-500">No items added.</td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  {{-- SUMMARY --}}
  <div class="p-8">
    <div class="flex flex-wrap gap-8 items-start">
      <div class="w-full md:w-2/4 space-y-4">
        @if($offer->total_in_words)
        <p>
          <strong>{{ __('translate.offer.amount_in_words') }}:</strong>
          {{ $offer->total_in_words }}
        </p>
        @endif

        @if(! empty($offer->notes))
        <div>
          <p class="text-gray-600 font-medium mb-1">{{ __('translate.offer.notes') }}:</p>
          <p class="text-gray-600 whitespace-pre-line break-words">{{ $offer->notes }}</p>
        </div>
        @endif
      </div>
      <div class="ml-auto w-full md:w-1/3 space-y-2">
        <div class="flex justify-between"><span class="text-gray-600">{{ __('translate.offer.subtotal') }}:</span> <span>{{ $fmt($subtotal) }} {{ $currencySymbol }}</span></div>
        @if($totalItemDiscount > 0)
        <div class="flex justify-between"><span class="text-gray-600">{{ __('translate.offer.discount_label') }}:</span> <span>–{{ $fmt($totalItemDiscount) }} {{ $currencySymbol }}</span></div>
        <div class="flex justify-between"><span class="text-gray-600">{{ __('translate.offer.subtotal_after_discount') }}:</span> <span>{{ $fmt($subtotalAfterDiscount) }} {{ $currencySymbol }}</span></div>
        @endif
        <div class="flex justify-between"><span class="text-gray-600">{{ __('translate.offer.tax_total') }}:</span> <span>{{ $fmt($taxTotal) }} {{ $currencySymbol }}</span></div>
        <div class="border-t border-gray-200 pt-2 flex justify-between font-bold"><span>{{ __('translate.offer.total') }}:</span> <span>{{ $fmt($offer->total_with_vat) }} {{ $currencySymbol }}</span></div>
      </div>
    </div>
  </div>

  {{-- ACCEPT / REJECT BUTTONS --}}
  @if($offer->status === 'created')
  <div class="px-8 pb-8 flex justify-center space-x-4">
    {{-- Accept --}}
    <form method="POST" action="{{ route('offers.accept', [$offer, $token]) }}" class="flex-1">
      @csrf
      <button
        type="submit"
        class="w-full px-6 py-2 bg-green-500 hover:bg-green-600 text-white font-medium rounded-lg transition">
        Accept offer
      </button>
    </form>

    {{-- Reject --}}
    <form method="POST" action="{{ route('offers.reject', [$offer, $token]) }}" class="flex-1">
      @csrf
      <button
        type="submit"
        class="w-full px-6 py-2 bg-red-500 hover:bg-red-600 text-white font-medium rounded-lg transition">
        Reject offer
      </button>
    </form>
  </div>
  @endif

</div>
</body>

</html>