<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table): void {
            $table->boolean('sslcommerz_enabled')->default(false)->after('bank_transfer_instructions');
            $table->string('sslcommerz_store_id', 64)->nullable()->after('sslcommerz_enabled');
            $table->text('sslcommerz_store_password')->nullable()->after('sslcommerz_store_id');
            $table->string('sslcommerz_mode', 16)->default('sandbox')->after('sslcommerz_store_password');
        });

        Schema::table('event_booking_checkouts', function (Blueprint $table): void {
            $table->string('sslcommerz_tran_id', 48)->nullable()->unique()->after('razorpay_order_id');
        });

        Schema::table('event_bookings', function (Blueprint $table): void {
            $table->string('sslcommerz_val_id', 64)->nullable()->after('razorpay_payment_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('event_bookings', function (Blueprint $table): void {
            $table->dropIndex(['sslcommerz_val_id']);
            $table->dropColumn('sslcommerz_val_id');
        });

        Schema::table('event_booking_checkouts', function (Blueprint $table): void {
            $table->dropUnique(['sslcommerz_tran_id']);
            $table->dropColumn('sslcommerz_tran_id');
        });

        Schema::table('site_settings', function (Blueprint $table): void {
            $table->dropColumn(['sslcommerz_enabled', 'sslcommerz_store_id', 'sslcommerz_store_password', 'sslcommerz_mode']);
        });
    }
};
