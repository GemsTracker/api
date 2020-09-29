<?php

namespace Gems\Rest\Fhir\Model\Transformer;


class QuestionnaireTaskExecutionPeriodTransformer extends \MUtil_Model_ModelTransformerAbstract
{
    public function transformLoad(\MUtil_Model_ModelAbstract $model, array $data, $new = false, $isPostData = false)
    {
        foreach ($data as $key => $row) {
            $validFrom = null;
            if ($row['gto_valid_from'] && !($row['gto_valid_from'] instanceof \MUtil_Date)) {
                $validFrom = new \MUtil_Date($row['gto_valid_from']);
            }

            $data[$key]['executionPeriod']['start'] = null;
            if ($validFrom instanceof \MUtil_Date) {
                $data[$key]['executionPeriod']['start'] = $validFrom->toString(\MUtil_Date::ISO_8601);
            }

            $validUntil = null;
            if ($row['gto_valid_until'] && !($row['gto_valid_until'] instanceof \MUtil_Date)) {
                $validUntil = new \MUtil_Date($row['gto_valid_until']);
            }

            $data[$key]['executionPeriod']['end'] = null;
            if ($validUntil instanceof \MUtil_Date) {
                $data[$key]['executionPeriod']['end'] = $validUntil->toString(\MUtil_Date::ISO_8601);
            }
        }
        return $data;
    }
}
