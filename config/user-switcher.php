<?php

return [
    'user_model' => 'App\\Models\\User',

    'enabled' => env('USER_SWITCHER_ENABLED', false),

    'environments' => env('USER_SWITCHER_ENVIRONMENTS', 'local,testing'),

    'auto_inject' => env('USER_SWITCHER_AUTO_INJECT', true),

    'authorization_callback' => null,
];
