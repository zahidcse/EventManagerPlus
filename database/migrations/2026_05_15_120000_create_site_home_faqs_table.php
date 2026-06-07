<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_home_faqs', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->string('question');
            $table->text('answer');
            $table->timestamps();
        });

        $now = now();
        DB::table('site_home_faqs')->insert([
            [
                'sort_order' => 0,
                'question' => 'How do I receive my tickets?',
                'answer' => 'After booking, you receive confirmation by email. Your organizer may send QR codes or entry instructions separately.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'sort_order' => 1,
                'question' => 'Can I get a refund?',
                'answer' => 'Refund rules depend on each event. Check the event listing or contact the organizer through the details on your confirmation.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'sort_order' => 2,
                'question' => 'Are the prices final?',
                'answer' => 'Ticket prices are set per event. Any fees should be shown before you complete checkout.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'sort_order' => 3,
                'question' => 'Who runs this site?',
                'answer' => 'This site lists events managed in the admin panel. For event-specific questions, use the contact details for that listing.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('site_home_faqs');
    }
};
