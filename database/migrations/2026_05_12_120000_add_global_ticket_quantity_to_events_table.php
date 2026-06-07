<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->boolean('global_ticket_quantity_enabled')->default(false)->after('capacity');
            $table->unsignedInteger('global_ticket_quantity')->default(0)->after('global_ticket_quantity_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['global_ticket_quantity_enabled', 'global_ticket_quantity']);
        });
    }
};
