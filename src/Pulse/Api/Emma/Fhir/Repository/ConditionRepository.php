<?php

declare(strict_types=1);


namespace Pulse\Api\Emma\Fhir\Repository;


use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Sql\Sql;
use Laminas\Db\TableGateway\TableGateway;

class ConditionRepository
{
    /**
     * @var Adapter
     */
    protected $db;

    public function __construct(Adapter $db)
    {
        $this->db = $db;
    }

    /**
     * Get a condition by its source ID
     *
     * @param $sourceId string Source ID
     * @param $source string Source
     * @return array|null list of condition id, source id and episode id, or null if not found
     */
    public function getConditionBySourceId($sourceId, $source)
    {
        $sql = new Sql($this->db);
        $select = $sql->select();
        $select->from('gems__medical_conditions')
            ->columns(['gmco_id_condition', 'gmco_id_source', 'gmco_id_episode_of_care', 'gmco_id_user'])
            ->where([
                'gmco_id_source' => $sourceId,
                'gmco_source' => $source,
            ]);

        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();

        if ($result->valid() && $result->current()) {
            return $result->current();
        }
        return null;
    }

    /**
     * @param $sourceId string Source ID
     * @param $source string Source
     * @return int|null Linked Episode of care ID or null if not found
     */
    public function getEpisodeOfCareIdFromConditionBySourceId($sourceId, $source)
    {
        $condition = $this->getConditionBySourceId($sourceId, $source);
        if ($condition !== null && array_key_exists('gmco_id_episode_of_care', $condition)) {
            return $condition['gmco_id_episode_of_care'];
        }
        return null;
    }

    public function addEpisodeOfCareIdToConditionSourceId($sourceId, $source, $episodeOfCareId)
    {
        $table = new TableGateway('gems__medical_conditions', $this->db);

        return $table->update([
            'gmco_id_episode_of_care' => $episodeOfCareId,
        ], [
            'gmco_id_source' => $sourceId,
            'gmco_source' => $source,
        ]);
    }

    /**
     * @param $sourceId
     * @param $source
     * @return int changed rows
     */
    public function softDeleteConditionFromSourceId($sourceId, $source)
    {
        $table = new TableGateway('gems__medical_conditions', $this->db);

        return $table->update([
            'gmco_active' => 0,
        ], [
            'gmco_source' => $source,
            'gmco_id_source' => $sourceId,
        ]);
    }
}
