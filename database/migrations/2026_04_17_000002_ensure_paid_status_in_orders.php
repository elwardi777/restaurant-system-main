<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Ensures the 'paid' status is available in the orders table ENUM.
     * This fixes the error when trying to update order status to 'paid'.
     */
    public function up(): void
    {
        // ✅ FIX: Ensure 'paid' status is in the ENUM
        DB::statement("ALTER TABLE orders MODIFY status ENUM('pending', 'preparing', 'ready', 'served', 'paid', 'cancelled') NOT NULL DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to previous ENUM without 'paid' if needed
        DB::statement("ALTER TABLE orders MODIFY status ENUM('pending', 'preparing', 'ready', 'served', 'cancelled') NOT NULL DEFAULT 'pending'");
    }
};
