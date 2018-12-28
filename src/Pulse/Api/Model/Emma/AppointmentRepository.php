<?php


namespace Pulse\Api\Model\Emma;


use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Sql;

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

    public function getAppointmentExistsBySourceId($sourceId, $source)
    {
        $sql = new Sql($this->db);
        $select = $sql->select();
        $select->from('gems__appointments')
            ->columns(['gap_id_appointment'])
            ->where(['gap_id_in_source' => $sourceId, 'gap_source' => $source]);

        $statement = $sql->prepareStatementForSqlObject($select);

        $result = $statement->execute();

        if ($result->valid() && $result->current()) {
            return true;
        }

        return false;
    }
}