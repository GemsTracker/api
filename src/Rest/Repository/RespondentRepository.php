<?php


namespace Gems\Rest\Repository;


use Psr\Http\Message\ServerRequestInterface;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Sql;

class RespondentRepository
{
    public function __construct(Adapter $db)
    {
        $this->db = $db;
    }

    public function getRespondentId($patientNr, $organizationId=null)
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

        if ($result->valid()) {
            return $result->current();
        }
        return false;
    }

    public function getRespondentIdFromRequest(ServerRequestInterface $request)
    {

    }
}