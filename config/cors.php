<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Cross-Origin Resource Sharing (CORS)
|--------------------------------------------------------------------------
| Solo afecta a peticiones hechas desde un navegador en un origen distinto
| (apps JavaScript con fetch/axios). Las llamadas servidor-a-servidor o con
| Postman/curl ignoran CORS, así que esta configuración no las altera.
|
| Se aplica a la API interna (core/v1).
*/

return [

    'paths' => [
        'core/v1/*',
    ],

    'allowed_methods' => ['*'],

    // La autenticación es por token Bearer (no por cookies de sesión), por lo
    // que permitir cualquier origen es seguro: el token sigue siendo necesario.
    'allowed_origins' => ['*'],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [
        'X-Correlation-Id',
    ],

    'max_age' => 0,

    // false porque no se usan cookies/sesión cross-origin; el token va en el
    // header Authorization. (Con credentials en true no se permite origen '*'.)
    'supports_credentials' => false,

];
