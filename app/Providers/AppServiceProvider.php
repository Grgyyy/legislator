<?php

namespace App\Providers;

use App\Models\Province;
use Illuminate\Support\ServiceProvider;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\Facades\Validator;

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

        if (env('APP_ENV') !== 'local')
        {
            // $url->forceHttps(true);
            $url->forceScheme('https');
        }
    }
}
