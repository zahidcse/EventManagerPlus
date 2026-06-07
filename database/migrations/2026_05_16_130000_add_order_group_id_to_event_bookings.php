<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_bookings', function (Blueprint $table): void {
            $table->string('order_group_id', 64)->nullable()->after('event_ticket_id');
            $table->index(['event_id', 'order_group_id'], 'event_bookings_event_order_group_idx');
        });

        DB::table('event_bookings')
            ->select(['id'])
            ->orderBy('id')
            ->chunkById(500, static function ($rows): void {
                foreach ($rows as $row) {
                    DB::table('event_bookings')
                        ->where('id', (int) $row->id)
                        ->whereNull('order_group_id')
                        ->update(['order_group_id' => 'legacy-'.(string) $row->id]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('event_bookings', function (Blueprint $table): void {
            $table->dropIndex('event_bookings_event_order_group_idx');
            $table->dropColumn('order_group_id');
        });
    }
};

