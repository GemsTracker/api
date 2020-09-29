<?php

namespace Gems\Rest\Fhir\Model\Transformer;


use Gems\Rest\Fhir\Endpoints;

class QuestionnaireTaskFocusTransformer extends \MUtil_Model_ModelTransformerAbstract
{
    public function transformFilter(\MUtil_Model_ModelAbstract $model, array $filter)
    {
        if (isset($filter['survey'])) {
            $filter['gto_id_survey'] = $filter['survey'];
        }

        if (isset($filter['survey.name'])) {
            $filter[] = 'gsu_survey_name LIKE %' . $filter['survey.name'] . '%';
        }

        return $filter;
    }

    public function transformLoad(\MUtil_Model_ModelAbstract $model, array $data, $new = false, $isPostData = false)
    {
        foreach($data as $key=>$row) {
            if (isset($row['gto_id_survey'])) {
                $focus = [
                    'id' => $row['gto_id_survey'],
                    'reference' => Endpoints::QUESTIONNAIRE . $row['gto_id_survey'],
                ];

                if (isset($row['gsu_survey_name'])) {
                    $focus['display'] = $row['gsu_survey_name'];
                }

                $data[$key]['focus'] = $focus;
            }
        }
        return $data;
    }
}
