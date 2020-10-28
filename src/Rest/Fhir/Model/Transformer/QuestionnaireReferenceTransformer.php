<?php

namespace Gems\Rest\Fhir\Model\Transformer;


use Gems\Rest\Fhir\Endpoints;

class QuestionnaireReferenceTransformer extends \MUtil_Model_ModelTransformerAbstract
{
    protected $dbField = 'gsu_id_survey';

    protected $field = 'questionnaire';

    public function __construct($field = 'questionnaire', $dbField = 'gsu_id_survey')
    {
        $this->dbField = $dbField;
        $this->field = $field;
    }

    public function transformFilter(\MUtil_Model_ModelAbstract $model, array $filter)
    {
        if (isset($filter['survey'])) {
            $filter[$this->dbField] = $filter['survey'];
            unset($filter['survey']);
        }

        if (isset($filter[$this->field])) {
            $filter[$this->dbField] = $filter[$this->field];
            unset($filter[$this->field]);
        }

        if (isset($filter['questionnaire'])) {
            $filter[$this->dbField] = $filter['questionnaire'];
            unset($filter['questionnaire']);
        }

        if (isset($filter['survey_name'])) {
            $filter[] = 'gsu_survey_name LIKE %' . $filter['survey_name'] . '%';
            unset($filter['survey_name']);
        }

        if (isset($filter['questionnaire_name'])) {
            $filter[] = 'gsu_survey_name LIKE %' . $filter['questionnaire_name'] . '%';
            unset($filter['questionnaire_name']);
        }

        return $filter;
    }

    public function transformLoad(\MUtil_Model_ModelAbstract $model, array $data, $new = false, $isPostData = false)
    {
        foreach($data as $key=>$row) {
            if (isset($row[$this->dbField])) {
                $questionnaireReference = [
                    'id' => $row[$this->dbField],
                    'reference' => Endpoints::QUESTIONNAIRE . $row[$this->dbField],
                ];

                if (isset($row['gsu_survey_name'])) {
                    $questionnaireReference['display'] = $row['gsu_survey_name'];
                }

                $data[$key][$this->field] = $questionnaireReference;
            }
        }
        return $data;
    }
}
