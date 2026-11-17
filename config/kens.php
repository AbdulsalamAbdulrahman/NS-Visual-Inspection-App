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
    | Platform operator
    |--------------------------------------------------------------------------
    |
    | The company that runs KENS for Kaduna Electric and collects the fees
    | (named in the Monnify board resolution). Shown on the public pages.
    |
    */

    'operator' => [
        'name' => env('KENS_OPERATOR_NAME', 'Jannats Engineering Services Ltd'),
        'rc' => env('KENS_OPERATOR_RC', '7180154'),
        'phones' => array_values(array_filter(explode(',', (string) env('KENS_OPERATOR_PHONES', '+234 703 499 5242,+234 806 885 9568')))),
        'email' => env('KENS_OPERATOR_EMAIL', 'jannatsbusiness42@gmail.com'),
        'address' => env('KENS_OPERATOR_ADDRESS', '11 Rahama Road, Kakuri, Kaduna, Kaduna State, Nigeria'),
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
