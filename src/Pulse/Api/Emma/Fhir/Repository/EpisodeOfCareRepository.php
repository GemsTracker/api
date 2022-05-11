<?php

declare(strict_types=1);


namespace Pulse\Api\Emma\Fhir\Repository;


use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Sql\Sql;
use Laminas\Db\TableGateway\TableGateway;

class EpisodeOfCareRepository
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
     * @param $sourceId string source ID
     * @param $source string epd/source name
     * @return array|null episode of care fields or null of not found
     */
    public function getEpisodeOfCareBySourceId($sourceId, $source)
    {
        $sql = new Sql($this->db);
        $select = $sql->select();
        $select->from('gems__episodes_of_care')
            ->columns(['gec_episode_of_care_id', 'gec_id_user'])
            ->where([
                'gec_source' => $source,
                'gec_id_in_source' => $sourceId,
            ]);

        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();

        if ($result->valid() && $result->current()) {
            return $result->current();
        }
        return null;
    }

    /**
     * @param $sourceId
     * @param $source
     * @return int changed rows
     */
    public function softDeleteEpisodeFromSourceId($sourceId, $source)
    {
        $table = new TableGateway('gems__episodes_of_care', $this->db);

        return $table->update([
            'gec_status' => 'C',
        ], [
            'gec_source' => $source,
            'gec_id_in_source' => $sourceId,
        ]);
    }
}
