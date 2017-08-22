<?php

namespace Gems\Rest\Legacy;

use Exception;
use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;

use Zend_Db;
use Zend_Db_Table;
use Zend_Registry;

class LegacyZendDatabaseFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $config = $container->get('config');

        if (!isset($config['db'])) {
            throw new Exception('No database configuration found');
        }
        $databaseConfig = $config['db'];

        /**
         * Zend\Db (2.x) uses other configuration names vs Zend_Db:
         * adapter => driver
         * dbname  => database
         */
        if (!isset($databaseConfig['adapter']) && isset($databaseConfig['driver'])) {
            $databaseConfig['adapter'] = $databaseConfig['driver'];
        }

        if (!isset($databaseConfig['adapter'])) {
            throw new Exception('No database adapter set in config');
        }

        if (!isset($databaseConfig['dbname']) && isset($databaseConfig['database'])) {
            $databaseConfig['dbname'] = $databaseConfig['database'];
        }

        if (!isset($databaseConfig['dbname'])) {
            throw new Exception('No database set in config');
        }

        $db = Zend_Db::factory($databaseConfig['adapter'], $databaseConfig);

        Zend_Db_Table::setDefaultAdapter($db);
        Zend_Registry::set('db', $db);

        return $db;
    }
}