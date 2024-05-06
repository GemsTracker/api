<?php

namespace Gems\Rest\Db;

use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Adapter\Platform\PlatformInterface;
use Laminas\Db\ResultSet\ResultSet;
use Laminas\Db\Sql\Select;
use Laminas\Db\Sql\Sql;
use Laminas\Db\Sql\SqlInterface;
use Laminas\Db\Sql\TableIdentifier;
use Laminas\Db\TableGateway\TableGateway;

class ResultFetcher
{
    protected Sql $sql;
    protected Adapter $db;

    public function __construct(Adapter $db)
    {
        $this->sql = new Sql($db);
        $this->db = $db;
    }

    /**
     * @param Select|string $select
     * @param array|null $params
     * @return array
     */
    public function fetchPairs($select, ?array $params = null): array
    {
        $resultArray = $this->fetchAllAssociative($select, $params);
        if (count($resultArray) === 0) {
            return [];
        }
        $firstRow = reset($resultArray);

        $keyKey   = key($firstRow);
        $valueKey = key(array_slice($firstRow, 1, 1, true));
        if (! $valueKey) {
            // For one column us it for both data sets
            $valueKey = $keyKey;
        }

        return array_column($resultArray, $valueKey, $keyKey);
    }

    /**
     * @param string $tableName
     * @param mixed $where
     * @return int
     */
    public function deleteFromTable(string $tableName, $where): int
    {
        $table = new TableGateway($tableName, $this->getAdapter());
        return $table->delete($where);
    }

    /**
     * @param Select|null $select
     * @param array|null $params
     * @return array
     */
    public function fetchAll($select, ?array $params = null): array
    {
        return $this->fetchAllAssociative($select, $params);
    }

    /**
     * @param Select|null $select
     * @param array|null $params
     * @return array
     */
    public function fetchCol($select, ?array $params = null): array
    {
        $resultArray = $this->fetchAllAssociative($select, $params);
        if (count($resultArray) === 0) {
            return [];
        }
        $firstRow = reset($resultArray);
        $valueKey = key($firstRow);

        return array_column($resultArray, $valueKey);
    }

    /**
     * @param Select|null $select
     * @param array|null $params
     * @return false|mixed|null
     */
    public function fetchOne($select, ?array $params = null)
    {
        $result = $this->query($select, $params);
        $row = $result->current();
        if (is_array($row)) {
            return reset($row);
        }
        return null;
    }

    /**
     * @param Select|null $select
     * @param array|null $params
     * @return array|null
     */
    public function fetchRow($select, ?array $params = null): ?array
    {
        return $this->fetchAssociative($select, $params);
    }

    /**
     * @param Select|string $select
     * @param array|null $params
     * @return array|null
     */
    public function fetchAssociative($select, ?array $params = null): ?array
    {
        $result = $this->query($select, $params);
        $row = $result->current();
        if (is_array($row)) {
            return $result->current();
        }
        return null;
    }

    /**
     * @param Select|string $select
     * @param array|null $params
     * @return array
     */
    public function fetchAllAssociative($select, ?array $params = null): array
    {
        $result = $this->query($select, $params);
        return $result->toArray();
    }

    /**
     * @return Adapter
     */
    public function getAdapter(): Adapter
    {
        return $this->db;
    }

    /**
     * @return PlatformInterface
     */
    public function getPlatform(): PlatformInterface
    {
        return $this->db->getPlatform();
    }

    /**
     * @param SqlInterface $select
     * @return string
     */
    public function getQueryString(SqlInterface $select): string
    {
        return $select->getSqlString($this->db->getPlatform());
    }

    /**
     * @param string|TableIdentifier|null $table
     * @return Select
     */
    public function getSelect($table = null): Select
    {
        return $this->sql->select($table);
    }

    /**
     * @param Select|string $select
     * @param array|null $params
     * @return ResultSet
     */
    public function query($select, ?array $params = null): ResultSet
    {
        $resultSet = new ResultSet(ResultSet::TYPE_ARRAY);
        if ($select instanceof Select) {
            $statement = $this->sql->prepareStatementForSqlObject($select);
            $result = $statement->execute($params);
            $resultSet->initialize($result);
            return $resultSet;
        }

        if ($params === null) {
            $params = Adapter::QUERY_MODE_EXECUTE;
        }

        return $this->db->query($select, $params, $resultSet);
    }

    /**
     * @param string $tableName
     * @param array $values
     * @return int
     */
    public function insertIntoTable(string $tableName, array $values): int
    {
        $table = new TableGateway($tableName, $this->getAdapter());
        $table->insert($values);
        return $this->getAdapter()->getDriver()->getLastGeneratedValue();
    }

    /**
     * @param string $tableName
     * @param array $values
     * @param mixed $where
     * @return int
     */
    public function updateTable(string $tableName, array $values, $where): int
    {
        $table = new TableGateway($tableName, $this->getAdapter());
        return $table->update($values, $where);
    }
}
