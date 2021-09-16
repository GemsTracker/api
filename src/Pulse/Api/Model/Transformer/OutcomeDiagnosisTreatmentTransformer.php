<?php

namespace Pulse\Api\Model\Transformer;

class OutcomeDiagnosisTreatmentTransformer extends \MUtil_Model_ModelTransformerAbstract
{
    public function transformFilter(\MUtil_Model_ModelAbstract $model, array $filter)
    {
        $this->filter = $filter;
        if (isset($filter['pt2o_id_treatment'])) {
            if (!isset($filter['pt2o_id_diagnosis'])) {
                $filter[] = 'pt2o_id_diagnosis IS NULL';
            } else {

                $diagnosisId = $filter['pt2o_id_diagnosis'];
                unset($filter['pt2o_id_diagnosis']);
                $filter[] = [
                    'pt2o_id_diagnosis' => $diagnosisId,
                    'pt2o_id_diagnosis IS NULL',
                ];
            }
        }

        return $filter;
    }

    public function transformLoad(\MUtil_Model_ModelAbstract $model, array $data, $new = false, $isPostData = false)
    {
        // If both diagnosis and treatment are supplied, try to find records with that combination and return only those,
        // otherwise fall back to the data found with only treatment
        if (isset($this->filter['pt2o_id_diagnosis'], $this->filter['pt2o_id_treatment'])) {
            $onlyCombinations = [];
            foreach($data as $row) {
                if (isset($row['pt2o_id_diagnosis'], $row['pt2o_id_treatment'])) {
                    $onlyCombinations[] = $row;
                }
            }

            if (count($onlyCombinations)) {
                return $onlyCombinations;
            }
        }

        return $data;
    }
}
