<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Mokėti sąskaitą #{{ $invoice->invoice_number }}</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/alpinejs@3" defer></script>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
    body { font-family: 'Inter', sans-serif; }
    [x-cloak] { display: none; }
  </style>
</head>
<body class="bg-gray-100 text-gray-800 antialiased">
  <div 
    x-data="{ 
      method: '{{ old('method', 'card') }}',
      paymentSuccess: @json(session('payment_success', false)),
      isLoading: false
    }"
    x-cloak
    class="max-w-lg mx-auto bg-white shadow rounded-lg p-8 mt-12"
  >
    <!-- Form block -->
    <template x-if="!paymentSuccess">
      <div>
        <h2 class="text-2xl font-semibold mb-6">Mokėti sąskaitą #{{ $invoice->invoice_number }}</h2>
        <form 
          @submit.prevent="
            isLoading = true;
            $nextTick(() => $el.submit());
          "
           action="{{ route('invoices.pay.process', ['invoice' => $invoice->id, 'token' => $token]) }}"
          method="POST"
          class="space-y-6"
        >
          @csrf

          <p class="text-gray-700">
            Suma:
            <strong>{{ number_format($invoice->total_with_vat, 2) }} {{ $invoice->currency }}</strong>
          </p>

          {{-- Pasirinkti mokėjimo būdą --}}
          <div class="space-x-6">
            <label class="inline-flex items-center">
              <input type="radio" name="method" value="card" x-model="method" class="form-radio"/>
              <span class="ml-2">Kortelė</span>
            </label>
            <label class="inline-flex items-center">
              <input type="radio" name="method" value="bank" x-model="method" class="form-radio"/>
              <span class="ml-2">Bankinis pavedimas</span>
            </label>
          </div>
          @error('method')<div class="text-red-600 text-sm">{{ $message }}</div>@enderror

          {{-- Kortelės laukai --}}
          <div x-show="method === 'card'" class="space-y-4">
            <div>
              <label class="block text-sm font-medium">Kortelės savininkas</label>
              <input
                type="text"
                name="cardholder_name"
                value="{{ old('cardholder_name') }}"
                class="mt-1 w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring focus:ring-blue-200"
              />
              @error('cardholder_name')<div class="text-red-600 text-sm">{{ $message }}</div>@enderror
            </div>
            <div>
              <label class="block text-sm font-medium">Kortelės numeris</label>
              <input
                type="text"
                name="card_number"
                maxlength="19"
                value="{{ old('card_number') }}"
                class="mt-1 w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring focus:ring-blue-200"
              />
              @error('card_number')<div class="text-red-600 text-sm">{{ $message }}</div>@enderror
            </div>
            <div class="flex gap-4">
              <div class="flex-1">
                <label class="block text-sm font-medium">Galiojimo data (MM/YY)</label>
                <input
                  type="text"
                  name="card_expiry"
                  maxlength="5"
                  value="{{ old('card_expiry') }}"
                  placeholder="MM/YY"
                  class="mt-1 w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring focus:ring-blue-200"
                />
                @error('card_expiry')<div class="text-red-600 text-sm">{{ $message }}</div>@enderror
              </div>
              <div class="flex-1">
                <label class="block text-sm font-medium">CVV</label>
                <input
                  type="text"
                  name="card_cvv"
                  value="{{ old('card_cvv') }}"
                  maxlength="3"
                  placeholder="123"
                  class="mt-1 w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring focus:ring-blue-200"
                />
                @error('card_cvv')<div class="text-red-600 text-sm">{{ $message }}</div>@enderror
              </div>
            </div>
          </div>

          {{-- Bankinio pavedimo laukai --}}
          <div x-show="method === 'bank'" class="space-y-4">
            <div>
              <label class="block text-sm font-medium">Vardas / Įmonės pavadinimas</label>
              <input
                type="text"
                name="bank_account_name"
                value="{{ old('bank_account_name') }}"
                class="mt-1 w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring focus:ring-blue-200"
              />
              @error('bank_account_name')<div class="text-red-600 text-sm">{{ $message }}</div>@enderror
            </div>
            <div>
              <label class="block text-sm font-medium">IBAN</label>
              <input
                type="text"
                name="bank_iban"
                value="{{ old('bank_iban') }}"
                class="mt-1 w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring focus:ring-blue-200"
              />
              @error('bank_iban')<div class="text-red-600 text-sm">{{ $message }}</div>@enderror
            </div>
            <div>
              <label class="block text-sm font-medium">SWIFT/BIC (neprivaloma)</label>
              <input
                type="text"
                name="bank_bic"
                value="{{ old('bank_bic') }}"
                class="mt-1 w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring focus:ring-blue-200"
              />
              @error('bank_bic')<div class="text-red-600 text-sm">{{ $message }}</div>@enderror
            </div>
          </div>

          <button
            type="submit"
            class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 rounded-lg flex justify-center items-center"
            :disabled="isLoading"
          >
            <span x-show="!isLoading">Atlikti mokėjimą</span>
            <span x-show="isLoading" class="inline-flex items-center">
              <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              Apdorojama...
            </span>
          </button>
        </form>
      </div>
    </template>

    <!-- Success block -->
    <template x-if="paymentSuccess">
  <div class="text-center py-8">
    <svg class="mx-auto h-16 w-16 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
    </svg>
    <h2 class="text-2xl font-semibold mb-2">Mokėjimas sėkmingai atliktas!</h2>
    <p class="text-gray-600 mb-6">Sąskaita #{{ $invoice->invoice_number }} apmokėta.</p>
    <a 
      href="{{ route('invoices.public-view', ['invoice' => $invoice->id, 'token' => $token]) }}" 
      class="inline-block px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg"
    >
      Grįžti į peržiūrą
    </a>
  </div>
</template>
  </div>
</body>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function(e) {
            // Pašalina tarpus TIK prieš submit
            const cardNumberInput = form.querySelector('input[name="card_number"]');
            if (cardNumberInput) {
                cardNumberInput.value = cardNumberInput.value.replace(/\s+/g, '');
            }
            // Expiry gali būti su /, todėl nereikia modifikuoti (jei backend validuoja su /)
        });
    }

    // Maskavimas – rodom kas 4 skaičius tarpelį (RODOM tik vizualiai)
    document.querySelectorAll('input[name="card_number"]').forEach(function(input) {
        input.addEventListener('input', function(e) {
            let value = this.value.replace(/\D/g, '').slice(0, 16);
            let formatted = '';
            for (let i = 0; i < value.length; i += 4) {
                if (i > 0) formatted += ' ';
                formatted += value.substr(i, 4);
            }
            this.value = formatted;
        });
    });
});
</script>

</html>