<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_gallery_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->string('path');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['event_id', 'sort_order']);
        });

        Schema::table('event_tickets', function (Blueprint $table) {
            $table->decimal('early_bird_price', 10, 2)->nullable()->after('price');
            $table->date('early_bird_ends_at')->nullable()->after('early_bird_price');
        });

        Schema::create('event_coupons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->string('code', 64);
            $table->string('discount_type')->default('percent');
            $table->decimal('discount_value', 10, 2);
            $table->unsignedInteger('max_uses')->nullable();
            $table->unsignedInteger('uses_count')->default(0);
            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['event_id', 'code']);
            $table->index(['event_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_coupons');
        Schema::table('event_tickets', function (Blueprint $table) {
            $table->dropColumn(['early_bird_price', 'early_bird_ends_at']);
        });
        Schema::dropIfExists('event_gallery_images');
    }
};
