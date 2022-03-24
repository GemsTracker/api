<?php

declare(strict_types=1);


namespace Pulse\Api\Emma\Fhir\Repository;


use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Sql\Sql;

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
     * @return int|null episode of care ID or null of not found
     */
    public function getEpisodeOfCareBySourceId($sourceId, $source)
    {
        $sql = new Sql($this->db);
        $select = $sql->select();
        $select->from('gems__episodes_of_care')
            ->columns(['gec_episode_of_care_id'])
            ->where([
                'gec_source' => $source,
                'gec_id_in_source' => $sourceId,
            ]);

        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();

        if ($result->valid() && $result->current()) {
            $current = $result->current();
            if (isset($current['gec_episode_of_care_id'])) {
                return (int)$current['gec_episode_of_care_id'];
            }
        }
        return null;
    }
}
