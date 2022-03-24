<?php

namespace GemsTest\Rest\Test;

use Laminas\Db\Adapter\Adapter;
use Laminas\Db\TableGateway\TableGateway;

trait DatabaseAssertions
{
    /**
     * @var Adapter
     */
    protected $db;

    protected function assertTableRowCount(int $expected, string $table, string $message = '')
    {
        $table = new TableGateway($table, $this->db);
        $results = $table->select();
        return $this->assertEquals($expected, $results->count(), $message);
    }
}
