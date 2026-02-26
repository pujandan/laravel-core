<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Response Format
    |--------------------------------------------------------------------------
    |
    | The default response format for API responses
    |
    */

    'default_response_format' => 'json',

    /*
    |--------------------------------------------------------------------------
    | Enable UUID Primary Key
    |--------------------------------------------------------------------------
    |
    | Enable UUID as default primary key for models using HasUuid trait
    |
    */

    'use_uuid' => env('LARAVEL_CORE_USE_UUID', false),

    /*
    |--------------------------------------------------------------------------
    | Exception Handler
    |--------------------------------------------------------------------------
    |
    | Enable custom exception handler
    |
    */

    'enable_exception_handler' => env('LARAVEL_CORE_EXCEPTION_HANDLER', true),

];
