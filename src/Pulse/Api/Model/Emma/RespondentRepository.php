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

    public function getPatientId($patientNr, $organizationId=null)
    {
        $sql = new Sql($this->db);
        $select = $sql->select();
        $select->from('gems__respondent2org')
            ->columns(['gr2o_id_user', 'gr2o_patient_nr', 'gr2o_id_organization'])
            ->where(['gr2o_patient_nr' => $patientNr]);
        if ($organizationId !== null) {
            $select->where(['gr2o_id_organization' => $organizationId]);
        }
        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();

        //if ($result->count() > 0) {
        if ($result->valid()) {
            $user = $result->current();
            return $user['gr2o_id_user'];
        }
        return false;
    }

    public function getPatientsBySsn($ssn)
    {
        $sql = new Sql($this->db);
        $select = $sql->select();
        $select->from('gems__respondent2org')
            ->join('gems__respondents', 'grs_id_user = gr2o_id_user')
            ->columns(['gr2o_patient_nr'])
            ->where(['grs_ssn' => $ssn,]);

        $statement = $sql->prepareStatementForSqlObject($select);

        $result = $statement->execute();

        $patients = iterator_to_array($result);

        if (count($patients) === 0) {
            return null;
        }

        return $patients;
    }

    public function getPatientBySsn($ssn)
    {
        $sql = new Sql($this->db);
        $select = $sql->select();
        $select->from('gems__respondents')
            ->columns(['grs_id_user'])
            ->where(['grs_ssn' => $ssn]);
        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();

        if ($result->valid()) {
            $user = $result->current();
            return $user['grs_id_user'];
        }
        return false;

    }
}