<?php

namespace Gems\Rest\Fhir\Model\Transformer;


class PatientIdTransformer extends \MUtil_Model_ModelTransformerAbstract
{
    public function transformFilter(\MUtil_Model_ModelAbstract $model, array $filter)
    {
        if (isset($filter['id'])) {
            $idParts = explode('@', $filter['id']);
            $filter['gr2o_patient_nr'] = $idParts[0];
            $filter['gr2o_id_organization'] = $idParts[1];
            unset($filter['id']);
        }

        return $filter;
    }
}
