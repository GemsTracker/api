<?php

namespace Pulse\Api\Model\Transformer;

class DigitalClinicAccountTransformer extends \MUtil_Model_ModelTransformerAbstract
{
    public function transformFilter(\MUtil_Model_ModelAbstract $model, array $filter)
    {
        $filter['pag_api_client_name'] = 'digital-clinic';
        return $filter;
    }
}