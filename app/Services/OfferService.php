<?php

namespace App\Services;

class OfferService
{
    public function calculateFormTotals(callable $set, callable $get): void
{
    $items = collect($get('offerItems') ?? []);
    $items->each(function ($item, $index) use ($set) {
        $price        = (float) ($item['price'] ?? 0);
        $quantity     = (int)   ($item['quantity'] ?? 1);
        $discountType = $item['discount_type'] ?? 'percent';
        $discountVal  = (float) ($item['discount_value'] ?? 0);
        $taxRate      = (float) (($item['tax_rate'] ?? '-') === '-' ? 0 : $item['tax_rate']);

        $net = $price * $quantity;
        if ($discountType === 'percent') {
            $net *= (1 - ($discountVal / 100));
        } else {
            $net -= $discountVal;
        }
        $net = max(0, $net);

        $gross = $net * (1 + $taxRate);

        $set("offerItems.{$index}.total_price", round($gross, 2));
    });

    $subtotal = $items->sum(function ($item) {
        $price        = (float) ($item['price'] ?? 0);
        $quantity     = (int)   ($item['quantity'] ?? 1);
        $discountType = $item['discount_type'] ?? 'percent';
        $discountVal  = (float) ($item['discount_value'] ?? 0);

        $net = $price * $quantity;
        if ($discountType === 'percent') {
            $net *= (1 - ($discountVal / 100));
        } else {
            $net -= $discountVal;
        }
        return max(0, $net);
    });

    $taxAmount = $items->sum(function ($item) {
        $lineGross = (float) ($item['total_price'] ?? 0);
        $price     = (float) ($item['price'] ?? 0);
        $quantity  = (int)   ($item['quantity'] ?? 1);
        $discountType = $item['discount_type'] ?? 'percent';
        $discountVal  = (float) ($item['discount_value'] ?? 0);

        $net = $price * $quantity;
        if ($discountType === 'percent') {
            $net *= (1 - ($discountVal / 100));
        } else {
            $net -= $discountVal;
        }
        return max(0, $lineGross - $net);
    });

    $totalWithVat = $subtotal + $taxAmount;

    $set('subtotal',     round($subtotal, 2));
    $set('tax_amount',   round($taxAmount, 2));
    $set('total_with_vat', round($totalWithVat, 2));
}
}