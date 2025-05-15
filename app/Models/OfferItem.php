<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfferItem extends Model
{
    use HasFactory;

    protected $primaryKey = 'item_id';

    protected $fillable = [
        'offer_id',
        'product_service_id',
        'name',
        'description',
        'price',
        'quantity',
        'tax_rate',
        'discount_value',
        'discount_type',
        'total_price',
        'unit_code',
    ];

    protected $casts = [
        'total_price' => 'decimal:2',
    ];

    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class, 'offer_id', 'offer_id');
    }

    public function productService(): BelongsTo
    {
        return $this->belongsTo(
            ProductService::class,
            'product_service_id',
            'product_service_id'
        );
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(MeasurementUnit::class);
    }
}
