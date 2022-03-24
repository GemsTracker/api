<?php

namespace GemsTest\Rest\Test;

use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Adapter\Driver\Pdo\Pdo;

trait LegacyDb
{
    /**
     * @var \Zend_Db_Adapter_Abstract
     */
    protected $legacyDb;

    public function initLegacyDb(SqliteFunctions $sqliteFunctions)
    {
        if (!defined('DB_CONNECTION') || DB_CONNECTION === 'pdo_Sqlite') {

            if (!defined('DB_DATABASE')) {
                define('DB_DATABASE', ':memory:');
            }

            $this->legacyDb = \Zend_Db::factory(
                'Pdo_sqlite',
                [
                    'dbname' => DB_DATABASE
                ]
            );

            $pdo = $this->legacyDb->getConnection();
            $sqliteFunctions->addSqlFunctonsToPdoAdapter($pdo);

        } else {
            $this->legacyDb = \Zend_Db::factory('Pdo_Mysql',
                [
                    'dbname' => DB_DATABASE,
                    'host' => DB_HOST,
                    'username' => DB_USERNAME,
                    'password' => DB_PASSWORD,
                ]
            );
        }



        \Zend_Db_Table::setDefaultAdapter($this->legacyDb);
    }
}
