<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyDetail extends Model
{
    protected $fillable = [
        'user_setting_id',
        'company_name',
        'company_address',
        'vat_code',
        'company_code',
        'bank_account',
        'phone_number',
    ];

    public function userSetting()
{
    return $this->belongsTo(UserSetting::class, 'user_id', 'user_id');
}
}
