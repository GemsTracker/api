<?php

namespace Gems\Rest\Fhir\Model\Transformer;


class QuestionnaireSubjectTypeTransformer extends \MUtil_Model_ModelTransformerAbstract
{
    public function transformFilter(\MUtil_Model_ModelAbstract $model, array $filter)
    {
        if (isset($filter['subjectType'])) {
            switch(strtolower($filter['subjectType'])) {
                case 'patient':
                    $filter['ggp_respondent_members'] = 1;
                    break;
                case 'practitioner':
                    $filter['ggp_staff_members'] = 1;
                    break;
            }
        }

        return $filter;
    }

    public function transformLoad(\MUtil_Model_ModelAbstract $model, array $data, $new = false, $isPostData = false)
    {
        foreach($data as $key=>$row) {
            if (isset($row['ggp_respondent_members']) && $row['ggp_respondent_members'] == 1) {
                $data[$key]['subjectType'] = ['Patient'];
            }
            if (isset($row['ggp_staff_members']) && $row['ggp_staff_members'] == 1) {
                $data[$key]['subjectType'] = ['Practitioner'];
            }
        }

        return $data;
    }
}
