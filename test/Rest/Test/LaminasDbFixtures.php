<?php

namespace GemsTest\Rest\Test;

use Laminas\Db\Adapter\Adapter;
use Laminas\Db\TableGateway\TableGateway;
use Symfony\Component\Yaml\Yaml;

trait LaminasDbFixtures
{
    use DbHelpers;

    /**
     * @var Adapter
     */
    protected $db;

    protected function insertFixtures($fixtures, $default=[], $addCreated=false)
    {
        foreach ($fixtures as $fixture) {
            if (class_exists($fixture)) {
                $object = new $fixture();

                $data = $object->getData();
                $this->insertData($data, $default, $addCreated);
                continue;
            }
            if (substr($fixture, -strlen('.yml'))==='.yml') {
                if (!file_exists($fixture)) {
                    $fixture = dirname(__FILE__) . DIRECTORY_SEPARATOR . $fixture;
                    if (!file_exists($fixture)) {
                        throw new \Exception(sprintf('Yaml fixture file %s cannot be found', $fixture));
                    }
                }

                $data = Yaml::parseFile($fixture);
                if ($data) {
                    $this->insertData($data, $default, $addCreated);
                }
                continue;
            }
        }
    }

    protected function insertData(array $data, array $default=[], $addCreated=false)
    {
        foreach($data as $tableName=>$rows) {
            $table = new TableGateway($tableName, $this->db);
            foreach($rows as $row) {
                if (isset($default[$tableName])) {
                    $row += $default[$tableName];
                }
                if ($addCreated !== false) {
                    $prefix = explode('_', key($row))[0] . '_';
                    switch($addCreated) {
                        case ChangeFields::ADD_CREATED_FIELDS:
                            $row[$prefix . 'created'] = $this->getDbNow();
                            $row[$prefix . 'created_by'] = 1;
                            break;
                        case ChangeFields::ADD_CHANGED_FIELDS:
                            $row[$prefix . 'changed'] = $this->getDbNow();
                            $row[$prefix . 'changed_by'] = 1;

                        //Intentional fall through
                        case ChangeFields::ADD_BOTH_FIELDS:
                            $row[$prefix . 'created'] = $this->getDbNow();
                            $row[$prefix . 'created_by'] = 1;
                            break;
                        default;
                            break;
                    }
                }
                $table->insert($row);
            }
        }
    }
}
