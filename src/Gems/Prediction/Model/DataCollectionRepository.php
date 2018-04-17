<?php


namespace Gems\Prediction\Model;


use Gems\Tracker\Field\AppointmentField;
use Zalt\Loader\ProjectOverloader;
use Zend\Db\Adapter\Adapter;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Sql\Sql;

class DataCollectionRepository
{
    /**
     * @var \Zend\Db\Adapter\Adapter
     */
    protected $db;

    /**
     * @var ProjectOverloader
     */
    protected $loader;

    /**
     * @var \Gems_Tracker_Respondent;
     */
    protected $respondent;


    protected $respondentTrackId;

    /**
     * @var \Gems_Tracker
     */
    protected $tracker;

    public function __construct(Adapter $db, ProjectOverloader $loader, \Gems_Tracker $tracker)
    {
        $this->db = $db;
        $this->loader = $loader;
        $this->tracker = $tracker;
    }

    protected function changeToJsonDates($data)
    {
        foreach($data as $key=>$value) {
            if ($value instanceof \MUtil_Date) {
                $data[$key] = $value->toString(\MUtil_Date::ISO_8601);
            }
        }

        return $data;
    }

    protected function getDataFromTypes($predictionTypes)
    {
        $respondentData = [];
        $trackfieldData = [];
        $surveyData = [];

        /*foreach($predictionTypes as $type=>$mappings) {
            switch ($type) {
                case 'Respondent':
                    $respondentData = $this->getRespondentData($mappings);
                    break;

                case 'Trackfield':
                    $trackfieldData = $this->getRespondentTrackfieldData($mappings);
                    break;

                case 'Survey':
                    $surveyData = $this->getRespondentSurveyData($mappings);
                    break;
            }
        }*/
        if (isset($predictionTypes['Respondent'])) {
            $respondentData = $this->getRespondentData($predictionTypes['Respondent']);
        }

        if (isset($predictionTypes['Trackfield'])) {
            $trackfieldData = $this->getRespondentTrackfieldData($predictionTypes['Trackfield']);
        }

        if (isset($predictionTypes['Survey'])) {
            $surveyData = $this->getRespondentSurveyData($predictionTypes['Survey'], $trackfieldData);
        }

        if (isset($predictionTypes['Fixed'])) {
            $fixedData = [];
            foreach ($predictionTypes['Fixed'] as $mapping) {
                $fixedData[$mapping['gpmm_variable_name']] = $mapping['gpmm_type_id'];
            }
        }

        $data = [];
        foreach($surveyData as $round=>$surveyRoundData) {
            $combinedData = array_merge($respondentData, $trackfieldData, $surveyRoundData, $fixedData);
            foreach ($combinedData as $field=>$item) {
                if ($item instanceof \MUtil_Date) {
                    $combinedData[$field] = $item->toString(\MUtil_Date::ISO_8601);
                }
            }
            $data[] = $combinedData;
        }

        return $data;
    }

    public function getPredictionChartsData($patientNr, $organizationId)
    {

        $sql = new Sql($this->db);
        $select = $sql->select();
        $select->from('gems__respondent2track')
            ->join('gems__respondent2org', 'gr2o_id_user = gr2t_id_user AND gr2o_id_organization = gr2t_id_organization', [])
            ->join('gems__tracks', 'gtr_id_track = gr2t_id_track', ['gtr_track_name'])
            ->join('gems__prediction_models', 'gpm_id_track = gr2t_id_track', ['gpm_id', 'gpm_name'])
            ->columns(['gr2t_id_respondent_track'])
            ->where(['gr2o_patient_nr' => $patientNr, 'gr2t_id_organization' => $organizationId]);

        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();
        $resultSet = new ResultSet();
        $resultSet->initialize($result);

        $data = $resultSet->toArray();
        $sortedData = [];
        foreach($data as $predictionChartData) {
            $renamedData = [
                'respondentTrack' => $predictionChartData['gr2t_id_respondent_track'],
                'modelId' => $predictionChartData['gpm_id'],
                'title' => $predictionChartData['gpm_name'],
            ];

            $sortedData[$predictionChartData['gtr_track_name']][] = $renamedData;
        }

        return $sortedData;
    }

    public function getPredicationDataInputModel($predictionId, $patientNr=null, $organizationId=null, $respondentTrackId=null)
    {
        // Set Respondent if supplied
        if ($patientNr !== null && $organizationId !== null) {
            $this->setRespondent($patientNr, $organizationId);
        }

        if (!$this->respondent instanceof \Gems_Tracker_Respondent) {
            throw new DataCollectionMissingDataException('Respondent not found');
        }

        if ($respondentTrackId !== null) {
            $this->respondentTrackId = $respondentTrackId;
        } else {
            throw new DataCollectionMissingDataException('No respondent track supplied');
        }

        // Get all prediction model mapping
        // Sort prediction model mapping by type
        $predictionTypes = $this->getPredictionMappingsByType($this->getPredictionMappings($predictionId));
        // Per type and subtype get all data
        // Add all data to all rows of an array
        $data = $this->getDataFromTypes($predictionTypes);

        return $data;
    }

    protected function getPredictionMappingsByType($predictionMappings)
    {
        $predictionTypes = [];
        foreach ($predictionMappings as $predictionMapping)
        {
            $type = $predictionMapping['gpmm_variable_type'];
            $predictionTypes[$type][] = $predictionMapping;
        }

        return $predictionTypes;
    }

    protected function getPredictionMappings($predictionId)
    {
        $sql = new Sql($this->db);
        $select = $sql->select();
        $select->from('gems__prediction_model_mapping')
            ->where(['gpmm_prediction_model_id' => $predictionId]);
        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();
        $resultSet = new ResultSet();
        $resultSet->initialize($result);

        return $resultSet->toArray();
    }

    protected function getRespondent()
    {
        return $this->respondent;
    }

    protected function getRespondentData($mappings)
    {
        $respondent = $this->getRespondent();
        foreach($mappings as $mapping) {
            $typeId = $mapping['gpmm_type_id'];
            switch ($typeId) {
                case 'age':
                    $itemData = $respondent->getAge();
                    break;
                case 'birthday':
                    $itemData = $respondent->getBirthday();
                    break;
                case 'gender':
                    $itemData = $respondent->getGender();
                    break;
                default:
                    $itemData = null;
            }

            if ($mapping['gpmm_custom_mapping']) {
                $customMapping = json_decode($mapping['gpmm_custom_mapping'], true);
                if (isset($customMapping[$itemData])) {
                    $itemData = $customMapping[$itemData];
                }
            }

            if ($mapping['gpmm_required'] && $itemData === null) {
                throw new DataCollectionMissingDataException('Required data not found in respondent');
            }

            $data[$mapping['gpmm_variable_name']] = $itemData;
        }

        //$data = $this->changeToJsonDates($data);

        return $data;
    }

    protected function getRespondentSurveyData($mappings, $trackfieldData=null)
    {
        $data = [];
        $tokenModel = $this->tracker->getTokenModel();

        $surveys = [];
        $mappingsPerSurvey = [];
        foreach($mappings as $mapping) {
            $surveys[$mapping['gpmm_type_id']] = true;
            $mappingsPerSurvey[$mapping['gpmm_type_id']][] = $mapping;
        }
        $surveys = array_keys($surveys);

        $filter = [
            'gto_id_respondent'     => $this->respondent->getId(),
            'gto_id_organization'   => $this->respondent->getOrganizationId(),
            'gto_id_survey'        => $surveys,
            'gto_completion_time IS NOT NULL',
            'grc_success'           => 1,
        ];

        $sort = [
            'gto_id_survey',
            'gto_round_order'
        ];

        $tokens = $tokenModel->load($filter, $sort);

        $incompleteRounds = [];

        foreach($tokens as $tokenData) {
            $token = $this->tracker->getToken($tokenData);
            $answers = $token->getRawAnswers();
            foreach($mappingsPerSurvey[$tokenData['gto_id_survey']] as $mapping) {
                if ($mapping['gpmm_type_sub_id'] == "{{completion_time}}") {
                    $data[$tokenData['gto_round_description']][$mapping['gpmm_variable_name']] = $token->getCompletionTime();
                }

                // Possibly Temporary calculation of time in days. Might be cut and put into R
                if ($mapping['gpmm_type_sub_id'] == "{{time_in_days}}") {
                    if (isset($trackfieldData[0])) {
                        $zero = $trackfieldData[0];
                    } else {
                        $zero = $token->getRespondentTrack()->getStartDate();
                    }

                    $end = $token->getCompletionTime();
                    $data[$tokenData['gto_round_description']][$mapping['gpmm_variable_name']] = $end->diffDays($zero);
                }

                // Possibly temporary filter of the SIDE trackfield in a survey variable. For MHQ questionair.
                // Might be solved in Limesurvey
                if (strpos($mapping['gpmm_type_sub_id'], '{{side}}') !== false && isset($trackfieldData['side'])) {
                    $side = strtolower($trackfieldData['side']);
                    $mapping['gpmm_type_sub_id'] = str_replace('{{side}}', $side, $mapping['gpmm_type_sub_id']);
                }

                if (array_key_exists($mapping['gpmm_type_sub_id'], $answers)) {
                    $itemData = $answers[$mapping['gpmm_type_sub_id']];
                    if (is_numeric($itemData) && is_string($itemData)) {
                        $itemData = (float)$itemData;
                    }
                    if ($mapping['gpmm_custom_mapping']) {
                        $customMapping = json_decode($mapping['gpmm_custom_mapping'], true);
                        if (isset($customMapping[$itemData])) {
                            $itemData = $customMapping[$itemData];
                        }
                    }

                    if ($mapping['gpmm_required'] && $itemData === null) {
                        $incompleteRounds[$tokenData['gto_round_description']] = true;
                    }

                    $data[$tokenData['gto_round_description']][$mapping['gpmm_variable_name']] = $itemData;
                }
            }
            // Add completion time per survey on survey code field. Disabled for now in favor of {{completion_time}} sub ID
            /*$surveyColumnName = $token->getSurvey()->getCode();
            $data[$tokenData['gto_round_description']][$surveyColumnName] = $tokenData['gto_completion_time'];*/
        }

        if (count($data) === count($incompleteRounds)) {
            throw new DataCollectionMissingDataException('Required data not found in surveys');
        }

        foreach($incompleteRounds as $round=>$value) {
            if (isset($data[$round])) {
                unset($data[$round]);
            }
        }

        return $data;
    }

    protected function getRespondentTrackfieldData($mappings)
    {

        $respondentTrack = $this->tracker->getRespondentTrack($this->respondentTrackId);
        $fieldsDefinition = $this->getFieldsDefinitions($respondentTrack->getTrackId());
        $fieldsdata = $fieldsDefinition->getFieldsDataFor($this->respondentTrackId);
        $fieldCodes = array_flip($fieldsDefinition->getFieldCodes());


        foreach($mappings as $mapping) {
            $typeId = $mapping['gpmm_type_id'];

            if (isset($fieldCodes[$typeId]) && $mapping['gpmm_type_sub_id'] == null) {
                $itemData = $fieldsdata[$fieldCodes[$typeId]];
                $field = $fieldsDefinition->getField($fieldCodes[$typeId]);
                if ($field instanceof AppointmentField) {
                    $appointment = $this->getAppointment($itemData);
                    if ($appointment instanceof \Gems_Agenda_Appointment) {
                        $itemData = $appointment->getAdmissionTime();
                    }
                }
            }

            /*if (isset($fieldData[$typeId]) && $mapping['gpmm_type_sub_id'] == null) {
                $itemData = $fieldData[$typeId];
            }*/

            if ($mapping['gpmm_custom_mapping']) {
                $customMapping = json_decode($mapping['gpmm_custom_mapping'], true);
                if (isset($customMapping[$itemData])) {
                    $itemData = $customMapping[$itemData];
                }
            }

            if ($mapping['gpmm_required'] && $itemData === null) {
                throw new DataCollectionMissingDataException('Required data not found in track');
            }

            $data[$mapping['gpmm_variable_name']] = $itemData;
        }

        //$data = $this->changeToJsonDates($data);
        return $data;
    }

    /**
     * @param $trackId
     * @return \Gems\Tracker\\Engine\\FieldsDefinition
     * @throws \Zalt\Loader\Exception\LoadException
     */
    protected function getFieldsDefinitions($trackId)
    {
        return $this->loader->create('Tracker\\Engine\\FieldsDefinition', $trackId);
    }

    public function getAppointment($appointmentData)
    {
        return $this->loader->create('Agenda_Appointment', $appointmentData);
    }

    public function setRespondent($patientNr, $organizationId)
    {
        $this->respondent = $this->loader->create('Tracker_Respondent', $patientNr, $organizationId);
    }
}