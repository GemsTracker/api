<?php

chdir(__DIR__ . '/../');

define('GEMS_ROOT_DIR', dirname(__DIR__));
defined('VENDOR_DIR') || define('VENDOR_DIR', GEMS_ROOT_DIR . '/vendor/');
defined('GEMS_LIBRARY_DIR') || define('GEMS_LIBRARY_DIR', VENDOR_DIR . '/gemstracker/gemstracker');


require(VENDOR_DIR . 'autoload.php');

$config = include 'config/config.php';

$keyGenerator = new Gems\Rest\Auth\KeyGenerator($config);
$keyGenerator->generateKeys();


exit(0);
