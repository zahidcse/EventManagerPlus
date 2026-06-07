<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->string('home_hero_badge', 255)->nullable();
            $table->string('home_hero_headline_before', 255)->nullable();
            $table->string('home_hero_headline_highlight', 255)->nullable();
            $table->string('home_hero_headline_suffix', 255)->nullable();
            $table->text('home_hero_lead')->nullable();
            $table->string('home_hero_cta_primary_label', 128)->nullable();
            $table->string('home_hero_cta_secondary_label', 128)->nullable();
            $table->string('home_hero_stat_1_label', 128)->nullable();
            $table->string('home_hero_stat_2_value', 64)->nullable();
            $table->string('home_hero_stat_2_label', 128)->nullable();
            $table->string('home_hero_stat_3_value', 64)->nullable();
            $table->string('home_hero_stat_3_label', 128)->nullable();

            $table->string('home_how_eyebrow', 128)->nullable();
            $table->string('home_how_title_before', 255)->nullable();
            $table->string('home_how_title_highlight', 255)->nullable();
            $table->string('home_how_step1_title', 128)->nullable();
            $table->text('home_how_step1_description')->nullable();
            $table->string('home_how_step2_title', 128)->nullable();
            $table->text('home_how_step2_description')->nullable();
            $table->string('home_how_step3_title', 128)->nullable();
            $table->text('home_how_step3_description')->nullable();

            $table->string('home_faq_eyebrow', 128)->nullable();
            $table->string('home_faq_title_before', 255)->nullable();
            $table->string('home_faq_title_highlight', 255)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn([
                'home_hero_badge',
                'home_hero_headline_before',
                'home_hero_headline_highlight',
                'home_hero_headline_suffix',
                'home_hero_lead',
                'home_hero_cta_primary_label',
                'home_hero_cta_secondary_label',
                'home_hero_stat_1_label',
                'home_hero_stat_2_value',
                'home_hero_stat_2_label',
                'home_hero_stat_3_value',
                'home_hero_stat_3_label',
                'home_how_eyebrow',
                'home_how_title_before',
                'home_how_title_highlight',
                'home_how_step1_title',
                'home_how_step1_description',
                'home_how_step2_title',
                'home_how_step2_description',
                'home_how_step3_title',
                'home_how_step3_description',
                'home_faq_eyebrow',
                'home_faq_title_before',
                'home_faq_title_highlight',
            ]);
        });
    }
};
