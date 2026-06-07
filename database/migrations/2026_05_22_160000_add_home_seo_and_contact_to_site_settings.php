<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->string('home_meta_title', 255)->nullable();
            $table->text('home_meta_description')->nullable();

            $table->string('home_contact_eyebrow', 128)->nullable();
            $table->string('home_contact_title_before', 255)->nullable();
            $table->string('home_contact_title_highlight', 255)->nullable();
            $table->text('home_contact_lead')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn([
                'home_meta_title',
                'home_meta_description',
                'home_contact_eyebrow',
                'home_contact_title_before',
                'home_contact_title_highlight',
                'home_contact_lead',
            ]);
        });
    }
};
