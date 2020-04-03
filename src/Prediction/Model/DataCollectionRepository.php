<?php


namespace Prediction\Model;


use Gems\Rest\Repository\SurveyQuestionsRepository;
use Gems\Tracker\Field\AppointmentField;
use Zalt\Loader\ProjectOverloader;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\ResultSet\ResultSet;
use Laminas\Db\Sql\Sql;

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

    /**
     * @var int RespondentTrackId
     */
    protected $respondentTrackId;

    /**
     * @var SurveyQuestionsRepository
     */
    protected $surveyQuestionsRepository;

    /**
     * @var \Gems_Tracker
     */
    protected $tracker;


    public function __construct(Adapter $db, ProjectOverloader $loader,  SurveyQuestionsRepository $surveyQuestionsRepository,\Gems_Tracker $tracker)
    {
        $this->db = $db;
        $this->loader = $loader;
        $this->surveyQuestionsRepository = $surveyQuestionsRepository;
        $this->tracker = $tracker;

    }

    protected function getCalculatedDate($rawDate, $calculationCommand, \Gems_Tracker_Token $token)
    {
        $commands = explode(':', $calculationCommand);
        $trackfields = $token->getRespondentTrack()->getFieldData();

        if (count($commands) > 1) {
            $timeSetting = $commands[0];
            $calculateWith = $commands[1];
            if (isset($commands[2])) {
                $calculateWithValue = $commands[2];
            }

            if ($timeSetting == 'null' || $calculateWith == 'null') {
                return null;
            }

            switch($calculateWith) {
                case 'survey-valid-from':
                    $zeroDate = $token->getValidFrom();
                    break;
                case 'survey-valid-until':
                    $zeroDate = $token->getValidUntil();
                    break;
                case 'survey-completion':
                    $zeroDate = $token->getCompletionTime();break;
                case 'track-start':
                    $zeroDate = $token->getRespondentTrack()->getStartDate();
                    break;
                case 'track-end':
                    $zeroDate = $token->getRespondentTrack()->getEndDate();
                    break;
                case 'field':
                    if (isset($calculateWithValue) && array_key_exists($calculateWithValue, $trackfields)) {
                        $zeroDate = $trackfields[$calculateWithValue];
                    }
                    break;
            }

            if (isset($zeroDate, $rawDate)) {
                if ($zeroDate instanceof \MUtil_Date) {
                    $zeroDate = $zeroDate->getDateTime();
                } else {
                    $zeroDate = new \DateTime($zeroDate);
                }
                if ($rawDate instanceof \MUtil_Date) {
                    $rawDate = $rawDate->getDateTime();
                } else {
                    $rawDate = new \DateTime($rawDate);
                }

                if ($rawDate instanceof \DateTime && $zeroDate instanceof \DateTime) {

                    switch($timeSetting) {
                        case 'days since':
                            $interval = $zeroDate->diff($rawDate);
                            return $interval->days;
                        default:
                            return $rawDate;
                    }
                }
            }
        }
        return null;
    }

    protected function getDataFromTypes($predictionTypes)
    {
        $respondentData = [];
        $trackfieldData = [];
        $surveyData = [];
        $fixedData = [];

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
        if (isset($predictionTypes['respondent'])) {
            $respondentData = $this->getRespondentData($predictionTypes['respondent']);
        }

        if (isset($predictionTypes['trackfield'])) {
            $trackfieldData = $this->getRespondentTrackfieldData($predictionTypes['trackfield']);
        }

        if (isset($predictionTypes['survey'])) {
            $surveyData = $this->getRespondentSurveyData($predictionTypes['survey']);
        }

        if (isset($predictionTypes['static'])) {
            foreach ($predictionTypes['static'] as $mapping) {
                $fixedData[$mapping['gpmm_name']] = $mapping['gpmm_type_id'];
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
            ->join('gems__prediction_models', 'gpm_id_track = gr2t_id_track', ['gpm_id', 'gpm_name', 'gpm_source_id'])
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
                'modelId' => $predictionChartData['gpm_source_id'],
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
        if (array_key_exists('mappings', $predictionMappings) && is_array($predictionMappings['mappings'])) {
            foreach ($predictionMappings['mappings'] as $predictionMapping) {
                $type = $predictionMapping['gpmm_type'];
                $predictionTypes[$type][] = $predictionMapping;
            }
        }

        return $predictionTypes;
    }

    protected function getPredictionMappings($predictionId)
    {
        $model = new PredictionModelsWithMappingModel();
        $filter = [
            'gpm_source_id' => $predictionId,
        ];

        return $model->loadFirst($filter);

        /*$sql = new Sql($this->db);
        $select = $sql->select();
        $select->from('gems__prediction_model_mapping')
            ->join('gems__prediction_models', 'gpm_id = gpmm_prediction_model_id', [])
            ->where(['gpm_source_id' => $predictionId]);
        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();
        $resultSet = new ResultSet();
        $resultSet->initialize($result);

        return $resultSet->toArray();*/
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

            $data[$mapping['gpmm_name']] = $itemData;
        }

        //$data = $this->changeToJsonDates($data);

        return $data;
    }

    protected function getRespondentSurveyData($mappings)
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
            'gto_round_order',
            'gto_id_survey',
        ];

        $tokenModel->getItemCount();
        $tokenModel->getSelect();

        $tokens = $tokenModel->load($filter, $sort);

        $surveyInformation = [];
        foreach($surveys as $surveyId) {
            $surveyInformation[$surveyId] = $this->surveyQuestionsRepository->getSurveyListAndAnswers($surveyId, true);
        }

        $incompleteRounds = [];
        $repeatValues = [];

        foreach($tokens as $tokenData) {
            $token = $this->tracker->getToken($tokenData);
            $surveyId = $tokenData['gto_id_survey'];
            $answers = $token->getRawAnswers();
            foreach($mappingsPerSurvey[$surveyId] as $mapping) {
                $questionCode = $mapping['gpmm_type_sub_id'];
                if ($questionCode == "{{completion_time}}") {
                    $data[$tokenData['gto_round_description']][$mapping['gpmm_name']] = $token->getCompletionTime();
                }

                // Possibly Temporary calculation of time in days. Might be cut and put into R
                if ($questionCode == "{{time_in_days}}") {
                    $trackfieldData = $token->getRespondentTrack()->getFieldData();
                    if (isset($trackfieldData[0])) {
                        $zero = $trackfieldData[0];
                    } else {
                        $zero = $token->getRespondentTrack()->getStartDate();
                    }

                    $end = $token->getCompletionTime();
                    $data[$tokenData['gto_round_description']][$mapping['gpmm_name']] = $end->diffDays($zero);
                }

                // Possibly temporary filter of the SIDE trackfield in a survey variable. For MHQ questionair.
                // Might be solved in Limesurvey
                if (strpos($questionCode, '{{side}}') !== false) {
                    $trackfieldData = $token->getRespondentTrack()->getFieldData();
                    if  (isset($trackfieldData['side'])) {
                        $side = strtolower($trackfieldData['side']);
                        $questionCode = str_replace('{{side}}', $side, $questionCode);
                    }
                }

                if (array_key_exists($questionCode, $answers)) {
                    $itemData = $answers[$questionCode];
                    if (is_numeric($itemData) && is_string($itemData)) {
                        $itemData = (float)$itemData;
                    }
                    if ($mapping['gpmm_custom_mapping']) {
                        $questionType = $surveyInformation[$surveyId][$questionCode]['type'];

                        if ($questionType == 'date') {
                            $itemData = $this->getCalculatedDate($itemData, $mapping['gpmm_custom_mapping'], $token);
                        } else {
                            $customMapping = $mapping['gpmm_custom_mapping'];
                            if (isset($customMapping[$itemData])) {
                                $itemData = $customMapping[$itemData];
                            }
                        }
                    }

                    if ($mapping['gpmm_repeat']) {
                        $repeatValues[$mapping['gpmm_name']][$tokenData['gto_round_description']] = $mapping['gpmm_repeat'];
                    }

                    $data[$tokenData['gto_round_description']][$mapping['gpmm_name']] = $itemData;
                }
            }
            // Add completion time per survey on survey code field. Disabled for now in favor of {{completion_time}} sub ID
            /*$surveyColumnName = $token->getSurvey()->getCode();
            $data[$tokenData['gto_round_description']][$surveyColumnName] = $tokenData['gto_completion_time'];*/
        }

        foreach($repeatValues as $variableName=>$values) {

            $currentValue = reset($values);
            foreach($data as $roundDescription=>$roundData) {
                if (array_key_exists($roundDescription, $values)) {
                    $currentValue = $values[$roundDescription];
                }

                if (!array_key_exists($variableName, $roundData) || $roundData[$variableName] === null) {
                    $data[$roundDescription][$variableName] = $currentValue;
                }
            }
        }

        foreach($mappings as $mapping) {
            if ($mapping['gpmm_required']) {
                foreach($data as $roundDescription=>$roundData) {
                    if (!array_key_exists($mapping['gpmm_name'], $roundData) || $roundData[$mapping['gpmm_name']] === null) {
                        $incompleteRounds[$roundDescription] = true;
                    }
                }
            }
        }

        if (count($data) === count($incompleteRounds)) {
            throw new DataCollectionMissingDataException('Required data not found in any rounds');
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

            $data[$mapping['gpmm_name']] = $itemData;
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
