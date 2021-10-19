<?php
return [
    'db' => [
        'prefix' => 'tp_',
        'url' => getenv('DATABASE_URL') ?? '',
        'dsn' => getenv('DATABASE_DSN') ?? '',
        'type' => $_ENV['DATABASE_TYPE'] ?? 'sqlite',
        'database' => $_ENV['DATABASE_NAME'] ?? 'sns.sqlite3',
        'host' => $_ENV['DATABASE_HOST'] ?? '',
        'username' => $_ENV['DATABASE_USERNAME'] ?? '',
        'password' => $_ENV['DATABASE_PASSWORD'] ?? '',
    ],
    'cache' => isset($_ENV['BUNNY_CACHE']) ? json_decode($_ENV['BUNNY_CACHE'], true) : [],
    'storage' => isset($_ENV['BUNNY_STORAGE']) ? json_decode($_ENV['BUNNY_STORAGE'], true) : [],
    'site_name' => $_ENV['BUNNY_SITE_NAME'] ?? 'TwimiSNS',
    'site_url' => $_ENV['BUNNY_SITE_URL'] ?? 'localhost',
    'controller' => 'Index',
    'action' => 'index',
    'allow_reg' => $_ENV['BUNNY_ALLOW_REG'] ?? '1',
];