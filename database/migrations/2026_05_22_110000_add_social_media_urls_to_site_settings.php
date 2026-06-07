<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table): void {
            $table->string('social_facebook_url', 500)->nullable()->after('contact_phone');
            $table->string('social_twitter_url', 500)->nullable()->after('social_facebook_url');
            $table->string('social_instagram_url', 500)->nullable()->after('social_twitter_url');
            $table->string('social_youtube_url', 500)->nullable()->after('social_instagram_url');
            $table->string('social_linkedin_url', 500)->nullable()->after('social_youtube_url');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table): void {
            $table->dropColumn([
                'social_facebook_url',
                'social_twitter_url',
                'social_instagram_url',
                'social_youtube_url',
                'social_linkedin_url',
            ]);
        });
    }
};
