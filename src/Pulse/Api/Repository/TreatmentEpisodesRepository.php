<?php


namespace Pulse\Api\Repository;


use Zalt\Loader\ProjectOverloader;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\ResultSet\ResultSet;
use Laminas\Db\Sql\Sql;

class TreatmentEpisodesRepository
{
    /**
     * @var Zend\Db\Adapter\Adapter
     */
    protected $db;

    /**
     * @var ProjectOverloader
     */
    protected $loader;

    /**
     * @var \Gems_Tracker
     */
    protected $tracker;

    protected $trackIncludeColumns = [
        "gtr_id_track",
        "gtr_track_name",
        //"gtr_date_start",
        //"gtr_date_until",
        //"gtr_active",
        "gtr_code",
        //"gtr_survey_rounds",
        "gr2t_mailable",
        "gr2t_id_respondent_track",
        "gr2t_id_organization",
        'gr2o_patient_nr',
    ];

    protected $treatmentTrackValues = [];

    public function __construct(\Gems_Tracker $tracker, Adapter $db, ProjectOverloader $loader)
    {
        $this->db = $db;
        $this->loader = $loader;
        $this->tracker = $tracker;
    }

    protected function getAppointment($appointmentData)
    {
        return $this->loader->create('Agenda_Appointment', $appointmentData);
    }

    public function getTreatmentEpisode($id, $filters=[])
    {
        $respondentTrackModel = $this->tracker->getRespondentTrackModel();

        $itemNames = array_flip($respondentTrackModel->getItemNames());
        foreach($filters as $filterField=>$filterValue) {
            if (!isset($itemNames[$filterField])) {
                unset($filters[$filterField]);
            }
        }

        $trackTreatmentFilters = $filters;
        $intakeFilters = $filters;

        $trackTreatmentFilters[] = new \Zend_Db_Expr('(gtr_code IS NULL OR gtr_code NOT IN ("intake", "anesthesia"))');
        if ($id != 0) {
            $trackTreatmentFilters['gr2t_id_respondent_track'] = $id;
        }

        $intakeFilters[] = 'gtr_code = "intake"';

        $tracks = [];
        if ($treatmentTrack = $this->getTrack($trackTreatmentFilters, true, true)) {
            $tracks[] = $treatmentTrack;
            // The intake track should be the same organization as the treatment track!
            $intakeFilters['gr2o_id_organization'] = $treatmentTrack['gr2t_id_organization'];
        }

        if ($intakeTrack = $this->getTrack($intakeFilters, true, false)) {
            $tracks[] = $intakeTrack;
        }

        $treatmentEpisode = [
            'gte_id_episode' => null,
            'tracks' => $tracks,
        ];
        if ($treatmentTrack && isset($treatmentTrack['gr2t_id_respondent_track'])) {
            $treatmentEpisode['gte_id_episode'] = $treatmentTrack['gr2t_id_respondent_track'];
        }

        $treatmentEpisode = array_merge($treatmentEpisode, $this->treatmentTrackValues);

        return $treatmentEpisode;

    }

    protected function getTrack($filters, $addFields=true, $setTreatment=true)
    {
        $respondentTrackModel = $this->tracker->getRespondentTrackModel();

        $sort = [
            'gr2t_start_date' => SORT_DESC
        ];

        $respondentTrackData = $respondentTrackModel->loadFirst($filters, $sort);

        if ($respondentTrackData === false) {
            return null;
        }
        foreach($this->trackIncludeColumns as $columnName) {
            if (array_key_exists($columnName, $respondentTrackData)) {
                $track[$columnName] = $respondentTrackData[$columnName];
            }
        }
        if ($addFields) {
            $respondentTrack = $this->tracker->getRespondentTrack($respondentTrackData);

            $fieldData = $respondentTrack->getFieldData();
            $fieldDefinition = $respondentTrack->getTrackEngine()->getFieldsDefinition();
            $fieldCodes = $fieldDefinition->getFieldCodes();

            $fields = [];
            foreach ($fieldCodes as $fieldId => $fieldCode) {
                if ($fieldCode && array_key_exists($fieldCode, $fieldData)) {
                    $fields[$fieldCode] = $fieldData[$fieldCode];
                }
            }

            $track['fields'] = $fields;

            if ($setTreatment) {
                $treatmentCodes = $fieldDefinition->getFieldCodesOfType('treatment');

                foreach ($treatmentCodes as $treatmentCode => $value) {
                    if (array_key_exists($treatmentCode, $fieldData)) {
                        $treatmentId = $fieldData[$treatmentCode];
                        $treatment = $this->getTreatment($treatmentId);
                    }

                    break;
                }

                if (!empty($treatment)) {
                    $this->treatmentTrackValues['treatment'] = [
                        'id' => $treatment['gtrt_id_treatment'],
                        'name' => $treatment['gtrt_name'],
                    ];
                }

                if (isset($fields['side'])) {
                    $this->treatmentTrackValues['side'] = $fields['side'];
                }

                if (isset($fields['treatmentAppointment'])) {
                    $treatmentAppointment = $fields['treatmentAppointment'];
                    $appointment = $this->getAppointment($treatmentAppointment);
                    if ($appointment instanceof \Gems_Agenda_Appointment) {
                        $this->treatmentTrackValues['treatmentAppointment'] = $appointment->getAdmissionTime();
                    }
                }
            }
        }

        return $track;
    }

    protected function getTreatment($treatmentId)
    {
        $sql = new Sql($this->db);
        $select = $sql->select();
        $select->from('gems__treatments')
            ->where(['gtrt_id_treatment' => $treatmentId, 'gtrt_active' => 1]);

        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();
        $resultSet = new ResultSet();
        $resultSet->initialize($result);

        $data = $resultSet->toArray();

        return reset($data);
    }
}
