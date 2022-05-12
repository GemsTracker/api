<?php

namespace Pulse\Api\Emma\Fhir\Model\Transformer;

/**
 * translate status
 */
class AppointmentStatusTransformer extends \MUtil_Model_ModelTransformerAbstract
{
    protected $statusTranslations = [
        'booked' => 'AC', // Active
        'fulfilled' => 'CO', // Completed
        //'AB', // Aborted
        'cancelled' => 'CA', // Cancelled
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
