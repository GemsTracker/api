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
        $patientInfo = $this->getPatientInfoFromRespondentInEpd($respondentId, $epdName, $locationId = null);
        $copy = true;
        if ($patientInfo['gr2o_id_organization'] == 81) {
            $copy = false;
            $patientInfo = [];
        }
        $patientInfo['gr2o_id_organization'] = $newOrganizationId;
        $patientInfo['gr2o_id_location'] = $locationId;

        $table = new TableGateway('gems__respondent2org', $this->db);
        try {
            if ($copy) {
                $table->insert($patientInfo);
            } else {
                $table->update($patientInfo, [
                    'gr2o_id_user' => $respondentId,
                    'gr2o_id_organization' => 81,
                ]);
            }
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
            ->join('gems__organizations', 'gor_id_organization = gr2o_id_organization', [])
            ->columns([
                'gr2o_id_user',
                'gr2o_patient_nr',
                'gr2o_id_organization'
            ])
            ->where([
                'gr2o_patient_nr' => $patientNr,
            ]);

        if ($epd !== null) {
            $select->where(['gor_epd' => $epd]);
        }

        $statement = $sql->prepareStatementForSqlObject($select);

        $result = $statement->execute();

        $patients = iterator_to_array($result);

        if (count($patients)) {
            return $patients;
        }

        return null;
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
            ])
            ->order(['gr2o_created']);

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

    public function softDeletePatientFromSourceId($sourceId, $source)
    {
        $sql = new Sql($this->db);
        $update = $sql->update();
        return $update->table('gems__respondent2org')
            ->join('gems__respondents', 'gr2o_id_user = grs_id_user')
            ->join('gems__organizations', 'gr2o_id_organization = gor_id_organization')
            ->set([
                'grs_ssn' => null,
                'gr2o_reception_code' => 'deleted'])
            ->where([
                'gr2o_epd_id' => $sourceId,
                'gor_epd' => $source,
            ]);
    }
}
