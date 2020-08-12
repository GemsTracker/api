<?php


namespace Pulse\Api\Model\Emma;


use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Sql\Sql;

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
        if ($patient = $this->getPatient($patientNr, $organizationId)) {
            if (array_key_exists('gr2o_id_user', $patient)) {
                return $patient['gr2o_id_user'];
            }
        }
        return false;
    }

    public function getPatient($patientNr, $organizationId=null)
    {
        $sql = new Sql($this->db);
        $select = $sql->select();
        $select->from('gems__respondent2org')
            ->join('gems__respondents', 'grs_id_user = gr2o_id_user', ['grs_ssn'])
            ->columns(['gr2o_id_user', 'gr2o_patient_nr', 'gr2o_id_organization'])
            ->where(['gr2o_patient_nr' => $patientNr]);
        if ($organizationId !== null) {
            $select->where(['gr2o_id_organization' => $organizationId]);
        }
        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();

        //if ($result->count() > 0) {
        if ($result->valid() && $result->current()) {
            return $result->current();
        }
        return false;
    }

    public function getPatientsBySsn($ssn)
    {
        $sql = new Sql($this->db);
        $select = $sql->select();
        $select->from('gems__respondent2org')
            ->join('gems__respondents', 'grs_id_user = gr2o_id_user', ['grs_ssn'])
            ->columns(['gr2o_id_user', 'gr2o_patient_nr', 'gr2o_id_organization'])
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

        if ($result->valid() && $result->current()) {
            $user = $result->current();
            return $user['grs_id_user'];
        }
        return false;

    }
}
