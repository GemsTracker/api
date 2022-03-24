<?php

declare(strict_types=1);


namespace Pulse\Api\Repository;


use Laminas\Db\Sql\Expression;
use Laminas\Db\Sql\Sql;
use Laminas\Db\TableGateway\TableGateway;

class RespondentRepository extends \Gems\Rest\Repository\RespondentRepository
{
    public function copyRespondentToOrganization($respondentId, $newOrganizationId, $epdName)
    {
        $patientInfo = $this->getPatientInfoFromRespondentInEpd($respondentId, $epdName);
        $patientInfo['gr2o_id_organization'] = $newOrganizationId;

        $table = new TableGateway('gems__respondent2org', $this->db);
        try {
            $table->insert($patientInfo);
        } catch(\Exception $e) {
            return false;
        }
        return true;
    }

    public function getPatientsFromPatientNr($patientNr, $epd = null)
    {
        $sql = new Sql($this->db);
        $select = $sql->select();
        $select->from('gems__respondent2org')
            ->join('gems__respondents', 'grs_id_user = gr2o_id_user', ['grs_ssn'])
            ->join('gems__organizations', 'gor_id_organization = gr2o_id_organization')
            ->columns([
                'gr2o_id_user',
                'gr2o_patient_nr',
                'gr2o_id_organization'
            ])
            ->where([
                'grs_ssn' => null,
                'gr2o_patient_nr' => $patientNr,
            ]);

        if ($epd !== null) {
            $select->where(['gor_epd' => $epd]);
        }

        $statement = $sql->prepareStatementForSqlObject($select);

        $result = $statement->execute();

        $patients = iterator_to_array($result);

        if (count($patients) === 0) {
            return null;
        }
    }

    public function getPatientsFromSsn($ssn, $epd = null)
    {
        $sql = new Sql($this->db);
        $select = $sql->select();
        $select->from('gems__respondent2org')
            ->join('gems__respondents', 'grs_id_user = gr2o_id_user', ['grs_ssn'])
            ->columns(['gr2o_id_user', 'gr2o_patient_nr', 'gr2o_id_organization'])
            ->where(['grs_ssn' => $ssn,]);

        if ($epd !== null) {
            $select->join('gems__organizations', 'gor_id_organization = gr2o_id_organization', [])
                ->where(['gor_epd' => $epd]);
        }

        $statement = $sql->prepareStatementForSqlObject($select);

        $result = $statement->execute();

        $patients = iterator_to_array($result);

        if (count($patients)) {
            return $patients;
        }

        return null;
    }

    public function getPatientInfoFromRespondentInEpd($respondentId, $epdName)
    {
        $sql = new Sql($this->db);
        $select = $sql->select();
        $select->from('gems__respondent2org')
            ->join('gems__organizations', 'gor_id_organization = gr2o_id_organization', [])
            ->where([
                'gr2o_id_user' => $respondentId,
                'gor_epd' => $epdName,
            ]);

        $test = $sql->buildSqlString($select);

        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();

        if ($result->valid() && $result->current()) {
            return $result->current();
        }
        return null;
    }

    public function getRespondentIdFromEpdId($epdId, $epd)
    {
        $sql = new Sql($this->db);
        $select = $sql->select();
        $select->from('gems__respondent2org')
            ->join('gems__organizations', 'gor_id_organization = gr2o_id_organization', [])
            ->columns(['gr2o_id_user'])
            ->where([
                'gr2o_epd_id' => $epdId,
                'gor_epd' => $epd,
            ]);

        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();

        if ($result->valid() && $result->current()) {
            $user = $result->current();
            return $user['gr2o_id_user'];
        }
        return null;
    }

    public function patientNrExistsInEpd($patientNr, $epd)
    {
        $sql = new Sql($this->db);
        $select = $sql->select();
        $select->from('gems__respondent2org')
            ->join('gems__organizations', 'gor_id_organization = gr2o_id_organization', [])
            ->columns(['count' => new Expression('COUNT(*)')])
            ->where([
                'gr2o_patient_nr' => $patientNr,
                'gor_epd' => $epd,
            ]);

        $statement = $sql->prepareStatementForSqlObject($select);

        $result = $statement->execute();
        if ($result->count()) {
            return true;
        }
        return false;
    }

    public function respondentExistsInOrganization($respondentId, $organizationId)
    {
        $sql = new Sql($this->db);
        $select = $sql->select();
        $select->from('gems__respondent2org')
            ->columns(['count' => new Expression('COUNT(*)')])
            ->where([
                'gr2o_id_user' => $respondentId,
                'gr2o_id_organization' => $organizationId,
            ]);

        $test = $sql->buildSqlString($select);

        $statement = $sql->prepareStatementForSqlObject($select);

        $result = $statement->execute();
        if ($result->valid() && $result->current()) {
            $current = $result->current();
            if (isset($current['count']) && $current['count'] > 0) {
                return true;
            }
        }
        return false;
    }
}
