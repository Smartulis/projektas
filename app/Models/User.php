<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Models\UserSetting;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function settings(): HasOne
    {
        return $this->hasOne(UserSetting::class);
    }

    public function getSettings()
    {
        return $this->settings()->firstOrCreate();
    }

    /**
     * Filament panelės prieigos leidimas
     */
    public function canAccessPanel(Panel $panel): bool
    {
        // Laikinai leidžiam visiems prisijungti
        return true;

        // Arba jei nori tikrinti el. pašto domeną ir patvirtinimą:
        // return str_ends_with($this->email, '@tavodomenas.lt') && $this->hasVerifiedEmail();
    }
}
