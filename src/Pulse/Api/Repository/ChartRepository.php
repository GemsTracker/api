<?php


namespace Pulse\Api\Repository;


use MUtil\Translate\TranslateableTrait;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\ResultSet\ResultSet;
use Laminas\Db\Sql\Predicate\Predicate;
use Laminas\Db\Sql\Sql;

class ChartRepository
{

    use TranslateableTrait;

    /**
     * @var array list of chart data
     */
    protected $chartData;

    /**
     * @var Adapter
     */
    protected $db;

    /**
     * @var array list of translations of internal name and database name of the fields
     */
    public $dbFieldToNormField = array(
        'gno_field_1' => 'treatment',
        'gno_field_2' => 'caretaker',
        'gno_field_3' => 'location',
    );

    /**
     * @var bool should groups be hidden
     */
    protected $hideGroups;

    /**
     * @var \Zend_Locale
     */
    protected $locale;

    /**
     * @var string Name of the Outcome variable
     */
    protected $name;

    /**
     * @var string Code of the question
     */
    protected $questionCodes;

    /**
     * @var int Id of the survey
     */
    protected $surveyId;

    /**
     * @var \Gems_Tracker
     */
    protected $tracker;

    /**
     * @var \Zend_Translate_Adapter
     */
    protected $translateAdapter;

    /**
     * @var string internal name of the treatment field
     */
    protected $treatmentFieldName = 'treatment';

    /**
     * @var int treatment Id
     */
    protected $treatmentId;

    /**
     * @var string Graph type
     */
    protected $type;

    /**
     * @var array List of chart Ids and their used rounds
     */
    protected $usedRounds;

    public function __construct(Adapter $db, \Zend_Locale $locale, \Gems_Tracker $tracker, \Zend_Translate_Adapter $translateAdapter)
    {
        $this->db = $db;
        $this->locale = $locale;
        $this->tracker = $tracker;
        $this->translateAdapter = $translateAdapter;
    }

    /**
     * Add the base round data to each chart. This is mock data setting the rounds in their correct order
     * regardless of order of the added charts. This base round data will only be added if the respondent data
     * has rounds that or not in the base norm data
     *
     */
    protected function addBaseRoundsData()
    {
        if (!is_array($this->usedRounds)) {
            return;
        }
        ksort($this->usedRounds);
        $chartData = [
            'x' => array_values($this->usedRounds),
            'mode' => 'none',
            "showlegend" => false,
            "hoverinfo" => "none",
        ];


        array_unshift($this->chartData, $chartData);
    }

    /**
     * Translate norm data to Chart data and add it to the chartData parameter
     *
     * @param $norms array list of norm data from gems__norms
     * @param string $normType string name of the type of data from the Norm (e.g. physician data
     * @throws \Zend_Date_Exception
     */
    protected function addChartDataFromScores($norms, $normType='')
    {
        $firstNorm = reset($norms);
        if ($firstNorm['pt2o_graph'] == 'errorbar') {
            $errorData = $this->getErrorData($norms);
            if (isset($errorData['error_y'])) {
                $norms = $errorData['norms'];
                $errorY = $errorData['error_y'];
            }
        }

        $area = [];
        $order = [];
        $groups = [];

        $blankDescriptive = true;
        $nValues = [];
        $range = false;

        $normDate = false;

        $scores = [];
        foreach ($norms as $norm) {
            $descriptive = $norm['gno_descriptive'];
            $round = $norm['gro_round_description'];
            $scores[$descriptive][$round] = (float)$norm['gno_value'];

            if ($normType == 'base') {
                $this->usedRounds[$norm['gro_id_order']] = $norm['gro_round_description'];
            }

            $order[$norm['gno_order']] = $descriptive;

            $area[$descriptive] = false;
            if ($norm['gno_value'] != 0) {
                $blankDescriptive = false;
            }

            $nValues[$round] = $norm['gno_n'];

            if (isset($norm['gno_group']) && $norm['gno_group'] != 0) {
                $area[$descriptive] = $norm['gno_order'];
                $groups[$descriptive] = $norm['gno_group'];
            }

            if ($range === false && isset($norm['gno_range'])) {
                $range = explode('-', $norm['gno_range']);
                foreach ($range as $key => $value) {
                    $range[$key] = (int)$value;
                }
            }

            if (!$normDate && !empty($norm['gno_changed'])) {
                $normDate = new \MUtil_Date($norm['gno_changed'], 'yyyy-MM-dd HH:mm:ss');
                $this->calculatedOn = $normDate;
            }

            if (!empty($norm['gno_from'])) {
                $from = new \MUtil_Date($norm['gno_from'], 'yyyy-MM-dd');
                if ((isset($this->dateRange['from']) && $from->isEarlierOrEqual($this->dateRange['from'])) || !isset($this->dateRange['from'])) {
                    $this->dateRange['from'] = $from;
                }
            }
            if (!empty($norm['gno_until'])) {
                $until = new \MUtil_Date($norm['gno_until'], 'yyyy-MM-dd');
                if ((isset($this->dateRange['until']) && $until->isLaterOrEqual($this->dateRange['until'])) || !isset($this->dateRange['until'])) {
                    $this->dateRange['until'] = $until;
                }
            }
        }

        ksort($order);

        $descriptiveCount = 1;

        foreach ($order as $descriptive) {
            $descriptiveScores = $scores[$descriptive];
            if (!empty($normType)) {
                $descriptiveVariableNames[] = $variableName = $normType . '_descriptive_' . $descriptiveCount;
            } else {
                $descriptiveVariableNames[] = $variableName = 'descriptive_' . $descriptiveCount;
            }

            $descriptiveLabel = $this->getDescriptiveName($descriptive, $normType);

            if ($blankDescriptive) {
                return;
            }

            $descriptiveData = [
                'x' => array_keys($descriptiveScores),
                'y' => array_values($descriptiveScores),
                'name' => $descriptiveLabel,
                'type' => $this->type,
            ];

            if (isset($errorY)) {
                $descriptiveData['type'] = 'bar';
                $descriptiveData['error_y'] = $errorY;
            }

            //if (isset($groups[$descriptive]) && $groups[$descriptive] !== false) {
            if (!empty($normType)) {
                //$descriptiveData['legendgroup'] = $groups[$descriptive];
                $descriptiveData['legendgroup'] = $normType;
                if ($this->hideGroups) {
                    $descriptiveData['visible'] = 'legendonly';
                }
            }

            $descriptiveCount++;

            $descriptiveData = $this->addDescriptiveStyle($variableName, $descriptiveData, $normType, $area[$descriptive], false, $this->type);
            $this->chartData[] = $descriptiveData;
        }

        $nLabels = [];
        $nYValues = [];

        $nName = 'n';
        $nYValue = 7.5;

        if ($range) {
            $totalRange = $range[1] - $range[0];
            $nYValue = ($totalRange * .075) + $range[0];
        }

        if (!empty($normType)) {
            $nName = $normType . '_' . $nName;
            $nYValue = 100 - $nYValue;

            if ($range) {
                $nYValue = $range[1] - ($totalRange * .075);
            }
        }

        foreach ($nValues as $roundDescription => $nValue) {
            $nLabels[$roundDescription] = 'N = ' . $nValue;
            $nYValues[] = $nYValue;
        }

        $this->range = $range;

        $nData = [
            'x' => array_keys($nLabels),
            'y' => $nYValues,
            'mode' => 'text',
            'text' => array_values($nLabels),
            'textposition' => 'bottom',
            'showlegend' => false,
            'hoverinfo' => 'none',
            'legendgroup' => 'n-data',
        ];

        $nData = $this->addDescriptiveStyle($nName, $nData, $normType, false, true);
        $this->chartData[] = $nData;
    }

    /**
     * Get the norm data without any other fields applied but treatment
     *
     * @param $outcomeVariableId int the ID of the outcome variable
     * @return array list of Database norm values from gems__norms
     */
    protected function getBaseNormData($outcomeVariableId)
    {
        //$outcomeVariable = $this->getOutcomeVariable($outcomeVariableId);
        $normFieldToDbField = array_flip($this->dbFieldToNormField);

        $sql = new Sql($this->db);
        $select = $sql->select();

        $select->from('gems__norms')
            ->join('pulse__treatment2outcomevariable',
                'gno_survey_id = pt2o_id_survey AND gno_answer_code = pt2o_question_code AND gno_field_1 = pt2o_id_treatment')
            ->join('gems__rounds',
                'gems__norms.gno_round_id = gems__rounds.gro_id_round AND pulse__treatment2outcomevariable.pt2o_id_track = gems__rounds.gro_id_track')
            ->where(['pt2o_id' => $outcomeVariableId])
            ->order('gro_id_order');

        $select->where->notEqualTo('gro_id_round', 0);
        $select->where->notEqualTo('gro_round_description', 'Stand-alone survey');
        //->where(['pt2o_id' => $outcomeVariableId]);

        foreach($normFieldToDbField as $normFieldName=>$dbFieldName) {
            if ($normFieldName == 'treatment') {
                continue;
            }
            $select->where([$dbFieldName => null]);
        }

        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();
        $resultSet = new ResultSet();
        $resultSet->initialize($result);

        $data = $resultSet->toArray();

        return $data;
    }

    /**
     * Get data from a chart for a specific outcome variable
     *
     * @param $outcomeVariableId int Outcome variable
     * @param $respondentTrackId string optional RespondentTrackId for if you want to add RespondentData
     * @throws \Zend_Date_Exception
     */
    public function getChart($outcomeVariableId, $respondentTrackId = null, $hideGroups = false)
    {
        $showLayout = true;
        $this->chartData = [];
        $data = $this->getBaseNormData($outcomeVariableId);
        $this->hideGroups = $hideGroups;

        if (!empty($data)) {
            $firstRow = reset($data);
            $this->setInitialValues($firstRow);
        }

        $this->addChartDataFromScores($data, 'base');

        if ($respondentTrackId && $respondentData = $this->getRespondentData($respondentTrackId)) {
            $this->addChartDataFromScores($respondentData);
        }

        $this->addBaseRoundsData();

        $chartData = [
            'data' => $this->chartData,
        ];

        if ($showLayout) {
            $chartData['layout'] = $this->getChartLayouts();
        }
        return $chartData;
    }

    protected function getChartLayouts()
    {
        if ($this->surveyId && $this->questionCodes && $this->treatmentId) {
            $treatmentName = $this->getTreatmentName($this->treatmentId);
            $title = $treatmentName . ': ' . $title = $this->name;
            if (empty($this->name) && ($surveyName = $this->getSurvey($this->surveyId))) {
                $title = $treatmentName . ': ' . $surveyName . ': ' . join(' & ', $this->questionCodes);
            }

            $chartLayout = [
                'title' => $title,
                'titlefont' => [
                    'size' => 16
                ],
                'xaxis' => [
                    'title' => 'Tijd',
                ],
                'yaxis' => [
                    'title' => 'Waarde',
                ],
                'hovermode' => 'closest',
            ];

            if (isset($this->calculatedOn) && isset($this->dateRange) && isset($this->dateRange['from']) && isset($this->dateRange['until'])) {
                $chartLayout['annotations'] = array(
                    array(
                        'text' => $this->_('Data range: ') .
                            $this->dateRange['from']->toString('yyyy-MM-dd') .
                            ' - ' .
                            $this->dateRange['until']->toString('yyyy-MM-dd') .
                            '<br>' .
                            $this->_('Calculated on: ') .
                            $this->calculatedOn->toString('yyyy-MM-dd'),
                        'x' => 1.05,
                        'y' => 1.10,
                        'showarrow' => false,
                        'xref' => 'paper',
                        'yref' => 'paper',
                        'align' => 'left'
                    ),
                );
            }

            if (isset($this->range)) {
                $chartLayout['yaxis']['range'] = $this->range;
                $chartLayout['yaxis']['title'] .= "<br>" . join('-', $this->range);
            }
            return $chartLayout;
        }
        return null;
    }

    protected function getDescriptiveName($descriptiveLabel, $normType)
    {
        $name = $descriptiveLabel;

        /*if ($normType == 'physician') {
            $physicianName = $this->respondentResults->getPhysicianName($this->physicianId);
            $name = $physicianName . ' ' . $descriptiveLabel;
        } elseif ($normType == 'location') {
            $locationName = $this->respondentResults->getLocationName($this->locationId);
            $name = $locationName . ' ' . $descriptiveLabel;
        }*/

        return $name;
    }

    /**
     * Get the error data for error bars depending on the range of scores
     *
     * @param $norms array norm scores from the database
     * @return array
     */
    protected function getErrorData($norms)
    {
        $sortedData = [];
        $baseNorms = [];

        foreach($norms as $norm) {
            $order = $norm['gno_order'];
            $roundOrder = $norm['gro_id_order'];
            $sortedData[$roundOrder][$order] = $norm['gno_value'];
            if ($order == 0) {
                $baseNorms[] = $norm;
            }
        }

        ksort($sortedData);

        $correctLower = [];
        $correctUpper = [];

        foreach($sortedData as $roundOrder=>$values) {
            if (array_key_exists(0, $values)) {
                $mainValue = $values[0];

                if (array_key_exists(1, $values)) {
                    $correctLower[] = $mainValue - $values[1];
                }

                if (array_key_exists(2, $values)) {
                    $correctUpper[] = $values[2] - $mainValue;
                }
            }

        }

        $errorData = [
            'norms' => $baseNorms
        ];

        if (!empty($correctLower) && !empty($correctUpper)) {
            $errorData['error_y'] = [
                'type' => 'data',
                'symmetric' => false,
                'array' => $correctUpper,
                'arrayminus' => $correctLower,
            ];
        }

        return $errorData;
    }

    public function getOutcomeVariable($outcomeVariableId)
    {
        $sql = new Sql($this->db);
        $select = $sql->select();

        $select->from('pulse__treatment2outcomevariable')
            ->where(['pt2o_id' => $outcomeVariableId]);

        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();

        $firstRow = $result->current();

        return $firstRow;
    }

    public function getPatientNumber($respondentTrackId)
    {
        $sql = new Sql($this->db);

        $respondentSelect = $sql->select();
        $respondentSelect
            ->from('gems__respondent2org', 'gr2o_patient_nr')
            ->join('gems__respondent2track', 'gr2t_id_user = gr2o_id_user AND gr2t_id_organization = gr2o_id_organization')
            ->where(['gr2t_id_respondent_track' => $respondentTrackId]);

        $statement = $sql->prepareStatementForSqlObject($respondentSelect);
        $result = $statement->execute();
        $firstRow = $result->current();

        return $firstRow['gr2o_patient_nr'];
    }

    protected function getRespondentData($respondentTrackId)
    {
        if ($respondentScores = $this->getRespondentScores($respondentTrackId)) {
            //\MUtil_Echo::track($respondentScores);

            $patientNumber = $this->getPatientNumber($respondentTrackId);

            if ($this->type == 'errorbar') {
                $this->type = 'bar';
            }

            $key=0;

            foreach($respondentScores as $questionCode=>$questionScores) {

                $variableName = 'respondent';
                if ($key > 0) {
                    $variableName .= $key;
                }

                $legendName = $patientNumber;
                if (!empty($this->respondentLabels)) {
                    //$legendName .= ' - ' . $outcomeVariable['pt2o_respondent_labels'];
                    $labels = explode('|', $this->respondentLabels);
                    if (isset($labels[$key])) {
                        $legendName .= ' - ' . $labels[$key];
                    }
                }

                $respondentData = array(
                    'x' => array_keys($questionScores),
                    'y' => array_values($questionScores),
                    'type' => $this->type,
                    'name' => $legendName,
                    'legendgroup' => 'respondent',
                );

                $respondentData = $this->addDescriptiveStyle(
                    $variableName,
                    $respondentData,
                    $variableName,
                    false,
                    false,
                    $this->type);

                $this->chartData[] = $respondentData;
                $key++;
            }
        }
    }

    protected function getRespondentScores($respondentTrackId)
    {
        if ($this->questionCodes && $this->surveyId && $this->trackId) {

            $sql = new Sql($this->db);
            $tokenSelect = $sql->select();
            $tokenSelect
                ->from('gems__tokens')
                ->where(
                    [
                        'gto_id_respondent_track' => $respondentTrackId,
                        'gto_id_track' => $this->trackId,
                        'gto_id_survey' => $this->surveyId,
                    ])
                ->order('gto_round_order')
                ->where->notEqualTo('gto_round_description', 'Stand-alone survey');

            $statement = $sql->prepareStatementForSqlObject($tokenSelect);
            $result = $statement->execute();

            $tokens = iterator_to_array($result);

            $survey = $this->tracker->getSurvey($this->surveyId);

            $language = $this->locale->getLanguage();
            $questionInformation = $survey->getQuestionInformation($language);

            $respondentScores = [];

            $addedRounds = false;

            foreach($tokens as $currentToken) {
                $token = $this->tracker->getToken($currentToken['gto_id_token']);
                $tokenAnswers = $token->getRawAnswers();
                foreach($this->questionCodes as $questionCode) {
                    if (isset($tokenAnswers[$questionCode])) {
                        $answer = $tokenAnswers[$questionCode];
                        if (isset($questionInformation[$questionCode], $questionInformation[$questionCode]['answers'])
                            && is_array($questionInformation[$questionCode]['answers'])
                            && isset($questionInformation[$questionCode]['answers'][$answer])
                        ) {
                            $answer = $questionInformation[$questionCode]['answers'][$answer];
                        }

                        if (is_numeric($answer)) {
                            $respondentScores[$questionCode][$currentToken['gto_round_description']] = $answer;
                            if (!array_key_exists($currentToken['gto_round_order'], $this->usedRounds)) {
                                $this->usedRounds[$currentToken['gto_round_order']] = $currentToken['gto_round_description'];
                                $addedRounds = true;
                            }
                        }
                    }
                }
            }

            if (!$addedRounds) {
                $this->usedRounds = null;
            }

            return $respondentScores;
        }
        return false;
    }

    public function getTreatmentName($treatmentId)
    {
        $sql = new Sql($this->db);
        $select = $sql->select();
        $select->from('pulse__treatments', ['ptr_name'])
            ->where([
                'ptr_id_treatment' => $treatmentId,
                'ptr_active' => 1
            ]);

        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();

        $row = $result->current();

        if ($row && $row['ptr_name']) {
            return $row['ptr_name'];
        }

        return null;
    }

    protected function addDescriptiveStyle($variableName, $data, $normType, $area=false, $nValue=false, $type='scatter')
    {
        switch ($normType) {
            case 'physician':
            case 'location':
                $lineColor = 'rgb(84,199,0)';
                $fillColor = 'rgba(84,199,0,.2)';
                $barColor = 'rgba(84,199,0,.75)';
                break;

            case 'respondent':
                $lineColor = 'rgb(215,0,76)';
                $barColor = 'rgba(215,0,76,.75)';
                break;

            case 'respondent1':
                $lineColor = 'rgb(255,217,102)';
                $barColor = 'rgba(255,217,102,.75)';
                break;

            default:
                $lineColor = 'rgb(31,119,180)';
                $fillColor = 'rgba(31,119,180,.4)';
                $barColor = 'rgba(31,119,180,.75)';
        }

        if (($type == 'bar' || $type == 'errorbar') && isset($barColor)) {
            $data['marker'] = array('color' => $barColor);
        }

        if ($area !== false) {
            switch ($area) {

                case 2:

                    $this->order[99][] = $variableName;

                    if (isset($fillColor)) {
                        $data['fillcolor'] = $fillColor;
                        $data['marker'] = array('color' => $fillColor);
                    }
                    $data['fill'] = 'tonexty';
                    $data['line'] = array('width' => 0);
                    $data['showlegend'] = false;
                    $data['mode'] = 'lines';

                    if (count($data['x']) == 1) {
                        $data['mode'] = 'markers';
                        $data['marker'] = array(
                            'color' => $fillColor,
                            'size' => 8,
                            'symbol' => 'diamond',
                        );
                        unset($data['fill']);
                    }

                    break;
                case 1:

                    $this->order[99][] = $variableName;

                    if (isset($fillColor)) {
                        $data['marker'] = array('color' => $fillColor);
                    }
                    $data['line'] = array('width' => 0);
                    $data['showlegend'] = false;
                    $data['mode'] = 'lines';

                    if (count($data['x']) == 1) {
                        $data['mode'] = 'markers';
                        $data['marker'] = array(
                            'color' => $fillColor,
                            'size' => 8,
                            'symbol' => 'diamond',
                        );
                    }

                    break;
            }
        } elseif ($nValue==true) {
            $this->order[5][] = $variableName;
            $data['textfont']['color'] = $lineColor;
        } else {

            $this->order[10][] = $variableName;
            if(isset($lineColor)) {
                $data['line'] = array('color' => $lineColor);
            }
        }
        return $data;
    }

    /**
     * @param $row array a row of chart data with pulse__treatment2outcomevariable data
     */
    protected function setInitialValues($row)
    {
        $this->name = $row['pt2o_name'];
        $this->questionCodes = explode('|', $row['pt2o_question_code']);
        $this->respondentLabels = $row['pt2o_respondent_labels'];
        $this->surveyId = $row['pt2o_id_survey'];
        $this->trackId = $row['pt2o_id_track'];
        $this->treatmentId = $row['pt2o_id_treatment'];
        $this->type = $row['pt2o_graph'];
    }
}
