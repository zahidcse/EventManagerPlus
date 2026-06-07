<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table): void {
            $table->boolean('report_ai_enabled')->default(false);
            $table->string('report_ai_provider', 32)->default('openai');
            $table->string('report_ai_model', 128)->nullable();
            $table->string('report_ai_api_base_url_override', 2048)->nullable();
            $table->longText('report_ai_api_key')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table): void {
            $table->dropColumn([
                'report_ai_enabled',
                'report_ai_provider',
                'report_ai_model',
                'report_ai_api_base_url_override',
                'report_ai_api_key',
            ]);
        });
    }
};
