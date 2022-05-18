<?php

namespace GemsTest\Rest\Test;

use PHPUnit\Framework\TestCase;

abstract class DbTestCase extends TestCase
{
    use LaminasDb;
    use PhinxMigrateDatabase;
    use DatabaseTransactions;
    use DatabaseAssertions;

    public function setup()
    {
        $sqliteFunctions = new SqliteFunctions();
        $this->initDb($sqliteFunctions);
        $this->migrateDatabase();
        $this->beginDatabaseTransaction();
    }

    protected function tearDown()
    {
        $this->rollbackDatabaseTransaction();
    }
}
