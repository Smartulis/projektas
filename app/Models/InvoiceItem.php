<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'name',
        'product_service_id',
        'description',
        'quantity',
        'unit_code',
        'price',
        'tax_rate',
        'total_price',
        'discount_type',
        'discount_value',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'price' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'total_price' => 'decimal:2',
        'discount_value' => 'decimal:2', 
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function productService(): BelongsTo
    {
        return $this->belongsTo(\App\Models\ProductService::class);
    }
}