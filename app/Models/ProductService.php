<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductService extends Model
{
    protected $table = 'products_services';

    protected $primaryKey = 'product_service_id';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'name',
        'description',
        'price_without_vat',
        'vat_rate',
        'price_with_vat',
        'currency',
        'unit_id',
        'stock_quantity',
        'sku',
        'status', 
        'image',
    ];

    protected $casts = [
        'price_without_vat' => 'decimal:2',
        'vat_rate'          => 'decimal:2',
        'price_with_vat'    => 'decimal:2',
        'stock_quantity'    => 'integer',
    ];

    /**
     * Each product/service belongs to one measurement unit.
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(\App\Models\MeasurementUnit::class, 'unit_id');
    }
}
