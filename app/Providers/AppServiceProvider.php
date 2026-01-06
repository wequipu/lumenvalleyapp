<?php

namespace App\Providers;

use Illuminate\Support\Facades\Schema;
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
    public function boot(): void
    {
        Schema::defaultStringLength(191);

        // Define morph map to handle service_only type properly
        \Illuminate\Database\Eloquent\Relations\Relation::morphMap([
            'accommodation' => 'App\\Models\\Accommodation',
            'conference_room' => 'App\\Models\\ConferenceRoom',
            'service_only' => 'App\\Models\\ServiceOnlyType', // This will prevent the "class not found" error
        ]);
    }
}
