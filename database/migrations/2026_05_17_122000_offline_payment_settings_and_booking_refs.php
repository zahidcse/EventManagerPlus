<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table): void {
            $table->boolean('payment_cash_enabled')->default(false)->after('razorpay_key_secret');
            $table->boolean('payment_bank_transfer_enabled')->default(false)->after('payment_cash_enabled');
            $table->text('bank_transfer_instructions')->nullable()->after('payment_bank_transfer_enabled');
        });

        Schema::table('event_bookings', function (Blueprint $table): void {
            $table->string('offline_payment_method', 32)->nullable()->after('paypal_order_id');
            $table->string('offline_payment_reference', 191)->nullable()->after('offline_payment_method');
        });
    }

    public function down(): void
    {
        Schema::table('event_bookings', function (Blueprint $table): void {
            $table->dropColumn(['offline_payment_method', 'offline_payment_reference']);
        });

        Schema::table('site_settings', function (Blueprint $table): void {
            $table->dropColumn(['payment_cash_enabled', 'payment_bank_transfer_enabled', 'bank_transfer_instructions']);
        });
    }
};
