<?php

namespace Pulse\Api\Emma\Fhir\Model\Transformer;

/**
 * Translate Encounter status
 */
class EncounterStatusTransformer extends \MUtil_Model_ModelTransformerAbstract
{
    protected $statusTranslations = [
        'planned' => 'AC', // Active
        'arrived' => 'AC',
        'triaged' => 'AC',
        'in-progress' => 'AC',
        'onleave' => 'AC',

        'finished' => 'CO', // Completed
        'entered-in-error', // Aborted
        'cancelled' => 'CA', // Cancelled

        'unknown' => 'AC',
    ];

    public function transformRowBeforeSave(\MUtil_Model_ModelAbstract $model, array $row): array
    {
        if (isset($row['status'])) {
            if (isset($this->statusTranslations[$row['status']])) {
                $row['gap_status'] = $this->statusTranslations[$row['status']];
            }
        }

        return $row;
    }
}
