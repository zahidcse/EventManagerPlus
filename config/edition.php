<?php

declare(strict_types=1);

return [

    'premium_url' => env('EDITION_PREMIUM_URL', 'https://lucrativeit.com/products/event-manager-plus'),

    'premium_message' => 'Available in premium version',

    'themes' => ['default', 'classic'],

    'payment_gateways' => ['stripe'],

    'schedule_types' => ['single'],

    'additional_services' => false,

    'early_bird_pricing' => false,

];
