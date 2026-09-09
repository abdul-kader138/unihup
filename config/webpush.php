<?php

return [

    /*
    |--------------------------------------------------------------------------
    | VAPID keys
    |--------------------------------------------------------------------------
    |
    | Voluntary Application Server Identification for web push. Generate a
    | keypair once with:
    |
    |   php artisan tinker --execute="print_r(Minishlink\WebPush\VAPID::createVapidKeys());"
    |
    | The public key is also handed to the browser so it can create a push
    | subscription bound to this application server. When the keys are blank,
    | push is simply disabled (subscriptions still store, nothing is sent).
    |
    */

    'subject' => env('WEBPUSH_VAPID_SUBJECT', 'mailto:support@unihup.com'),

    'public_key' => env('WEBPUSH_VAPID_PUBLIC_KEY'),

    'private_key' => env('WEBPUSH_VAPID_PRIVATE_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Default notification icon / badge
    |--------------------------------------------------------------------------
    |
    | Paths under the public root, used by the service worker when a payload
    | does not carry its own.
    |
    */

    'icon' => '/icons/unihup-icon.svg',

    'badge' => '/icons/unihup-icon.svg',

];
