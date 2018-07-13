<?php


namespace Pulse\Api\Repository;


use Zalt\Loader\ProjectOverloader;
use Zend\Db\Adapter\Adapter;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Sql\Sql;

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
        "gtr_date_start",
        "gtr_date_until",
        "gtr_active",
        "gtr_code",
        "gtr_survey_rounds",
        "gr2t_mailable",
        "gr2t_id_respondent_track",
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
        if ($treatmentTrack = $this->getTrack($trackTreatmentFilters)) {
            $tracks[] = $treatmentTrack;
        }

        if ($intakeTrack    = $this->getTrack($intakeFilters)) {
            $tracks[] = $intakeTrack;
        }

        $treatmentEpisode = [
            'gte_id_episode' => $treatmentTrack['gr2t_id_respondent_track'],
            'tracks' => $tracks,
        ];

        $treatmentEpisode = array_merge($treatmentEpisode, $this->treatmentTrackValues);

        return $treatmentEpisode;

    }

    protected function getTrack($filters, $addFields=true)
    {
        $respondentTrackModel = $this->tracker->getRespondentTrackModel();

        $sort = [
            'gr2t_created DESC'
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

            $treatmentCodes = $fieldDefinition->getFieldCodesOfType('treatment');

            foreach($treatmentCodes as $treatmentCode=>$value) {
                if (array_key_exists($treatmentCode, $fieldData)) {
                    $treatmentId = $fieldData[$treatmentCode];
                    $treatment = $this->getTreatment($treatmentId);
                }

                break;
            }

            if (!empty($treatment)) {
                $this->treatmentTrackValues['treatment'] = [
                    'id' => $treatment['ptr_id_treatment'],
                    'ptr_name' => $treatment['ptr_name'],
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

        return $track;
    }

    protected function getTreatment($treatmentId)
    {
        $sql = new Sql($this->db);
        $select = $sql->select();
        $select->from('pulse__treatments')
            ->where(['ptr_id_treatment' => $treatmentId, 'ptr_active' => 1]);

        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();
        $resultSet = new ResultSet();
        $resultSet->initialize($result);

        $data = $resultSet->toArray();

        return reset($data);
    }
}