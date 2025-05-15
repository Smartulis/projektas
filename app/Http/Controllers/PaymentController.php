<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Invoice;
use Illuminate\Routing\Controller;
use App\Models\Payment;

class PaymentController extends Controller
{
    public function publicProcess(Request $request, Invoice $invoice, $token)
    {
        abort_if($invoice->public_token !== $token, 404);

        $data = $request->validate([
            'method'             => ['required', 'in:card,bank'],

            'cardholder_name'    => ['exclude_unless:method,card', 'required', 'string'],
            'card_number'        => ['exclude_unless:method,card', 'required', 'numeric'],
            'card_expiry'        => ['exclude_unless:method,card', 'required', 'date_format:m/y'],
            'card_cvv'           => ['exclude_unless:method,card', 'required', 'digits:3'],

            'bank_account_name'  => ['exclude_unless:method,bank', 'required', 'string'],
            'bank_iban'          => ['exclude_unless:method,bank', 'required', 'string'],
            'bank_bic'           => ['exclude_unless:method,bank', 'nullable', 'string'],
        ]);

        $cardLastFour = $data['method'] === 'card'
            ? substr($data['card_number'], -4)
            : null;

        $bankAccount = $data['method'] === 'bank'
            ? $data['bank_iban']
            : null;

        Payment::create([
    'invoice_id'      => $invoice->id,
    'amount'          => $invoice->total_with_vat,
    'method'          => $data['method'],
    'paid_at'         => now(),
    'card_last_four'  => $data['method'] === 'card'
        ? substr($data['card_number'], -4)
        : null,
    'bank_account'    => $data['method'] === 'bank'
        ? $data['bank_iban']
        : null,
]);

        $invoice->update([
            'status'  => 'paid',
            'paid_at' => now(),
        ]);

        return redirect()
            ->route('invoices.pay.show', ['invoice' => $invoice->id, 'token' => $token])
            ->with('payment_success', true);
    }

    public function publicCancel(Request $request, Invoice $invoice, $token)
    {
        abort_if($invoice->public_token !== $token, 404);

        $invoice->update(['status' => 'cancelled']);

        return redirect()
            ->route('invoices.public-view', ['invoice' => $invoice->id, 'token' => $token])
            ->with('cancel_success', true);
    }

    public function show(Invoice $invoice, string $token)
    {
        abort_if($invoice->public_token !== $token, 404);

        return view('invoices.pay', compact('invoice', 'token'));
    }
}
