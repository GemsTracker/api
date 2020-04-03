<?php

// Delegate static file requests back to the PHP built-in webserver
if (PHP_SAPI === 'cli-server' && $_SERVER['SCRIPT_FILENAME'] !== __FILE__) {
    return false;
}

error_reporting(E_ALL);
ini_set('display_errors', 'On');

define('GEMS_WEB_DIR', __DIR__);
define('GEMS_ROOT_DIR', realpath(GEMS_WEB_DIR . '/../'));
define('GEMS_LOG_DIR', GEMS_ROOT_DIR . '/data/logs');
ini_set('error_log', GEMS_LOG_DIR . '/php_errors.log');

chdir(dirname(__DIR__));
require 'vendor/autoload.php';

/**
 * Self-called anonymous function that creates its own scope and keep the global namespace clean.
 */

call_user_func(function () {
    /** @var \Psr\Container\ContainerInterface $container */
    $container = require 'config/container.php';

    /** @var \Mezzio\Application $app */
    $app = $container->get(\Mezzio\Application::class);

    // Import programmatic/declarative middleware pipeline and routing
    // configuration statements
    require 'config/pipeline.php';
    //require 'config/routes.php';

    $app->run();
});
