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
            $value = "%".$filter['survey_name'].'%';
            if ($model instanceof \MUtil_Model_DatabaseModelAbstract) {
                $adapter = $model->getAdapter();
                $value = $adapter->quote($value);
                $filter[] = 'gsu_survey_name LIKE ' . $value;
            }

            unset($filter['survey_name']);
        }

        if (isset($filter['questionnaire_name'])) {
            $value = '%'.$filter['questionnaire_name'].'%';
            if ($model instanceof \MUtil_Model_DatabaseModelAbstract) {
                $adapter = $model->getAdapter();
                $value = $adapter->quote($value);
                $filter[] = 'gsu_survey_name LIKE ' . $value;
            }

            unset($filter['questionnaire_name']);
        }

        if (isset($filter['survey_code'])) {
            $filter['gsu_code'] = $filter['survey_code'];
            unset($filter['survey_code']);
        }
        if (isset($filter['questionnaire_code'])) {
            $filter['gsu_code'] = $filter['questionnaire_code'];
            unset($filter['questionnaire_code']);
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
                if (isset($row['gsu_code'])) {
                    $questionnaireReference['code'] = $row['gsu_code'];
                }

                $data[$key][$this->field] = $questionnaireReference;
            }
        }
        return $data;
    }
}
