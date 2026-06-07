<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_organizer')->default(false)->after('is_admin');
            $table->foreignId('organizer_id')
                ->nullable()
                ->after('is_organizer')
                ->constrained('organizers')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('organizer_id');
            $table->dropColumn('is_organizer');
        });
    }
};
