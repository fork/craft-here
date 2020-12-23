<?php

return [
    // Global settings
    '*' => [
        'serverType' => 'nginx' // or 'apache'
    ],

    // Dev environment settings
    'dev' => [
        //'redirectsReloadCommand' => 'my-command',
    ],

    // Staging environment settings
    'staging' => [
    ],

    // Production environment settings
    'production' => [
        //'redirectsReloadCommand' => 'sudo /etc/init.d/nginx reload',
    ],
];
