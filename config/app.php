<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    |
    | This value is the name of your application, which will be used when the
    | framework needs to place the application's name in a notification or
    | other UI elements where an application name needs to be displayed.
    |
    */

    'name' => env('APP_NAME', 'Laravel'),

    /*
    |--------------------------------------------------------------------------
    | Application Environment
    |--------------------------------------------------------------------------
    |
    | This value determines the "environment" your application is currently
    | running in. This may determine how you prefer to configure various
    | services the application utilizes. Set this in your ".env" file.
    |
    */

    'env' => env('APP_ENV', 'production'),

    /*
    |--------------------------------------------------------------------------
    | Application Debug Mode
    |--------------------------------------------------------------------------
    |
    | When your application is in debug mode, detailed error messages with
    | stack traces will be shown on every error that occurs within your
    | application. If disabled, a simple generic error page is shown.
    |
    */

    'debug' => (bool) env('APP_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Application URL
    |--------------------------------------------------------------------------
    |
    | This URL is used by the console to properly generate URLs when using
    | the Artisan command line tool. You should set this to the root of
    | the application so that it's available within Artisan commands.
    |
    */

    'url' => env('APP_URL', 'http://localhost'),

    /*
    |--------------------------------------------------------------------------
    | Application Timezone
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default timezone for your application, which
    | will be used by the PHP date and date-time functions. The timezone
    | is set to "UTC" by default as it is suitable for most use cases.
    |
    */

    'timezone' => 'UTC',

    /*
    |--------------------------------------------------------------------------
    | Business Timezone
    |--------------------------------------------------------------------------
    |
    | The office's local timezone. Used for "today" in payments and dues
    | (payment dates, today's collection, which months have fallen due), so
    | early-morning IST is not treated as the previous UTC day.
    |
    */

    'business_timezone' => env('BUSINESS_TIMEZONE', 'Asia/Kolkata'),

    /*
    |--------------------------------------------------------------------------
    | Office Phone
    |--------------------------------------------------------------------------
    |
    | The number customers call from their own pages (/my) — "Call office".
    |
    */

    'office_phone' => env('OFFICE_PHONE', '9842510159'),

    /*
    | Sri Lakshmi Micro Finance can also open on its own domain (e.g.
    | srilakshmifinance.com): its staff land on the Micro Finance dashboard
    | and customers see its name on the login page.
    */

    'finance_domain' => env('FINANCE_DOMAIN', ''),

    /*
    | Which businesses customers see on their own pages (comma separated:
    | chit, traders, finance). SN Chit Funds and SN Traders are paused for
    | now, so customers see only Sri Lakshmi Micro Finance.
    */

    'portal_sections' => array_values(array_filter(array_map('trim', explode(',', (string) env('PORTAL_SECTIONS', 'finance'))))),

    /*
    | Sri Lakshmi Micro Finance details printed on the Key Fact Statement.
    | Fill these with the business's real registration and grievance
    | officer; empty ones are left out (the office phone is used for
    | complaints until a grievance officer is set).
    */

    'finance_kfs' => [
        'legal_name' => env('FINANCE_LEGAL_NAME', 'Sri Lakshmi Micro Finance'),
        'registration' => env('FINANCE_REGISTRATION', ''),
        'branch' => env('FINANCE_BRANCH', 'Coimbatore'),
        'address' => env('FINANCE_ADDRESS', ''),
        'grievance_officer' => env('FINANCE_GRIEVANCE_OFFICER', ''),
        'grievance_phone' => env('FINANCE_GRIEVANCE_PHONE', ''),
        'grievance_email' => env('FINANCE_GRIEVANCE_EMAIL', ''),
        'credit_bureaus' => env('FINANCE_CREDIT_BUREAUS', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Application Locale Configuration
    |--------------------------------------------------------------------------
    |
    | The application locale determines the default locale that will be used
    | by Laravel's translation / localization methods. This option can be
    | set to any locale for which you plan to have translation strings.
    |
    */

    'locale' => env('APP_LOCALE', 'en'),

    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),

    'faker_locale' => env('APP_FAKER_LOCALE', 'en_US'),

    /*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    |
    | This key is utilized by Laravel's encryption services and should be set
    | to a random, 32 character string to ensure that all encrypted values
    | are secure. You should do this prior to deploying the application.
    |
    */

    'cipher' => 'AES-256-CBC',

    'key' => env('APP_KEY'),

    'previous_keys' => [
        ...array_filter(
            explode(',', (string) env('APP_PREVIOUS_KEYS', ''))
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | Maintenance Mode Driver
    |--------------------------------------------------------------------------
    |
    | These configuration options determine the driver used to determine and
    | manage Laravel's "maintenance mode" status. The "cache" driver will
    | allow maintenance mode to be controlled across multiple machines.
    |
    | Supported drivers: "file", "cache", "array"
    |
    */

    'maintenance' => [
        'driver' => env('APP_MAINTENANCE_DRIVER', 'file'),
        'store' => env('APP_MAINTENANCE_STORE', 'database'),
    ],

];
