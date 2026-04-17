<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * This seeder initializes default application settings including:
     * - Restaurant information (name, logo path, contact details)
     * - Payment settings
     * - Order settings
     * - Notification preferences
     * - Localization settings
     * - Receipt settings
     * - Stock settings
     * - Security settings
     */
    public function run(): void
    {
        $settings = [
            // Restaurant Information
            ['key' => 'restaurant_name', 'value' => 'RestauPro'],
            ['key' => 'restaurant_address', 'value' => ''],
            ['key' => 'restaurant_phone', 'value' => ''],
            ['key' => 'restaurant_email', 'value' => ''],
            ['key' => 'restaurant_logo_path', 'value' => ''],  // Will be set when logo is uploaded
            ['key' => 'opening_hours', 'value' => 'Lundi - Dimanche: 08:00 - 23:00'],

            // Payment Settings
            ['key' => 'currency', 'value' => 'MAD'],
            ['key' => 'tax_rate', 'value' => '20.0'],
            ['key' => 'payment_cash', 'value' => '1'],
            ['key' => 'payment_card', 'value' => '1'],
            ['key' => 'payment_online', 'value' => '0'],

            // Order Settings
            ['key' => 'order_dine_in', 'value' => '1'],
            ['key' => 'order_takeaway', 'value' => '1'],
            ['key' => 'order_delivery', 'value' => '0'],
            ['key' => 'default_order_status', 'value' => 'pending'],
            ['key' => 'auto_update_order_status', 'value' => '0'],

            // Notifications
            ['key' => 'notify_email', 'value' => '1'],
            ['key' => 'notify_low_stock', 'value' => '1'],
            ['key' => 'notify_sms', 'value' => '0'],

            // Localization
            ['key' => 'language', 'value' => 'fr'],
            ['key' => 'timezone', 'value' => 'Africa/Casablanca'],
            ['key' => 'date_format', 'value' => 'd/m/Y'],

            // Receipt Settings
            ['key' => 'receipt_show_logo', 'value' => '1'],
            ['key' => 'receipt_footer_message', 'value' => 'Merci pour votre visite'],
            ['key' => 'receipt_show_tax_details', 'value' => '1'],

            // Stock Settings
            ['key' => 'low_stock_threshold', 'value' => '5'],
            ['key' => 'auto_stock_deduction', 'value' => '1'],

            // Security Settings
            ['key' => 'min_password_length', 'value' => '8'],
            ['key' => 'session_timeout_minutes', 'value' => '120'],
            ['key' => 'enable_2fa', 'value' => '0'],

            // Application Settings (backward compatibility)
            ['key' => 'app_name', 'value' => 'RestauPro'],
            ['key' => 'app_url', 'value' => 'http://localhost'],
            ['key' => 'notifications', 'value' => '1'],
            ['key' => 'maintenance_mode', 'value' => '0'],
            ['key' => 'backup_enabled', 'value' => '1'],
            ['key' => 'debug_mode', 'value' => '0'],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(
                ['key' => $setting['key']],
                ['value' => $setting['value']]
            );
        }
    }
}
