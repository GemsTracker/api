<?php

declare(strict_types=1);


namespace GemsTest\Rest\Test;


use Laminas\Db\Adapter\Adapter;

trait DatabaseTransactions
{
    /**
     * @var Adapter
     */
    protected $db;

    protected function beginDatabaseTransaction()
    {
        $this->db->getDriver()->getConnection()->beginTransaction();
    }

    protected function rollbackDatabaseTransaction()
    {
        $this->db->getDriver()->getConnection()->rollback();
    }
}
