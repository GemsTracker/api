<?php

namespace Pulse\Api\Fhir\Model\Transformer;


class CarePlanInfoTransformer extends \Gems\Rest\Fhir\Model\Transformer\CarePlanInfoTransformer
{
    protected function getDiagnosisName($caretakerId)
    {
        $model = new \MUtil_Model_TableModel('gems__diagnosis2track');
        $result = $model->loadFirst(['gdt_id_diagnosis' => $caretakerId]);
        if ($result) {
            return $result['gdt_diagnosis_name'];
        }
        return null;
    }

    protected function getDisplayValue($trackFieldInfo)
    {
        switch ($trackFieldInfo['gtf_field_type']) {
            case 'caretaker':
                return $this->getCaretakerName($trackFieldInfo['gr2t2f_value']);
            case 'treatment':
                return $this->getTreatmentName($trackFieldInfo['gr2t2f_value']);
            default:
                return parent::getDisplayValue($trackFieldInfo);
        }
    }

    protected function getTreatmentName($locationId)
    {
        $model = new \MUtil_Model_TableModel('gems__treatments');
        $result = $model->loadFirst(['gtrt_id_treatment' => $locationId]);
        if ($result) {
            return $result['gtrt_name'];
        }
        return null;
    }
}
