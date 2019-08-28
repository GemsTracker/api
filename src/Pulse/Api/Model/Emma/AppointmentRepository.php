<?php


namespace Pulse\Api\Model\Emma;


use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Sql;

class AppointmentRepository
{
    public $canceledAppointmentStatus = 'CA';

    /**
     * @var Adapter
     */
    protected $db;

    public $sourceVersionSuffix = '_v';

    public function __construct(Adapter $db)
    {
        $this->db = $db;
    }

    public function getAppointmentDataBySourceId($sourceId, $source)
    {
        $sql = new Sql($this->db);
        $select = $sql->select();
        $select->from('gems__appointments')
            ->where(['gap_id_in_source' => (string)$sourceId, 'gap_source' => (string)$source]);

        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();

        if ($result->valid() && $result->current()) {
            $row = (array) $result->current();
            return $row;
        }

        return false;
    }

    public function getLatestAppointmentVersion($sourceId, $source)
    {
        $sourcePrefix = $sourceId . $this->sourceVersionSuffix;

        $sql = new Sql($this->db);
        $select = $sql->select();
        $select->from('gems__appointments')
            ->columns(['gap_id_in_source'])
            ->where(['gap_source' => $source]);
        $select->where->like('gap_id_in_source', $sourcePrefix . '%');
        $select->order('gap_id_in_source DESC');

        $statement = $sql->prepareStatementForSqlObject($select);

        $result = $statement->execute();

        if ($result->valid() && $result->current()) {
            $row = $result->current();
            $latestSourceId = $row['gap_id_in_source'];

            return (int) str_replace($sourcePrefix, '', $latestSourceId);
        }

        return 0;
    }
}