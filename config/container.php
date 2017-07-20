<?php

use Zend\ServiceManager\Config;
//use Zend\ServiceManager\ServiceManager;
use Zalt\Loader\ProjectOverloader;

// Load configuration
$config = require __DIR__ . '/config.php';

/**
 * Define application environment
 */


if (! defined('APPLICATION_ENV')) {

    if (isset($config['project']) && isset($config['project']['environment'])) {
        $env = $config['project']['environment'];
    } else {
        $env = getenv('APPLICATION_ENV');
    }

    if (! $env) {
        $env = 'production';
    }

    define('APPLICATION_ENV', $env);
}

// Build container
$loader = new ProjectOverloader([
	$config['project']['name'],
    'Gems',
    'MUtil',
]);

$container = $loader->createServiceManager();
(new Config($config['dependencies']))->configureServiceManager($container);

// Inject config
$container->setService('config', $config);

return $container;
