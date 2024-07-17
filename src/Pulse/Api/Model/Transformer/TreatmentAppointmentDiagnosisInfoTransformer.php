<?php

namespace Pulse\Api\Model\Transformer;

use Ichom\Repository\Diagnosis2TreatmentRepository;

class TreatmentAppointmentDiagnosisInfoTransformer extends \MUtil_Model_ModelTransformerAbstract
{
    protected $loadEmptyDiagnosis = false;

    public function transformFilter(\MUtil_Model_ModelAbstract $model, array $filter)
    {
        if (isset($filter['with-diagnosis'])) {
            $this->loadEmptyDiagnosis = true;
            unset($filter['with-diagnosis']);
        }

        return $filter;
    }

    public function transformLoad(\MUtil_Model_ModelAbstract $model, array $data, $new = false, $isPostData = false)
    {
        if ($this->loadEmptyDiagnosis) {
            foreach($data as $key => $row) {
                $data[$key]['diagnosis'] = null;
            }
        }
        return $data;
    }
}