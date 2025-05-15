<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\App;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Filament::serving(function () {
            if ($user = Auth::user()) {
                App::setLocale($user->settings->language ?? config('app.locale'));
            }
        });
    }
}
