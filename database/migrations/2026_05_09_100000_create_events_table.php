<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organizer_id')->nullable()->constrained('organizers')->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('category')->nullable();
            $table->string('visibility')->default('public');
            $table->text('description')->nullable();
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('ends_at')->nullable();
            $table->string('cover_image_path')->nullable();
            $table->string('status')->default('draft');

            $table->string('location_type')->default('physical');

            $table->string('venue_street')->nullable();
            $table->string('venue_city')->nullable();
            $table->string('venue_state')->nullable();
            $table->string('venue_postal')->nullable();
            $table->string('venue_country')->nullable();

            $table->string('streaming_platform')->nullable();
            $table->string('meeting_url', 2048)->nullable();

            $table->json('tickets_json')->nullable();
            $table->unsignedInteger('capacity')->default(0);
            $table->unsignedInteger('registrations_count')->default(0);

            $table->string('fee_handling')->nullable();
            $table->unsignedTinyInteger('max_tickets_per_customer')->nullable();

            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('email_subject')->nullable();
            $table->text('email_body')->nullable();
            $table->json('faq_json')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
