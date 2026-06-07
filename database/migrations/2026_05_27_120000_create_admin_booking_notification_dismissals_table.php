<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_booking_notification_dismissals', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_booking_id')->constrained('event_bookings')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'event_booking_id'], 'abnd_user_booking_uq');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_booking_notification_dismissals');
    }
};
