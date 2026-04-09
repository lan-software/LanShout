<?php

return [
    /*
    |--------------------------------------------------------------------------
    | LanShout-specific LanCore Config
    |--------------------------------------------------------------------------
    |
    | Keys shared across all satellite apps are provided by the
    | lan-software/lancore-client package. This file only extends the
    | package config with LanShout-specific values.
    |
    */

    'app_slug' => env('LANCORE_APP_SLUG', 'lanshout'),

    'announcements_feed_url' => env('LANCORE_ANNOUNCEMENTS_FEED_URL', rtrim((string) env('LANCORE_BASE_URL', 'http://lancore.lan'), '/').'/api/announcements/feed'),
];
