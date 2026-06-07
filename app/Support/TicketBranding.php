<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Event;
use App\Models\SiteSetting;
use Illuminate\Support\Facades\Schema;

final class TicketBranding
{
    /**
     * @return array{logoUrl: ?string, logoDataUri: ?string, organizerName: string, companyName: string}
     */
    public static function forEvent(Event $event): array
    {
        $event->loadMissing('organizer');

        $logoUrl = null;
        $logoDataUri = null;

        if (filled($event->ticket_logo_path)) {
            $logoUrl = $event->ticketLogoPublicUrl();
            $logoDataUri = self::imageDataUri(public_path('uploads/'.$event->ticket_logo_path));
        }

        if ($logoUrl === null && Schema::hasTable('site_settings')) {
            $setting = SiteSetting::query()->first();
            if ($setting !== null && filled($setting->logo_path)) {
                $logoUrl = $setting->logoPublicUrl();
                $logoDataUri = self::imageDataUri(public_path('uploads/'.$setting->logo_path));
            }
        }

        if ($logoUrl === null && $event->organizer !== null && filled($event->organizer->photo_path)) {
            $logoUrl = asset('uploads/'.$event->organizer->photo_path);
            $logoDataUri = self::imageDataUri(public_path('uploads/'.$event->organizer->photo_path));
        }

        $organizerName = trim((string) ($event->organizer?->name ?? ''));
        $companyName = trim((string) ($event->organizer?->company_name ?? ''));

        return [
            'logoUrl' => $logoUrl,
            'logoDataUri' => $logoDataUri,
            'organizerName' => $organizerName,
            'companyName' => $companyName,
        ];
    }

    private static function imageDataUri(string $path): ?string
    {
        if (! is_file($path)) {
            return null;
        }

        $mime = mime_content_type($path) ?: 'image/png';

        return 'data:'.$mime.';base64,'.base64_encode((string) file_get_contents($path));
    }
}
