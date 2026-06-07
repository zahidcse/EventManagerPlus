<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->foreignId('event_ticket_id')->nullable()->constrained('event_tickets')->nullOnDelete();
            $table->string('attendee_name');
            $table->string('email')->nullable();
            $table->string('phone', 64)->nullable();
            $table->string('status')->default('confirmed');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['event_id', 'created_at']);
        });

        $events = DB::table('events')->where('registrations_count', '>', 0)->select(['id', 'registrations_count'])->get();
        foreach ($events as $event) {
            $n = min((int) $event->registrations_count, 500);
            for ($i = 1; $i <= $n; $i++) {
                DB::table('event_bookings')->insert([
                    'event_id' => $event->id,
                    'event_ticket_id' => null,
                    'attendee_name' => 'Guest '.$i,
                    'email' => 'guest'.$i.'.'.$event->id.'@bookings.local',
                    'phone' => null,
                    'status' => 'confirmed',
                    'notes' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('event_bookings');
    }
};
