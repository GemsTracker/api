<?php

namespace Pulse\Api\Model\Transformer;

use Pulse\Api\Repository\TreatmentTypeRepository;

class TreatmentTrackFilterTransformer extends \MUtil_Model_ModelTransformerAbstract
{
    public function transformFilter(\MUtil_Model_ModelAbstract $model, array $filter)
    {
        $filter['gap_id_organization'] = TreatmentTypeRepository::$xwowOrganizations;
        return $filter;
    }
}