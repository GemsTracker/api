<?php

declare(strict_types=1);


namespace Pulse\Api\Emma\Fhir\Repository;


use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Sql\Sql;
use Laminas\Db\TableGateway\TableGateway;

class AppointmentRepository
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
     * @param $sourceId string Source id
     * @param $epd string epd/source name
     * @return array|null appointment id with organization or null if not found
     */
    public function getAppointmentFromSourceId($sourceId, $epd = null)
    {
        $sql = new Sql($this->db);
        $select = $sql->select();
        $select->from('gems__appointments')
            ->columns(['gap_id_appointment', 'gap_id_organization', 'gap_id_user'])
            ->where([
                'gap_id_in_source' => $sourceId,
            ]);
        if ($epd !== null) {
            $select->where([
                'gap_source' => $epd,
            ]);
        }

        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();

        if ($result->valid() && $result->current()) {
            return $result->current();
        }
        return null;
    }

    public function addEpisodeOfCareIdToAppointmentSourceId($sourceId, $source, $episodeOfCareId)
    {
        $table = new TableGateway('gems__appointments', $this->db);

        return $table->update([
            'gap_id_episode' => $episodeOfCareId,
        ], [
            'gap_source' => $source,
            'gap_id_in_source' => $sourceId,
        ]);
    }

    /**
     * @param $sourceId
     * @param $source
     * @return int changed rows
     */
    public function softDeleteAppointmentFromSourceId($sourceId, $source)
    {
        $table = new TableGateway('gems__appointments', $this->db);

        return $table->update([
            'gap_status' => 'CA',
        ], [
            'gap_source' => $source,
            'gap_id_in_source' => $sourceId,
        ]);
    }
}
