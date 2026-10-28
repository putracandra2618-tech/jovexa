<?php

use Illuminate\Http\Request;

return [

    /*
    |--------------------------------------------------------------------------
    | Trusted Proxies
    |--------------------------------------------------------------------------
    |
    | When the application is served behind a TLS-terminating proxy or tunnel
    | (e.g. ngrok during development, or a load balancer in production), the
    | incoming request scheme must come from the forwarded headers so that
    | generated URLs stay HTTPS. Set TRUSTED_PROXIES to the proxy IP(s), or to
    | "*" only when the proxy fully controls the network boundary. Leave it
    | empty when there is no proxy so forwarded headers are never trusted.
    |
    */

    'proxies' => env('TRUSTED_PROXIES'),

    'headers' => Request::HEADER_X_FORWARDED_FOR
        | Request::HEADER_X_FORWARDED_HOST
        | Request::HEADER_X_FORWARDED_PROTO,

];
