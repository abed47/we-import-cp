<?php
return [
    'settings' => [
        'displayErrorDetails' => false, // set to false in production
        'addContentLengthHeader' => false, // Allow the web server to send the content-length header
        'display_error' => 0,

        // Renderer settings
        'renderer' => [
            'template_path' => __DIR__ . '/../templates/',
        ],

        // Monolog settings
        'logger' => [
            'name' => 'slim-app',
            'path' => isset($_ENV['docker']) ? 'php://stdout' : __DIR__ . '/../logs/app.log',
            'level' => \Monolog\Logger::DEBUG,
        ],

        'private_env' => [
            'db_name' => 'we_import_cp',
            'db_host' => '127.0.0.1',
            'db_user' => 'root',
            'db_pass' => 'root'
        ]
    ],
];
