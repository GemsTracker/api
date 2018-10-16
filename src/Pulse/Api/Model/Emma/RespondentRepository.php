<?php


namespace Pulse\Api\Model\Emma;


use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Sql;

class RespondentRepository
{
    /**
     * @var Adapter
     */
    protected $db;

    public function __construct(Adapter $db)
    {
        $this->db = $db;
    }

    public function getPatientId($patientNr, $organizationId)
    {
        $sql = new Sql($this->db);
        $select = $sql->select();
        $select->from('gems__respondent2org')
            ->columns(['gr2o_id_user', 'gr2o_patient_nr', 'gr2o_id_organization'])
            ->where(['gr2o_patient_nr' => $patientNr, 'gr2o_id_organization' => $organizationId]);
        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();

        if ($result->count() > 0) {
            $user = $result->current();
            return $user['gr2o_id_user'];
        }
        return false;
    }
}