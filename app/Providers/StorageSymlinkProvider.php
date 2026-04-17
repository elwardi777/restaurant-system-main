<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\File;

class StorageSymlinkProvider extends ServiceProvider
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
     * 
     * This provider ensures that the storage symlink exists on every boot,
     * preventing the logo and other storage files from becoming inaccessible
     * after application restarts or deployment.
     */
    public function boot(): void
    {
        $this->ensureStorageSymlink();
    }

    /**
     * Ensure the storage symlink exists.
     * 
     * Creates the symlink from public/storage to storage/app/public if it doesn't exist.
     * This is critical for serving files stored in storage/app/public/ via public URLs.
     * 
     * @return void
     */
    private function ensureStorageSymlink(): void
    {
        try {
            $link = public_path('storage');
            $target = storage_path('app/public');

            // If the symlink already exists, do nothing
            if (is_link($link)) {
                return;
            }

            // If a regular directory exists at the link location, remove it
            if (is_dir($link) && !is_link($link)) {
                // Create a backup by renaming
                rename($link, public_path('storage.backup'));
            }

            // If a regular file exists, remove it
            if (is_file($link)) {
                unlink($link);
            }

            // Create the target directory if it doesn't exist
            if (!is_dir($target)) {
                mkdir($target, 0755, true);
            }

            // Create the symlink
            if (!is_link($link)) {
                // On Windows, use proper path handling
                if (PHP_OS_FAMILY === 'Windows') {
                    // Use absolute paths for Windows symlinks
                    $target = realpath($target);
                    $link = realpath(dirname($link)) . DIRECTORY_SEPARATOR . basename($link);
                    
                    // On Windows (non-admin), try junction as fallback
                    if (!symlink($target, $link)) {
                        // Try using exec for junction if symlink fails
                        exec("mklink /J \"$link\" \"$target\"");
                    }
                } else {
                    // On Linux/Mac, create relative symlink
                    symlink($target, $link);
                }
            }
        } catch (\Exception $e) {
            // Log the error but don't fail the application
            \Log::warning('Failed to create storage symlink: ' . $e->getMessage());
        }
    }
}
