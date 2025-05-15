<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>{{ $invoice->invoice_number }}</title>
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

    // Payment terms label
    $termsOptions = __('translate.options.payment_terms');
    $paymentTermsLabel = $termsOptions[$invoice->payment_terms ?? $settings->payment_terms]
      ?? ($invoice->payment_terms ?? $settings->payment_terms)
      ?? '—';

    $currencyCode = $settings->currency ?? $invoice->currency;
    $currencySymbol = \App\Filament\Resources\InvoiceResource::getCurrencySymbol($currencyCode);
    $fmt = fn($value): string => number_format($value, 2, '.', '');
    $fmtPercent = fn($value): string => number_format($value, 0, '.', '') . ' %';

    $subtotal = $invoice->items->sum(fn($i) => $i->price * $i->quantity);
    $totalItemDiscount = $invoice->items->sum(fn($i) =>
      $i->discount_type === 'percent'
        ? $i->price * $i->quantity * ($i->discount_value / 100)
        : $i->discount_value * $i->quantity
    );
    $subtotalAfterDiscount = $subtotal - $totalItemDiscount;
    $taxTotal = $invoice->items->sum(function($i) {
      $base = $i->price * $i->quantity
        - (
          $i->discount_type === 'percent'
            ? $i->price * $i->quantity * ($i->discount_value / 100)
            : $i->discount_value * $i->quantity
        );
      return $base * ($i->tax_rate === '-' ? 0 : $i->tax_rate);
    });
  @endphp

  @if ($invoice->status === 'paid')
    <div class="max-w-4xl mx-auto mt-8 px-6 py-4 bg-green-100 border border-green-400 text-green-800 rounded-lg shadow">
      <strong>Invoice paid.</strong>
    </div>
  @elseif ($invoice->status === 'cancelled')
    <div class="max-w-4xl mx-auto mt-8 px-6 py-4 bg-red-100 border border-red-400 text-red-800 rounded-lg shadow">
      <strong>Invoice cancelled.</strong>
    </div>
  @endif

  <div class="max-w-4xl mx-auto mt-12 bg-white shadow-xl rounded-lg overflow-hidden">

    {{-- HEADER --}}
    <div class="flex">
      <div class="w-2/3 bg-blue-500 p-8 flex items-center">
        <h1 class="text-4xl font-extrabold text-white">INVOICE</h1>
      </div>
      <div class="w-1/3 bg-blue-600 p-8 text-right">
        <div class="text-sm text-blue-200 uppercase mb-1">Total Due ({{ $currencyCode }})</div>
        <div class="text-3xl font-bold text-white">{{ $fmt($invoice->total_with_vat) }} {{ $currencySymbol }}</div>
      </div>
    </div>

    {{-- BILL FROM & BILL TO --}}
    <div class="px-8 pt-8 pb-4 grid grid-cols-2 gap-8">
      <div>
        <div class="text-sm text-gray-500 uppercase mb-1">Bill From</div>
        <div class="text-lg font-medium">{{ $companyDetail->company_name ?? Auth::user()->name }}</div>
        <div class="text-gray-600">Address: {{ $companyDetail->company_address ?? '—' }}</div>
        <div class="text-gray-600">VAT: {{ $companyDetail->vat_code ?? '—' }}</div>
        <div class="text-gray-600">Reg. No.: {{ $companyDetail->company_code ?? '—' }}</div>
        <div class="text-gray-600">Bank: {{ $companyDetail->bank_account ?? '—' }}</div>
        <div class="text-gray-600">Tel.: {{ $companyDetail->phone_number ?? '—' }}</div>
      </div>
      <div class="flex flex-col items-end">
        <div class="text-right mb-6">
          <div class="text-sm text-gray-500 uppercase mb-1">Bill To</div>
          <div class="text-lg font-medium">{{ $invoice->customer->company_name ?? $invoice->customer->full_name }}</div>
          <div class="text-gray-600 whitespace-pre-line">Address: {{ $invoice->customer->address ?? '-' }}</div>
          <div class="text-gray-600">Email: {{ $invoice->customer->email }}</div>
          <div class="text-gray-600">Tel.: {{ $invoice->customer->phone ?? '-' }}</div>
        </div>
      </div>
    </div>

    {{-- PAYMENT TERMS & INVOICE DETAILS --}}
    <div class="px-8 pt-4 pb-8 grid grid-cols-2 gap-8">
      <div>
        <span class="font-medium uppercase">Payment Terms</span>
        <div>{{ $paymentTermsLabel }}</div>
      </div>
      <div class="text-right space-y-1">
        <div><span class="font-medium">Invoice:</span> {{ $invoice->invoice_number }}</div>
        <div><span class="font-medium">Date:</span> {{ $invoice->issue_date->format('F j, Y') }}</div>
        <div><span class="font-medium">Due Date:</span> {{ $invoice->due_date->format('F j, Y') }}</div>
      </div>
    </div>

    {{-- ITEMS TABLE --}}
    <div class="overflow-x-auto border-b border-gray-200">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-8 py-3 text-left text-xs font-medium text-gray-500 uppercase">Item</th>
            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Qty</th>
            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Price</th>
            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Tax</th>
            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Discount</th>
            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Amount</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          @forelse($invoice->items as $item)
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
          @if($invoice->total_in_words)
            <p>
              <strong>Amount in words:</strong>
              {{ $invoice->total_in_words }}
            </p>
          @endif

          @if(! empty($invoice->notes))
            <div>
              <p class="text-gray-600 font-medium mb-1">Notes:</p>
              <p class="text-gray-600 whitespace-pre-line break-words">{{ $invoice->notes }}</p>
            </div>
          @endif
        </div>

        <div class="ml-auto w-full md:w-1/3 space-y-2">
          <div class="flex justify-between"><span class="text-gray-600">Subtotal:</span> <span>{{ $fmt($subtotal) }} {{ $currencySymbol }}</span></div>
          @if($totalItemDiscount > 0)
            <div class="flex justify-between"><span class="text-gray-600">Discount:</span> <span>–{{ $fmt($totalItemDiscount) }} {{ $currencySymbol }}</span></div>
            <div class="flex justify-between"><span class="text-gray-600">Subtotal after Discount:</span> <span>{{ $fmt($subtotalAfterDiscount) }} {{ $currencySymbol }}</span></div>
          @endif
          <div class="flex justify-between"><span class="text-gray-600">Tax:</span> <span>{{ $fmt($taxTotal) }} {{ $currencySymbol }}</span></div>
          <div class="border-t border-gray-200 pt-2 flex justify-between font-bold"><span>Total:</span> <span>{{ $fmt($invoice->total_with_vat) }} {{ $currencySymbol }}</span></div>
        </div>
      </div>
    </div>

    {{-- ACTION BUTTONS --}}
    @if (! in_array($invoice->status, ['paid', 'cancelled'], true))
      <div class="px-8 pb-8 flex flex-col sm:flex-row justify-center sm:space-x-4 space-y-4 sm:space-y-0">
        <a
          href="{{ route('invoices.pay.show', ['invoice' => $invoice->id, 'token' => $invoice->public_token]) }}"
          class="flex-1 px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white text-center font-semibold rounded-lg transition"
        >
          Pay Now
        </a>
        <form
          action="{{ route('invoices.cancel', ['invoice' => $invoice->id, 'token' => $token]) }}"
          method="POST"
          class="flex-1"
        >
          @csrf
          @method('PATCH')
          <button
            type="submit"
            class="w-full px-6 py-3 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg transition"
          >
            Cancel
          </button>
        </form>
      </div>
    @endif
  </div>
</body>
</html>
