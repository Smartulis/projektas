<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    protected $table = 'customers';
    protected $primaryKey = 'customer_id';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'customer_number',
        'type',
        'status',
        'company_name',
        'company_code',
        'vat_number',
        'payment_term',
        'email',
        'phone',
        'address',
        'website',
        'bank_account',
        'bank_name',
        'payment_method',
        'city',
        'postal_code',
        'country',
        'notes',
        'documents',
    ];

    protected $casts = [
        'payment_term' => 'integer',
        'documents'    => 'array',
    ];

    protected static function booted()
    {
        static::creating(function (self $customer) {
            $last = self::max('customer_id') ?? 0;
            $customer->customer_number = sprintf('CUST%06d', $last + 1);
        });
    }

    public function invoices(): HasMany
    {
        // 2nd arg = foreign key on the invoices table
        // 3rd arg = local key on the customers table
        return $this->hasMany(Invoice::class, 'customer_id', 'customer_id');
    }

     public function offers(): HasMany
    {
        return $this->hasMany(Offer::class,   'customer_id', 'customer_id');
    }
}
