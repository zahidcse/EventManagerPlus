<?php

use App\Support\Installer\InstallationStatus;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('install:lock', function (): void {
    InstallationStatus::markCompleted('Created via install:lock Artisan command (existing deployment).');
    $this->components->info('Wrote '.InstallationStatus::lockFilePath().' — the web installer will stay disabled.');
})->purpose('Create install.lock for an already-configured site (skips the /install wizard).');

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
