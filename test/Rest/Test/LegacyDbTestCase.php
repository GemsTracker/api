<?php

declare(strict_types=1);


namespace GemsTest\Rest\Test;


class LegacyDbTestCase extends DbTestCase
{
    use LegacyDb;

    public function setup()
    {
        parent::setup();
        $sqliteFunctions = new SqliteFunctions();
        $this->initLegacyDb($sqliteFunctions);
    }
}
