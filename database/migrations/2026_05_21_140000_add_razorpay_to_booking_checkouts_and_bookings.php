<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_booking_checkouts', function (Blueprint $table): void {
            $table->string('razorpay_order_id', 48)->nullable()->unique()->after('paypal_order_id');
        });

        Schema::table('event_bookings', function (Blueprint $table): void {
            $table->string('razorpay_payment_id', 48)->nullable()->after('paypal_order_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('event_bookings', function (Blueprint $table): void {
            $table->dropIndex(['razorpay_payment_id']);
            $table->dropColumn('razorpay_payment_id');
        });

        Schema::table('event_booking_checkouts', function (Blueprint $table): void {
            $table->dropUnique(['razorpay_order_id']);
            $table->dropColumn('razorpay_order_id');
        });
    }
};
