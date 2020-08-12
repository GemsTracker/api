<?php

namespace GemsTest\Rest\Test;

use PHPUnit\DbUnit\TestCase;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Adapter\Driver\Pdo\Pdo;

abstract class ZendDbTestCase extends TestCase
{
    /**
     * Zend Framework 1 Db Adapter
     *
     * @var \Zend_Db_Adapter_Abstract
     */
    protected $db1;

    /**
     * Zend 2 Db Adapter
     *
     * @var \Zend\Db\Adapter\Adapter
     */
    protected $db;

    /**
     * @var bool should Zend 1 adapter be loaded?
     */
    protected $loadZendDb1 = false;

    /**
     * @var bool should Zend 2 adapter be loaded?
     */
    protected $loadZendDb2 = false;

    private $pdo = null;

    private $connection = null;

    protected function setUp()
    {
        parent::setUp();
    }

    /**
     * Returns the test database connection.
     *
     * @return Connection
     */
    protected function getConnection()
    {
        if ($this->connection === null) {
            if ($this->pdo === null) {
                $this->pdo = $this->initPdo();
            }

            $this->initSql($this->pdo);

            $this->connection = $this->createDefaultDBConnection($this->pdo);
        }
        return $this->connection;
    }

    protected function getInitSql()
    {
        $testDir = dirname(dirname(dirname(__FILE__)));
        $dataDir = $testDir . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'sqlite' . DIRECTORY_SEPARATOR;
        $file = $dataDir . 'init.sql';

        return [$file];
    }

    protected function initSql(\PDO $pdo)
    {
        if ($sqlFiles = $this->getInitSql()) {
            foreach ($sqlFiles as $file) {
                $sql  = file_get_contents($file);
                $statements = explode(';', $sql);
                foreach($statements as $sql) {
                    if (!empty($sql) && !strpos(strtoupper($sql), 'INSERT INTO') && !strpos(strtoupper($sql), 'INSERT IGNORE')
                        && !strpos(strtoupper($sql), 'UPDATE ')) {
                        $pdo->exec($sql);
                    }
                }
            }
        }
    }

    protected function initPdo()
    {
        if ($this->loadZendDb1) {
            $this->db1 = \Zend_Db::factory(
                'Pdo_sqlite',
                [
                    'dbname' => ':memory:'
                ]
            );
            \Zend_Db_Table::setDefaultAdapter($this->db1);

            $pdo = $this->db1->getConnection();
        } else {
            $pdo = new \PDO('sqlite::memory:');
        }

        if ($this->loadZendDb2) {
            $driver = new Pdo($pdo);
            $this->db = new Adapter($driver);
        }

        return $pdo;
    }

}
