<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();
            $table->string('site_name')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('admin_theme')->default('light');

            $table->string('smtp_host')->nullable();
            $table->unsignedSmallInteger('smtp_port')->nullable();
            $table->string('smtp_username')->nullable();
            $table->text('smtp_password')->nullable();
            $table->string('smtp_encryption')->nullable();
            $table->string('smtp_from_address')->nullable();
            $table->string('smtp_from_name')->nullable();

            $table->boolean('stripe_enabled')->default(false);
            $table->string('stripe_public_key')->nullable();
            $table->text('stripe_secret_key')->nullable();
            $table->text('stripe_webhook_secret')->nullable();

            $table->boolean('paypal_enabled')->default(false);
            $table->string('paypal_client_id')->nullable();
            $table->text('paypal_secret')->nullable();
            $table->string('paypal_mode')->default('sandbox');

            $table->boolean('razorpay_enabled')->default(false);
            $table->string('razorpay_key_id')->nullable();
            $table->text('razorpay_key_secret')->nullable();

            $table->timestamps();
        });

        DB::table('site_settings')->insert([
            'site_name' => 'Event Manager',
            'admin_theme' => 'light',
            'paypal_mode' => 'sandbox',
            'stripe_enabled' => false,
            'paypal_enabled' => false,
            'razorpay_enabled' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('site_settings');
    }
};
