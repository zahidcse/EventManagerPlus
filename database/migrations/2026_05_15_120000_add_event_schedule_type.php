<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('schedule_type', 32)->default('single')->after('ends_at');
            $table->json('recurrence_weekdays')->nullable()->after('schedule_type');
            $table->date('recurrence_ends_on')->nullable()->after('recurrence_weekdays');
            $table->unsignedSmallInteger('repeat_every_days')->nullable()->after('recurrence_ends_on');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['schedule_type', 'recurrence_weekdays', 'recurrence_ends_on', 'repeat_every_days']);
        });
    }
};
