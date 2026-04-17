<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Storage;

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
        // Ensure the restaurant-logos directory exists with proper permissions
        $this->ensureLogosDirectoryExists();
    }

    /**
     * Ensure the restaurant-logos directory exists and is writable.
     * 
     * This is called on every boot to ensure logos can be uploaded
     * even if the directory was accidentally deleted.
     */
    private function ensureLogosDirectoryExists(): void
    {
        try {
            $oldUmask = umask(0);
            if (!Storage::disk('public')->exists('restaurant-logos')) {
                Storage::disk('public')->makeDirectory('restaurant-logos', 0755, true);
            }
            umask($oldUmask);
        } catch (\Exception $e) {
            \Log::warning('Failed to ensure restaurant-logos directory: ' . $e->getMessage());
        }
    }
}
