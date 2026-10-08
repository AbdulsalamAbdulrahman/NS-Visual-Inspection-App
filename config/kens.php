<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | New Service Department contact
    |--------------------------------------------------------------------------
    |
    | Shown on the forgot-password and suspended-account screens.
    |
    */

    'nsd' => [
        'phone' => env('NSD_PHONE', '+234 800 000 0000'),
        'email' => env('NSD_EMAIL', 'nsd@kadunaelectric.com'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Public verification
    |--------------------------------------------------------------------------
    |
    | Base URL encoded in the ticket QR code. Defaults to the production
    | domain so printed reports always point at the live verify page.
    |
    */

    'verify_base_url' => env('KENS_VERIFY_BASE_URL', 'https://kens.buildingelectcert.com.ng'),

    /*
    |--------------------------------------------------------------------------
    | Service area editing
    |--------------------------------------------------------------------------
    |
    | Off for now: the area offices come from ServiceAreaSeeder (Kaduna
    | Electric's official list) and admins can only view them. Set
    | KENS_AREAS_EDITABLE=true to bring back add, rename and deactivate.
    |
    */

    'areas_editable' => (bool) env('KENS_AREAS_EDITABLE', false),

];
