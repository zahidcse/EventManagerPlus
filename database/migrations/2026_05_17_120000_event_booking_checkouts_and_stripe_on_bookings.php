<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_booking_checkouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->string('stripe_checkout_session_id')->nullable()->unique();
            $table->string('status')->default('pending');
            $table->unsignedInteger('amount_total_cents')->default(0);
            $table->string('currency', 3)->default('usd');
            $table->json('payload');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['event_id', 'status']);
        });

        Schema::table('event_bookings', function (Blueprint $table) {
            $table->string('stripe_checkout_session_id', 128)->nullable()->after('notes')->index();
        });
    }

    public function down(): void
    {
        Schema::table('event_bookings', function (Blueprint $table) {
            $table->dropIndex(['stripe_checkout_session_id']);
            $table->dropColumn('stripe_checkout_session_id');
        });

        Schema::dropIfExists('event_booking_checkouts');
    }
};
