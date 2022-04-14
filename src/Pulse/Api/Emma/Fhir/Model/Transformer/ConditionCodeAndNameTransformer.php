<?php

declare(strict_types=1);


namespace Pulse\Api\Emma\Fhir\Model\Transformer;


/**
 * Condition code and name translations
 */
class ConditionCodeAndNameTransformer extends \MUtil_Model_ModelTransformerAbstract
{
    public function transformRowBeforeSave(\MUtil_Model_ModelAbstract $model, array $row)
    {
        $row['gmco_code'] = null;
        $row['gmco_name'] = null;
        if (isset($row['code'], $row['code']['coding'], $row['code']['coding']) && is_array($row['code']['coding'])) {
            foreach($row['code']['coding'] as $code) {
                if (isset($code['code'])) {
                    $row['gmco_code'] = $code['code'];
                    if (isset($code['display'])) {
                        $row['gmco_name'] = $code['display'];
                    }
                }
            }

        }
        return $row;
    }
}
