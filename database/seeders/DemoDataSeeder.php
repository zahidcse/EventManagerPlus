<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\EventCategory;
use App\Models\EventTicket;
use App\Models\Organizer;
use App\Models\SiteSetting;
use App\Models\Speaker;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Enable "Pay on Site" (Cash Payment), set Classic theme and Hero image
        $settings = SiteSetting::instance();
        $settings->update([
            'payment_cash_enabled' => true,
            'frontend_theme' => 'classic',
            'frontend_hero_image_path' => 'hero-classic.jpg',
            'logo_path' => 'frontend-logo.png',
            'admin_logo_path' => 'admin-logo.png',
        ]);

        // 2. Create Demo Categories
        $catTech = EventCategory::firstOrCreate(['name' => 'Technology'], ['sort_order' => 10]);
        $catMusic = EventCategory::firstOrCreate(['name' => 'Music'], ['sort_order' => 11]);
        $catSports = EventCategory::firstOrCreate(['name' => 'Sports'], ['sort_order' => 12]);

        // 3. Create Demo Organizer
        $organizer = Organizer::firstOrCreate(
            ['email' => 'demo-organizer@example.com'],
            [
                'name' => 'Global Events Co.',
                'company_name' => 'Global Events Worldwide',
                'job_title' => 'Event Director',
                'bio' => 'Professional event organizers with over 15 years of experience in hosting world-class conferences and festivals.',
                'status' => 'active',
                'country' => 'US',
                'city' => 'New York',
                'password' => 'password',
            ]
        );

        // 4. Create Demo Speaker
        $speaker = Speaker::firstOrCreate(
            ['name' => 'Dr. Sarah Mitchell'],
            [
                'headline' => 'AI Research Scientist',
                'bio' => 'A leading expert in artificial intelligence and machine learning with a focus on ethical implementation.',
            ]
        );

        // 5. Create 3 Different Types of Events

        // Event Type 1: Single Day Conference
        $event1 = Event::create([
            'organizer_id' => $organizer->id,
            'event_category_id' => $catTech->id,
            'title' => 'Tech Pulse 2026',
            'slug' => 'tech-pulse-2026',
            'visibility' => 'public',
            'status' => 'active',
            'cover_image_path' => 'demo-event-1.jpg',
            'description' => 'A deep dive into the latest trends in technology, from AI to Quantum Computing.',
            'starts_at' => Carbon::now()->addDays(30)->setTime(9, 0),
            'ends_at' => Carbon::now()->addDays(30)->setTime(17, 0),
            'schedule_type' => 'single',
            'location_type' => 'physical',
            'venue_city' => 'San Francisco',
            'venue_state' => 'CA',
            'capacity' => 500,
        ]);
        $event1->speakers()->attach($speaker->id, ['sort_order' => 1]);
        EventTicket::create([
            'event_id' => $event1->id,
            'name' => 'General Admission',
            'price' => 299.00,
            'quantity' => 450,
            'sort_order' => 1,
        ]);
        EventTicket::create([
            'event_id' => $event1->id,
            'name' => 'VIP Pass',
            'price' => 599.00,
            'quantity' => 50,
            'sort_order' => 2,
        ]);

        // Event Type 2: Custom Schedule (Jazz Night Series)
        $jazzDates = [
            Carbon::now()->addDays(40)->toDateString(),
            Carbon::now()->addDays(41)->toDateString(),
            Carbon::now()->addDays(47)->toDateString(),
            Carbon::now()->addDays(48)->toDateString(),
        ];
        $event2 = Event::create([
            'organizer_id' => $organizer->id,
            'event_category_id' => $catMusic->id,
            'title' => 'Downtown Jazz Night',
            'slug' => 'jazz-night-series',
            'visibility' => 'public',
            'status' => 'active',
            'cover_image_path' => 'demo-event-2.jpg',
            'description' => 'Experience the smoothest rhythms and melodies from world-renowned jazz artists.',
            'starts_at' => Carbon::now()->addDays(40)->setTime(20, 0),
            'ends_at' => Carbon::now()->addDays(48)->setTime(23, 0),
            'schedule_type' => 'custom_interval',
            'custom_schedule_dates' => $jazzDates,
            'location_type' => 'physical',
            'venue_city' => 'New Orleans',
            'venue_street' => 'French Quarter',
            'capacity' => 50,
        ]);
        EventTicket::create([
            'event_id' => $event2->id,
            'name' => 'Standard Ticket',
            'price' => 45.00,
            'quantity' => 50,
            'sort_order' => 1,
        ]);

        // Event Type 3: Recurring Weekly Event
        $event3 = Event::create([
            'organizer_id' => $organizer->id,
            'event_category_id' => $catSports->id,
            'title' => 'Sunday Morning Yoga',
            'slug' => 'sunday-yoga-sessions',
            'visibility' => 'public',
            'status' => 'active',
            'description' => 'Start your week with peaceful mindfulness and revitalizing yoga poses.',
            'starts_at' => Carbon::now()->next(Carbon::SUNDAY)->setTime(8, 0),
            'schedule_type' => 'recurring',
            'recurrence_weekdays' => [0], // Sunday
            'recurrence_ends_on' => Carbon::now()->addMonths(6),
            'location_type' => 'hybrid',
            'venue_city' => 'New York',
            'venue_state' => 'NY',
            'venue_street' => 'Central Park West',
            'meeting_url' => 'https://meet.google.com/demo-yoga',
            'capacity' => 100,
        ]);
        EventTicket::create([
            'event_id' => $event3->id,
            'name' => 'Single Class',
            'price' => 15.00,
            'quantity' => 100,
            'sort_order' => 1,
        ]);
    }
}
