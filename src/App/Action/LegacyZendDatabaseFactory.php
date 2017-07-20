<?php

namespace App\Action;

use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;

class LegacyZendDatabaseFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $config = $container->get('config');
        $db = new \Zend_Db_Adapter_Pdo_Mysql([
            'host'     => $config['database']['host'],
            'username' => $config['database']['username'],
            'password' => $config['database']['password'],
            'dbname'   => $config['database']['database'],
        ]);

        \Zend_Db_Table::setDefaultAdapter($db);
        \Zend_Registry::set('db', $db);

        return $db;
    }
}