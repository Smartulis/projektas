<?php

use Illuminate\Support\Facades\Route;
use App\Models\Invoice;
use App\Models\Offer;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use App\Http\Controllers\PaymentController;

Route::get('/', fn() => redirect('/admin/login'));

Route::middleware(['auth'])->group(function () {
    Route::get('/offers/{offer}/pdf', function (Offer $offer) {
        $offer->load(['user.settings']);
        
        $lang = $offer->user->settings->language ?? config('app.locale');
        
        App::setLocale($lang);
        \Carbon\Carbon::setLocale($lang);
        setlocale(LC_ALL, $lang.'.UTF-8');
        
        $pdf = Pdf::loadView('offers.pdf', [
            'offer' => $offer,
            'forcedLocale' => $lang,
        ]);
        
        return $pdf->stream("offer-{$offer->estimate_number}.pdf");
    })->name('offer.pdf.preview');
     Route::get('/invoices/{invoice}/pdf', function (Invoice $invoice) {
        $invoice->load(['user.settings', 'items']);

        $lang = $invoice->user->settings->language 
              ?? config('app.locale');
        App::setLocale($lang);
        \Carbon\Carbon::setLocale($lang);

        $pdf = Pdf::loadView('invoices.pdf', [
            'invoice' => $invoice,
        ]);

        return $pdf
            ->setPaper('a4')
            ->stream($invoice->invoice_number . '.pdf');
    })->name('invoices.pdf');
});

Route::get('/invoices/public/{invoice}/{token}', function (Invoice $invoice, $token) {
    abort_if($invoice->public_token !== $token, 404);
    return view('invoices.public-invoice', compact('invoice', 'token'));
})->name('invoices.public-view');

Route::get('/offers/public/{offer}/{token}', function (Request $request, Offer $offer, string $token) {
    abort_if($offer->public_token !== $token, 404);

    if (! $request->hasValidSignature()) {
        if ($offer->status === 'sent') {
            $offer->update(['status' => 'viewed']);
        }
    }
    $offer->load(['customer', 'offerItems']);

    return view('offers.public-offer', compact('offer', 'token'));
})
->name('offers.public-view');

Route::post('/offers/public/{offer}/{token}/accept', function (Offer $offer, $token) {
    abort_if($offer->public_token !== $token, 404);

    if (in_array($offer->status, ['created', 'sent', 'viewed'], true)) {
        $offer->update(['status' => 'accepted']);
        session()->flash('status', 'accepted');
    }

    return redirect()->route('offers.public-view', [$offer, $token]);
})->name('offers.accept');

Route::post('/offers/public/{offer}/{token}/reject', function (Offer $offer, $token) {
    abort_if($offer->public_token !== $token, 404);

    if (in_array($offer->status, ['created', 'sent', 'viewed'], true)) {
        $offer->update(['status' => 'rejected']);
        session()->flash('status', 'rejected');
    }

    return redirect()->route('offers.public-view', [$offer, $token]);
})->name('offers.reject');

Route::post('/offers/public/{offer}/{token}/comment', function (Request $request, Offer $offer, $token) {
    abort_if($offer->public_token !== $token, 404);

    $data = $request->validate([
        'customer_comment' => ['nullable', 'string', 'max:1000'],
    ]);

    $offer->update([
        'customer_comment' => $data['customer_comment'],
    ]);

    session()->flash('status', 'commented');

    return redirect()->route('offers.public-view', [$offer, $token]);
})->name('offers.comment');

Route::post('/offers/public/{offer}/{token}/comment/delete', function (Request $request, Offer $offer, $token) {
    abort_if($offer->public_token !== $token, 404);

    if (! empty($offer->customer_comment)) {
        $offer->update(['customer_comment' => null]);
        session()->flash('status', 'deleted');
    }

    return redirect()->route('offers.public-view', [$offer, $token]);
})->name('offers.comment.delete');

Route::middleware(['auth'])->group(function () {
    // Rodom formą
    Route::get('invoices/{invoice}/pay/{token}', [PaymentController::class, 'show'])
    ->name('invoices.pay.show');
    // Apdorojam formos POST
    Route::post('invoices/{invoice}/pay/{token}', [PaymentController::class, 'publicProcess'])
    ->name('invoices.pay.process');
         Route::patch('invoices/{invoice}/cancel/{token}', [PaymentController::class, 'publicCancel'])
    ->name('invoices.cancel');
         
});