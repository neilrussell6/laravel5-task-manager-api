<?php

namespace App\Providers;

use Faker\Generator as FakerGenerator;
use Faker\Factory as FakerFactory;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot()
    {
        //
    }

    /**
     * Register any application services.
     */
    public function register()
    {
        $locale = env('APP_LOCALE') ?: "en_US";
        $this->app->singleton(FakerGenerator::class, function() use ($locale) {
            return FakerFactory::create($locale);
        });
    }
}
