<?php

$config = require 'config/autoload/database.local.php';

return
[
    'paths' => [
        'migrations' => [
            '%%PHINX_CONFIG_DIR%%/config/db/migrations',
            '%%PHINX_CONFIG_DIR%%/src/Prediction/config/db/migrations',
        ],
        'seeds' => '%%PHINX_CONFIG_DIR%%/config/db/seeds'
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
            'pass' => '',
            'port' => '3306',
            'charset' => 'utf8',
        ]
    ],
    'version_order' => 'creation'
];