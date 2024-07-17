<?php

namespace Pulse\Api\Model\Transformer;

use Pulse\Api\Repository\TreatmentTypeRepository;

class TreatmentAppointmentFilterTransformer extends \MUtil_Model_ModelTransformerAbstract
{
    public function transformFilter(\MUtil_Model_ModelAbstract $model, array $filter)
    {
        $filter[] = 'pa2t_id_treatment > 70';
        $filter['gap_id_organization'] = TreatmentTypeRepository::$treatmentAppointmentOrganizations;
        return $filter;
    }
}