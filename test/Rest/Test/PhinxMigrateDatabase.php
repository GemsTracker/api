<?php

namespace GemsTest\Rest\Test;

use Phinx\Config\Config;
use Phinx\Console\PhinxApplication;
use Phinx\Migration\Manager;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\NullOutput;

trait PhinxMigrateDatabase
{
    public static $migrated = false;

    public function migrateDatabase()
    {
        if (static::$migrated === false || DB_CONNECTION === 'Pdo_Sqlite') {
            $configArray = require('phinx.php');
            if (DB_CONNECTION === 'Pdo_Sqlite') {
                $configArray['environments']['testing']['adapter'] = 'sqlite';
                $configArray['environments']['testing']['connection'] = $this->pdo;
            }

            $config = new Config($configArray, './');

            $manager = new Manager($config, new StringInput(' '), new NullOutput());
            $manager->migrate('testing');


            static::$migrated = true;
        }
    }


}
