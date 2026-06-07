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
            $table->string('paypal_order_id')->nullable()->unique()->after('stripe_checkout_session_id');
        });

        Schema::table('event_bookings', function (Blueprint $table): void {
            $table->string('paypal_order_id', 64)->nullable()->after('stripe_checkout_session_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('event_bookings', function (Blueprint $table): void {
            $table->dropIndex(['paypal_order_id']);
            $table->dropColumn('paypal_order_id');
        });

        Schema::table('event_booking_checkouts', function (Blueprint $table): void {
            $table->dropUnique(['paypal_order_id']);
            $table->dropColumn('paypal_order_id');
        });
    }
};
