<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique('name');
        });

        $defaultNames = [
            'Technology & Innovation',
            'Business & Corporate',
            'Arts & Entertainment',
            'Health & Wellness',
            'Community Event',
            'Internal Event',
            'Executive Workshop',
        ];

        foreach ($defaultNames as $i => $name) {
            DB::table('event_categories')->insert([
                'name' => $name,
                'sort_order' => $i,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        Schema::table('events', function (Blueprint $table) {
            $table->foreignId('event_category_id')->nullable()->after('category')->constrained('event_categories')->nullOnDelete();
        });

        foreach ($defaultNames as $name) {
            $id = DB::table('event_categories')->where('name', $name)->value('id');
            if ($id !== null) {
                DB::table('events')->where('category', $name)->update(['event_category_id' => $id]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropForeign(['event_category_id']);
            $table->dropColumn('event_category_id');
        });

        Schema::dropIfExists('event_categories');
    }
};
