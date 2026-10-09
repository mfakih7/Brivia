<?php

return [
    'security' => [
        // Enable only once HTTPS works for the host and all subdomains.
        'hsts' => env('BRIVIA_HSTS', false),
        'trusted_proxies' => array_values(array_filter(array_map('trim', explode(',', (string) env('TRUSTED_PROXIES', ''))))),
    ],

    'notifications' => [
        // Comma-separated staff inboxes for new enquiry/appointment alerts. Never stored in public settings.
        'staff_recipients' => array_values(array_filter(array_map('trim', explode(',', (string) env('BRIVIA_STAFF_NOTIFICATION_EMAILS', ''))))),
        'max_attempts' => 3,
    ],

    'appointments' => [
        'max_days_ahead' => 90,
        'staff_timezone' => env('BRIVIA_STAFF_TIMEZONE', 'Asia/Beirut'),
        'reminder_hours_before' => 24,
    ],

    'media' => [
        'disk' => 'public',
        'max_kilobytes' => 5120,
        'max_dimension' => 6000,
        'max_pixels' => 25_000_000,
        'max_gallery_images' => 12,
        'variant_widths' => [480, 960, 1600],
    ],

    'scheduler' => [
        // Shared hosting (no daemons): run a bounded queue worker from the 5-minute scheduler.
        // Keep false locally, where `php artisan queue:work` runs in a terminal.
        'queue_worker' => (bool) env('BRIVIA_SCHEDULER_QUEUE_WORKER', false),
        // Must stay below the 5-minute cron interval.
        'queue_worker_max_seconds' => (int) env('BRIVIA_QUEUE_WORKER_MAX_SECONDS', 240),
    ],

    'forms' => [
        // Submissions faster than this after the form was shown are treated as automated.
        'min_seconds' => (int) env('BRIVIA_FORM_MIN_SECONDS', 3),
    ],

    'testing' => [
        // Only honoured when APP_ENV=testing: widens the race window in the MySQL concurrency test.
        'lock_delay_ms' => (int) env('BRIVIA_TEST_LOCK_DELAY_MS', 0),
    ],

    'currencies' => ['USD', 'EUR', 'GBP', 'LBP', 'AED', 'SAR'],
];
