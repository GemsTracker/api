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
        $includeColumns = [
            "gtr_id_track",
            "gtr_track_name",
            "gtr_date_start",
            "gtr_date_until",
            "gtr_active",
            "gtr_code",
            "gtr_survey_rounds",
            "gr2t_mailable",
        ];

        $respondentTrackModel = $this->tracker->getRespondentTrackModel();

        $itemNames = array_flip($respondentTrackModel->getItemNames());
        foreach($filters as $filterField=>$filterValue) {
            if (!isset($itemNames[$filterField])) {
                unset($filters[$filterField]);
            }
        }

        if ($id != 0) {
            $filters['gr2t_id_respondent_track'] = $id;
        }

        $sort = [
            'gr2t_created DESC'
        ];

        $respondentTrackData = $respondentTrackModel->loadFirst($filters, $sort);


        /*$track = [
            'gtr_id_track' => $respondentTrack->getTrackId(),
            'gtr_track_name' => $respondentTrack->getTrackEngine()->getTrackName(),
            'gtr_date_start' => $respondentTrack->getStartDate(),
            'gtr_date_until' => $respondentTrack->getEndDate(),
            'gtr_code' => $respondentTrack->getTrackEngine()->getTrackCode(),
            'gr2t_mailable' => $respondentTrack->
        ];*/

        foreach($includeColumns as $columnName) {
            if (array_key_exists($columnName, $respondentTrackData)) {
                $track[$columnName] = $respondentTrackData[$columnName];
            }
        }

        $respondentTrack = $this->tracker->getRespondentTrack($respondentTrackData);

        //$codeFields = $respondentTrack->getCodeFields();
        $fieldData = $respondentTrack->getFieldData();
        $fieldDefinition = $respondentTrack->getTrackEngine()->getFieldsDefinition();
        $fieldCodes = $fieldDefinition->getFieldCodes();

        $fields = [];
        foreach($fieldCodes as $fieldId=>$fieldCode) {
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


        $treatmentEpisode = [
            'gte_id_episode' => $respondentTrackData['gr2t_id_respondent_track'],
            'tracks' => [
                $track,
            ]
        ];



        if (!empty($treatment)) {
            $treatmentEpisode['treatment'] = [
                'id' => $treatment['ptr_id_treatment'],
                'ptr_name' => $treatment['ptr_name'],
            ];
        }

        if (isset($fields['side'])) {
            $treatmentEpisode['side'] = $fields['side'];
        }

        if (isset($fields['treatmentAppointment'])) {
            $treatmentAppointment = $fields['treatmentAppointment'];
            $appointment = $this->getAppointment($treatmentAppointment);
            if ($appointment instanceof \Gems_Agenda_Appointment) {
                $treatmentEpisode['treatmentAppointment'] = $appointment->getAdmissionTime();
            }
        }

        return $treatmentEpisode;

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