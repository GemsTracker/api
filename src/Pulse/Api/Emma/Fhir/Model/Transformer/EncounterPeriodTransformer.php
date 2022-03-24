<?php

declare(strict_types=1);


namespace Pulse\Api\Emma\Fhir\Model\Transformer;


use Gems\Rest\Exception\MissingDataException;

/**
 * Translate Period
 */
class EncounterPeriodTransformer extends \MUtil_Model_ModelTransformerAbstract
{
    public function transformRowBeforeSave(\MUtil_Model_ModelAbstract $model, array $row)
    {
        if (!isset($row['period'], $row['period']['start'])) {
            throw new MissingDataException('No Encounter start period set');
        }

        $row['gap_admission_time'] = $row['period']['start'];
        if (isset($row['period']['end'])) {
            $row['gap_discharge_time'] = $row['period']['end'];
        }

        return $row;
    }
}
