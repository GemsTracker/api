<?php

namespace GemsTest\Rest\Test;

use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Adapter\Driver\Pdo\Pdo;

trait LaminasDb
{
    /**
     * @var Adapter
     */
    protected $db;

    /**
     * @var \PDO
     */
    protected $pdo;

    public function initDb(SqliteFunctions $sqliteFunctions)
    {
        if (!defined('DB_CONNECTION')) {
            define('DB_CONNECTION', 'Pdo_Sqlite');
            define('DB_DATABASE', ':memory:');
        }

        $dsn = $this->getDsn();

        $this->pdo = new \PDO($dsn);


        if ($this->getDsnDriverName() === 'sqlite') {
            $sqliteFunctions->addSqlFunctonsToPdoAdapter($this->pdo);
        }

        $this->db = new Adapter(new Pdo($this->pdo));
    }

    protected function getDsn()
    {
        if (defined('DB_DSN')) {
            return DB_DSN;
        } else {
            $driverName = $this->getDsnDriverName();
            $dsn = $driverName;
            if ($driverName !== 'sqlite') {
                $dsn .= sprintf('host=%s;dbname=%s;user=%s;password=%s;', DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD);
                if (defined('DB_CHARSET')) {
                    $dsn .= sprintf('charset=%s', DB_CHARSET);
                }
            } else {
                $dsn .= sprintf(':%s', DB_DATABASE);
            }
            return $dsn;
        }
        return null;
    }

    protected function getDsnDriverName()
    {
        switch (DB_CONNECTION) {
            case 'Pdo_Mysql':
            case 'Mysqli':
            case 'mysql':
                return 'mysql';
            case 'Pdo_Sqlite':
            case 'sqlite':
                return 'sqlite';
            case 'Pdo_Pgsql':
            case 'Pgsql':
            case 'pgsql':
                return 'pgsql';
            case 'Sqlsrv':
            case 'mssql':
                return 'mssql';
            default:
                break;
        }
        return null;
    }

    public function sqliteNow()
    {
        $now = new \DateTimeImmutable();
        $format = 'Y-m-d';
        return $now->format($format);
    }
}
