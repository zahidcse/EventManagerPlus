<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_faqs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->string('question', 500);
            $table->longText('answer')->nullable();
            $table->timestamps();

            $table->index(['event_id', 'sort_order']);
        });

        Schema::create('event_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->string('name');
            $table->decimal('price', 10, 2)->default(0);
            $table->unsignedInteger('quantity')->default(0);
            $table->date('sales_start')->nullable();
            $table->date('sales_end')->nullable();
            $table->timestamps();

            $table->index(['event_id', 'sort_order']);
            $table->index('name');
        });

        Schema::create('event_additional_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->string('name');
            $table->decimal('price', 10, 2)->default(0);
            $table->timestamps();

            $table->index(['event_id', 'sort_order']);
            $table->index('name');
        });

        $events = DB::table('events')->select(['id', 'tickets_json', 'additional_services_json', 'faq_json'])->get();

        foreach ($events as $event) {
            $faqRows = $this->decodeJson($event->faq_json ?? null);
            if (is_array($faqRows)) {
                foreach (array_values($faqRows) as $i => $row) {
                    if (! is_array($row)) {
                        continue;
                    }
                    $q = trim((string) ($row['question'] ?? ''));
                    $a = trim((string) ($row['answer'] ?? ''));
                    if ($q === '' && $a === '') {
                        continue;
                    }
                    DB::table('event_faqs')->insert([
                        'event_id' => $event->id,
                        'sort_order' => (int) $i,
                        'question' => $q,
                        'answer' => $a !== '' ? $a : null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            $ticketRows = $this->decodeJson($event->tickets_json ?? null);
            if (is_array($ticketRows)) {
                foreach (array_values($ticketRows) as $i => $row) {
                    if (! is_array($row)) {
                        continue;
                    }
                    $name = trim((string) ($row['name'] ?? ''));
                    if ($name === '') {
                        continue;
                    }
                    $start = $this->parseDate($row['sales_start'] ?? null);
                    $end = $this->parseDate($row['sales_end'] ?? null);
                    DB::table('event_tickets')->insert([
                        'event_id' => $event->id,
                        'sort_order' => (int) $i,
                        'name' => $name,
                        'price' => round((float) ($row['price'] ?? 0), 2),
                        'quantity' => max(0, (int) ($row['quantity'] ?? 0)),
                        'sales_start' => $start,
                        'sales_end' => $end,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            $serviceRows = $this->decodeJson($event->additional_services_json ?? null);
            if (is_array($serviceRows)) {
                foreach (array_values($serviceRows) as $i => $row) {
                    if (! is_array($row)) {
                        continue;
                    }
                    $name = trim((string) ($row['name'] ?? ''));
                    if ($name === '') {
                        continue;
                    }
                    DB::table('event_additional_services')->insert([
                        'event_id' => $event->id,
                        'sort_order' => (int) $i,
                        'name' => $name,
                        'price' => round((float) ($row['price'] ?? 0), 2),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }

        foreach ($events as $event) {
            $cap = (int) DB::table('event_tickets')->where('event_id', $event->id)->sum('quantity');
            DB::table('events')->where('id', $event->id)->update(['capacity' => $cap]);
        }

        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['tickets_json', 'additional_services_json', 'faq_json']);
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->json('tickets_json')->nullable();
            $table->json('additional_services_json')->nullable();
            $table->json('faq_json')->nullable();
        });

        foreach (DB::table('events')->pluck('id') as $eventId) {
            $tickets = DB::table('event_tickets')
                ->where('event_id', $eventId)
                ->orderBy('sort_order')
                ->get()
                ->map(function ($t) {
                    return [
                        'name' => $t->name,
                        'price' => (float) $t->price,
                        'quantity' => (int) $t->quantity,
                        'sales_start' => $t->sales_start,
                        'sales_end' => $t->sales_end,
                    ];
                })
                ->values()
                ->all();

            $services = DB::table('event_additional_services')
                ->where('event_id', $eventId)
                ->orderBy('sort_order')
                ->get()
                ->map(fn ($r) => ['name' => $r->name, 'price' => (float) $r->price])
                ->values()
                ->all();

            $faqs = DB::table('event_faqs')
                ->where('event_id', $eventId)
                ->orderBy('sort_order')
                ->get()
                ->map(fn ($r) => ['question' => $r->question, 'answer' => (string) ($r->answer ?? '')])
                ->values()
                ->all();

            DB::table('events')->where('id', $eventId)->update([
                'tickets_json' => count($tickets) > 0 ? $this->encodeJson($tickets) : null,
                'additional_services_json' => count($services) > 0 ? $this->encodeJson($services) : null,
                'faq_json' => count($faqs) > 0 ? $this->encodeJson($faqs) : null,
            ]);
        }

        Schema::dropIfExists('event_additional_services');
        Schema::dropIfExists('event_tickets');
        Schema::dropIfExists('event_faqs');
    }

    private function decodeJson(mixed $value): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_array($value)) {
            return $value;
        }

        $decoded = json_decode((string) $value, true);

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * @param  array<int, mixed>  $data
     */
    private function encodeJson(array $data): string
    {
        return json_encode($data, JSON_THROW_ON_ERROR);
    }

    private function parseDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return $value;
        }

        try {
            return Carbon::parse((string) $value)->format('Y-m-d');
        } catch (Throwable) {
            return null;
        }
    }
};
