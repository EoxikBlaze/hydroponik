<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'esp32' => [
        'key' => env('ESP32_API_KEY', 'HARVEST123'),
    ],

    /*
    |--------------------------------------------------------------
    | Notifikasi WhatsApp (Fonnte — https://fonnte.com)
    |--------------------------------------------------------------
    | API key & nomor tujuan DIATUR LEWAT WEBSITE (menu "Notifikasi
    | WhatsApp") dan disimpan di tabel wa_settings — TIDAK di .env —
    | supaya bisa diganti tanpa akses ke server.
    |
    | Endpoint hanya untuk pengujian / penggantian server.
    |
    | Whitelist tipe yang BOLEH dikirim ke WhatsApp.
    | Tipe di luar daftar ini TETAP tercatat ke AlertLog (riwayat web utuh),
    | hanya tidak dikirim ke WA supaya tidak membanjiri notifikasi.
    | - solenoid    : ON / OFF
    | - water_level : Penuh / Kurang  (nilai "Sedang" difilter di service)
    | - jadwal      : pengingat H-1 pemindahan meja (dibuat server, bukan ESP32)
    */
    'fonnte' => [
        'endpoint' => env('FONNTE_ENDPOINT', 'https://api.fonnte.com/send'),
        'types' => ['solenoid', 'water_level', 'jadwal', 'pindah_tanam', 'ringkasan'],
    ],

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
