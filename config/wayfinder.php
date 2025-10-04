<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Wayfinder Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration options for the Wayfinder package.
    | You can customize these settings to match your application's needs.
    |
    */

    'output' => resource_path('js/wayfinder'),
    
    'form_variants' => true,
    
    'routes' => [
        'web' => true,
        'api' => false,
    ],
    
    'exclude' => [
        // Add route patterns to exclude from generation
    ],
];
