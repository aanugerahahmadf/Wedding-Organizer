<?php

return [

    /*
    |--------------------------------------------------------------------------
    | App name (mobile app bar / branding)
    |--------------------------------------------------------------------------
    | Dikelola dari backend Filament; mobile app wajib ambil dari API /api/settings.
    */
    'app_name' => env('WEDDING_APP_NAME', 'Devi Make Up - Rias Pengantin'),

    /*
    |--------------------------------------------------------------------------
    | Owner / brand name
    |--------------------------------------------------------------------------
    */
    'owner_name' => env('WEDDING_APP_OWNER_NAME', 'Devi Make Up - Rias Pengantin'),

    /*
    |--------------------------------------------------------------------------
    | Demo video URL (video demo aplikasi)
    |--------------------------------------------------------------------------
    | URL video yang ditampilkan di halaman "Video Demo Aplikasi". Wajib dari backend.
    */
    'demo_video_url' => env('WEDDING_APP_DEMO_VIDEO_URL', ''),
];
