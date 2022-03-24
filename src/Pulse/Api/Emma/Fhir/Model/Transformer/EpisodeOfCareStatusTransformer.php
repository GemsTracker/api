<?php

declare(strict_types=1);


namespace Pulse\Api\Emma\Fhir\Model\Transformer;

/**
 * Translate Episode status
 */
class EpisodeOfCareStatusTransformer extends \MUtil_Model_ModelTransformerAbstract
{
    protected $statusTranslations = [
        'active' => 'A',
        'cancelled' => 'C',
        'entered-in-error' => 'E',
        'finished' => 'F',
        'onhold' => 'O',
        'planned' => 'P',
        'waitlist' => 'W',
    ];

    public function transformRowBeforeSave(\MUtil_Model_ModelAbstract $model, array $row)
    {
        if (isset($row['status'])) {
            if (isset($this->statusTranslations[$row['status']])) {
                $row['gec_status'] = $this->statusTranslations[$row['status']];
            }
        }

        return $row;
    }
}
