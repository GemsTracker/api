<?php

namespace Gems\Rest\Model;

use Exception;
use Zalt\Loader\ProjectOverloader;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Adapter\Driver\ResultInterface;
use Zend\Db\Metadata\Object\AbstractTableObject;
use Zend\Db\Metadata\Source\Factory as MetadataFactory;
use Zend\Db\ResultSet\HydratingResultSet;

use Zend\Db\Sql\Sql;
use Zend\Hydrator\NamingStrategy\UnderscoreNamingStrategy;
use Zend\Hydrator\Reflection;


class EntityRepositoryAbstract
{
    /**
     * @var Zend\Db\Adapter\Adapter;
     */
    protected $db;

    /**
     * @var Zalt\Loader\ProjectOverloader
     */
    protected $loader;

    protected $nonPersistColumns = [];

    /**
     * @var AbstractTableObject
     */
    protected $tableObject;

    public function __construct(Adapter $db, ProjectOverloader $loader)
    {
        $this->db = $db;
        $this->loader = $loader;
    }

    /**
     * Check data to see if all columns exist in a database
     *
     * @param $data
     * @return mixed
     * @throws Exception when column does not exist
     */
    protected function checkDataColumns($data)
    {
        $columns = array_flip($this->getColumnNames());
        foreach($data as $key=>$value) {
            /*if (isset($this->nonPersistColumns[$key])) {
                unset($data[$key]);
                continue;
            }*/

            if (!isset($columns[$key])) {
                throw new \Exception(sprintf('Column %s not found in database table', $key));
            }
        }

        return $data;
    }

    /**
     * Filter data before saving
     *
     * @param $data
     * @return array filtered data
     */
    protected function filterDataForSave($data)
    {
        $data = $this->checkDataColumns($data);

        foreach($data as $key=>$value) {
            if ($value instanceof \DateTime) {
                $data[$key] = $value->format('Y-m-d H:i:s');
            }
        }

        return $data;
    }

    /**
     * Get the table column names
     *
     * @return array column names
     */
    protected function getColumnNames()
    {
        $tableObject = $this->getTableObject();
        $tableColumns = $tableObject->getColumns();

        $columns = [];
        foreach($tableColumns as $column) {
            $columns[] = $column->getName();
        }

        return $columns;
    }

    /**
     * Get the table primary keys
     *
     * @return array table primary keys
     */
    protected function getTableKeys()
    {
        $tableObject = $this->getTableObject();
        $constraints = $tableObject->getConstraints();

        $keys = [];
        foreach($constraints as $constraint) {
            if ($constraint->isPrimaryKey()) {
                $keys = array_merge($keys, $constraint->getColumns());
            }
        }

        return $keys;
    }

    /**
     * Get the table object for metadata
     * @return \Zend\Db\Metadata\Object\TableObject
     */
    protected function getTableObject()
    {
        if (!$this->tableObject) {
            if (!$this->tableObject instanceof AbstractTableObject) {
                $metadata = MetadataFactory::createSourceFromAdapter($this->db);
                $this->tableObject = $metadata->getTable($this->table);
            }
        }

        return $this->tableObject;
    }

    protected function loadResults($filter = [])
    {
        $sql = new Sql($this->db);

        $select = $sql->select();
        $select->from($this->table);

        if (is_array($filter)) {
            if (isset($filter['limit'])) {
                if (is_array($filter['limit'])) {
                    $count  = array_shift($filter['limit']);
                    $offset = reset($filter['limit']);
                } else {
                    $count  = $filter['limit'];
                    $offset = null;
                }
                $select->limit($count);
                if ($offset) {
                    $select->offset($offset);
                }

                unset($filter['limit']);
            }
        }

        $select->where($filter);

        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();

        return $result;
    }

    public function load($filter = [])
    {
        $result = $this->loadResults($filter);

        $reflectionHydrator = new Reflection;
        $reflectionHydrator->setNamingStrategy(new UnderscoreNamingStrategy);

        $resultSet = new HydratingResultSet($reflectionHydrator, $this->loader->create($this->entity));
        $resultSet->initialize($result);

        $resultArray = [];
        foreach ($resultSet as $resultItem) {
            $resultArray[] = $resultItem;
        }

        return $resultArray;
    }

    public function loadFirst($filter = [])
    {
        if (is_array($filter)) {
            if (isset($filter['limit'])) {
                if (is_array($filter['limit'])) {
                    $count = array_shift($filter['limit']);
                    $offset = reset($filter['limit']);
                    $count = 1;
                    $filter['limit'] = [$count, $offset];
                } else {
                    $filter['limit'] = 1;
                }
            } else {
                $filter['limit'] = 1;
            }
        }

        $dataArray = $this->load($filter);

        return reset($dataArray);
    }

    /**
     * Extract the values from an entity
     *
     * @param EntityInterface $entity
     * @param bool $removeEmptyValues
     * @return array values
     */
    protected function getEntityValues(EntityInterface $entity, $removeEmptyValues = false) {
        $reflectionHydrator = new Reflection;
        $reflectionHydrator->setNamingStrategy(new UnderscoreNamingStrategy);
        $values = $reflectionHydrator->extract($entity);

        if ($removeEmptyValues) {
            foreach($values as $key=>$value) {
                if (null === $value) {
                    unset($value);
                }
            }
        }

        return $values;
    }

    /**
     * Save array data or an entity
     *
     * @param $data Gems\Rest\Model\EntityInterface|array
     * @param array $filter
     * @return ResultInterface
     */
    public function save($data, $filter=[])
    {
        if ($data instanceof EntityInterface) {
            $data = $this->getEntityValues($data, true);
        }

        $update = false;
        foreach($this->getTableKeys() as $key) {
            //if (!isset($data[$key]) || (0 !== strlen($data[$key]))) {
            if (isset($data[$key]) && (0 !== strlen($data[$key]))) {
                $update = true;
                if (!isset($filter[$key])) {
                    $filter[$key] = $data[$key];
                }
            }
        }

        $data = $this->filterDataForSave($data);

        $sql = new Sql($this->db);

        if ($update) {
            $update = $sql->update();
            $update->table($this->table)
                ->set($data)
                ->where($filter);
            $statement = $sql->prepareStatementForSqlObject($update);
        } else {
            $insert = $sql->insert();
            $insert->into($this->table)
                ->columns(array_keys($data))
                ->values($data);
            $statement = $sql->prepareStatementForSqlObject($insert);
        }

        $result = $statement->execute();
        return $result;
    }
}