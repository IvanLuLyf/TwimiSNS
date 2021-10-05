<?php
return [
    'db' => [
        'prefix' => 'tp_',
        'url' => $_ENV['DATABASE_URL'] ?? '',
        'dsn' => $_ENV['DATABASE_DSN'] ?? '',
        'type' => $_ENV['DATABASE_TYPE'] ?? 'sqlite',
        'database' => $_ENV['DATABASE_NAME'] ?? 'sns.sqlite3',
        'host' => $_ENV['DATABASE_HOST'] ?? '',
        'username' => $_ENV['DATABASE_USERNAME'] ?? '',
        'password' => $_ENV['DATABASE_PASSWORD'] ?? '',
    ],
    'site_name' => $_ENV['BUNNY_SITE_NAME'] ?? 'TwimiSNS',
    'site_url' => $_ENV['BUNNY_SITE_URL'] ?? 'localhost',
    'controller' => 'Index',
    'action' => 'index',
    'allow_reg' => $_ENV['BUNNY_ALLOW_REG'] ?? '1',
];