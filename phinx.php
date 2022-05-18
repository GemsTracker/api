<?php

if (!defined('GEMS_LOG_DIR')) {
    define('GEMS_LOG_DIR', 'data/logs');
}
require 'vendor/autoload.php';
$config = require 'config/config.php';

return
[
    'paths' => [
        'migrations' => $config['migrations']['migrations'],
        'seeds' => $config['migrations']['seeds'],
    ],
    'environments' => [
        'default_migration_table' => 'gems__migration_log',
        'default_database' => 'production',
        'production' => [
            'adapter' => 'mysql',
            'host' => $config['db']['host'],
            'name' => $config['db']['database'],
            'user' => $config['db']['username'],
            'pass' => $config['db']['password'],
            'port' => '3306',
            'charset' => $config['db']['charset'],
        ],
        'testing' => [
            'adapter' => 'mysql',
            'host' => 'localhost',
            'name' => 'testing_db',
            'user' => 'root',
            'pass' => 'test123',
            'port' => '3306',
            'charset' => 'utf8',
        ]
    ],
    'version_order' => 'creation'
];
