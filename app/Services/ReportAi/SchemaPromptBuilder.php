<?php

declare(strict_types=1);

namespace App\Services\ReportAi;

use Illuminate\Support\Facades\Config;

final class SchemaPromptBuilder
{
    /**
     * Human-oriented schema excerpt for prompt injection (SQLite / MySQL).
     */
    public static function build(): string
    {
        /** @var list<string> $allowed */
        $allowed = Config::get('report_ai.allowed_tables', []);
        /** @phpstan-ignore-next-line */
        $driver = (string) config('database.connections.'.config('database.default').'.driver', 'sqlite');

        $driverHint = match ($driver) {
            'mysql', 'mariadb' => 'Dialect: MySQL / MariaDB. Prefer SQL that runs on MySQL or MariaDB.',
            'pgsql' => 'Dialect: PostgreSQL.',
            default => 'Dialect: SQLite. Use SQLite date helpers when needed (e.g. datetime("now"), date("now", "-6 months")).',
        };

        $tablesHint = '`'.implode('`, `', $allowed).'`. ONLY these base tables — no aliases that hide other tables; subqueries inside FROM must only reference allowed tables too. Never reference users, migrations, installers, sqlite_master or information_schema.';

        return <<<MARKDOWN
You are an expert analyst and database reporter for this event ticketing app.
{$driverHint}

## Allowed tables
{$tablesHint}

## Relationships (JOIN hints)
- `events.organizer_id` → `organizers.id`
- `events.event_category_id` → `event_categories.id`
- `event_bookings.event_id` → `events.id`
- `event_bookings.event_ticket_id` → `event_tickets.id` (nullable)
- `event_bookings.user_id` exists but do NOT join `users` (that table is not part of AI reporting schema)
- `event_tickets.event_id`, `event_faqs.event_id`, `event_additional_services.event_id` → `events.id`
- `event_speaker.event_id` → `events.id`, `event_speaker.speaker_id` → `speakers.id`
- `event_booking_checkouts.event_id` → `events.id`

## Bookings (`event_bookings`)
- `created_at`: when booked
- `status` — **exact string values in the database** (there is **no** bare `pending` on this column):
  - `confirmed`: paid online, free ticket flows, or admin-registered attendees
  - `pending_offline_payment`: **offline payment not yet verified** (cash/bank). Questions like “pending bookings”, “awaiting payment”, “unpaid offline” → filter `status = 'pending_offline_payment'` (not `pending`).
  - `checked_in`: booking was marked checked in (see also `checked_in_at`)
- `checked_in_at`: NULL = not checked in
- Offline fields: `offline_payment_method`, `offline_payment_reference`
- Online gateways (non-null IDs indicate channel):
  - `stripe_checkout_session_id`: Stripe card checkout
  - `paypal_order_id`: PayPal
  - `razorpay_payment_id`: Razorpay
  - `sslcommerz_val_id`: SSLCommerz
- Attendee / customer on the booking row: `attendee_name`, `email` (nullable)

### Booking lists — event and booking details
Whenever the answer is a **list of bookings** (one result row per booking: registrations, attendees, check-ins, pending payments, exports-style detail, etc.):

**Always include** (identifiers & contact):
- `JOIN events ON events.id = event_bookings.event_id`.
- **Event name:** `events.title` (alias e.g. `event_name`).
- **Customer:** `event_bookings.attendee_name`, `event_bookings.email`; include **`event_bookings.phone`** when present.

**Also include** full context by default (omit only if the user explicitly asks for a minimal column set, or the query is a pure aggregate with no detail rows):
- **Event details:** `events.starts_at`, `events.ends_at`; venue/location fields such as `events.venue_city`, `events.venue_state`, `events.venue_country`, `events.venue_street` as useful; **`LEFT JOIN organizers ON organizers.id = events.organizer_id`** and select **`organizers.name`** (e.g. alias `organizer_name`).
- **Booking details:** `event_bookings.id` (booking id), **`event_bookings.created_at`** (booked at), **`event_bookings.status`**, **`event_bookings.checked_in_at`**; offline fields `offline_payment_method`, `offline_payment_reference` when relevant to the question.
- **Ticket:** **`LEFT JOIN event_tickets` ON `event_tickets.id` = `event_bookings.event_ticket_id`**; select **`event_tickets.name`** and **`event_tickets.price`** (e.g. `ticket_name`, `ticket_price`).

Pure aggregates only (e.g. one total `COUNT` with no per-booking rows) may omit detail columns; any per-booking row must satisfy **Always include** above at minimum.

## Revenue totals (must match admin dashboard / classic reports)
**Total revenue** = **online collected** + **offline estimated**.

### Online (actual collected)
- Table: `event_booking_checkouts`
- Filter: `status = 'paid'`
- Sum: `amount_total_cents` (divide by 100 for currency units; column stores cents)
- Includes ticket + add-on totals from checkout; do **not** sum `event_tickets.price` for online revenue
- Gateway/channel: infer from non-null `stripe_checkout_session_id`, `paypal_order_id`, `razorpay_order_id`, `sslcommerz_tran_id`

### Offline (estimated per seat)
- Table: `event_bookings` with all gateway columns NULL (`stripe_checkout_session_id`, `paypal_order_id`, `razorpay_payment_id`, `sslcommerz_val_id`)
- Status in: `confirmed`, `checked_in`, `pending_offline_payment` (unless user asks for a specific status)
- Per-seat value: use ticket **effective** price — `CASE WHEN early_bird_price IS NOT NULL AND (early_bird_ends_at IS NULL OR date(early_bird_ends_at) >= date('now')) THEN early_bird_price ELSE price END` (adapt `date('now')` to dialect)
- JOIN `event_tickets` ON `event_tickets.id` = `event_bookings.event_ticket_id`
- Sum effective unit price per matching booking row; add-ons are **not** in offline estimates

### Combined total
`SUM(paid checkout cents)/100 + SUM(offline effective unit prices)` — never use raw `event_tickets.price` alone for “total revenue”.

### Date filters for revenue
- **Payment / booking activity:** `event_booking_checkouts.paid_at` for online; `event_bookings.created_at` for offline-only questions
- **Event schedule window (classic report):** filter via `events.starts_at` / `events.ends_at` overlap with the requested range

### “Plugin report” / gateway breakdown
Group paid checkouts or bookings by inferred channel (gateway columns + `offline_payment_method`).

### Check-in list
List attendees with non-null `checked_in_at`; include event title from `events` and booking fields.

### Booking activity timelines (non-revenue)
Prefer `event_bookings.created_at` unless the question clearly refers to event schedule dates.

## Checkout rows (`event_booking_checkouts`)
Use for online revenue, paid order counts, and payment dates. Bookings mirror final attendee rows; for **revenue totals** always include paid checkouts plus offline booking estimates as above.

MARKDOWN;
    }

    public static function systemPromptRules(): string
    {
        return <<<'RULES'
Respond with a SINGLE JSON object (no prose outside JSON) with keys:
{
  "sql": "...",        // compulsory: one SELECT/WITH ... SELECT ending without semicolon; read-only analytics
  "summary": "..."     // brief plain-text description of what the query returns (for UI)
}

Rules for `sql`:
- SELECT or WITH (CTE whose body is SELECT only). No DDL/DML (no INSERT/UPDATE/DELETE/ALTER/DROP/TRUNCATE/REPLACE/ATTACH/VACUUM/etc.).
- No semicolons, no UNION ALL with multiple independent queries that mix types awkwardly prefer single SELECT unless union is logically required.
- No comments (`--`, `/*`), no placeholders for parameters beyond literals.
- Prefer LIMIT 250 unless aggregates return few rows anyway.
- Listing bookings (one row per `event_bookings` row): JOIN `events`; include **event + booking details** — at minimum `events.title`, `event_bookings.attendee_name`, `event_bookings.email`, and by default add schedule (`starts_at`/`ends_at`), venue fields, **`organizers.name`** via **LEFT JOIN** `organizers`, **`event_bookings.created_at`**, **`status`**, **`phone`**, **`checked_in_at`**, and **`LEFT JOIN event_tickets`** for ticket name/price unless the user only wants counts or explicitly limits columns. Skip detail rules only for aggregate-only answers.
RULES;
    }
}
