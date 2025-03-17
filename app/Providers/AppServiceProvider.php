<?php

namespace App\Providers;

use App\Models\Province;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */

    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(UrlGenerator $url): void
    {
        Validator::extend('unique_province', function ($attribute, $value, $parameters, $validator) {
            $regionId = $validator->getData()['region_id'] ?? null;

            if ($regionId) {
                $existingProvince = Province::where('name', $value)
                    ->where('region_id', $regionId)
                    ->where('id', '<>', $validator->getData()['id'] ?? null)
                    ->exists();

                return !$existingProvince;
            }

            return true;
        }, 'A province with this name and region already exists.');

        if (env('APP_ENV') !== 'local') {
            $url->forceScheme('https');
        }

        Route::get('/john-1-3', function () {
            return view('vendor.filament.components.grid.system_helper');
        });
    }
}
