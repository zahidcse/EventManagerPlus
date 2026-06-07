<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_bookings', function (Blueprint $table): void {
            $table->string('check_in_token', 64)->nullable()->unique();
            $table->timestamp('checked_in_at')->nullable();
        });

        DB::table('event_bookings')
            ->select(['id'])
            ->whereNull('check_in_token')
            ->orderBy('id')
            ->lazyById()
            ->each(function (object $row): void {
                DB::table('event_bookings')
                    ->where('id', $row->id)
                    ->update(['check_in_token' => Str::lower(Str::random(48))]);
            });
    }

    public function down(): void
    {
        Schema::table('event_bookings', function (Blueprint $table): void {
            $table->dropColumn(['check_in_token', 'checked_in_at']);
        });
    }
};
