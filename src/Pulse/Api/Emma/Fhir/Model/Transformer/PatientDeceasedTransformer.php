<?php

declare(strict_types=1);


namespace Pulse\Api\Emma\Fhir\Model\Transformer;


class PatientDeceasedTransformer extends \MUtil_Model_ModelTransformerAbstract
{
    public function transformRowBeforeSave(\MUtil_Model_ModelAbstract $model, array $row)
    {
        if (isset($row['deceasedBoolean']) && $row['deceasedBoolean'] === true) {
            $row['gr2o_reception_code'] = 'deceased';
        }
        return $row;
    }
}
