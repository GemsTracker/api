<?php

declare(strict_types=1);


namespace Pulse\Api\Emma\Fhir\Model\Transformer;


class AppointmentRequestedPeriodTransformer extends \MUtil_Model_ModelTransformerAbstract
{
    public function transformRowBeforeSave(\MUtil_Model_ModelAbstract $model, array $row)
    {
        if (isset($row['requestedPeriod'])) {
            foreach($row['requestedPeriod'] as $requestedPeriod) {
                if (isset($requestedPeriod['start']) && $requestedPeriod['start'] !== $row['gap_admission_time']) {
                    $info = [];
                    if (isset($row['gap_info']) && is_array($row['gap_info'])) {
                        $info = $row['gap_info'];
                    }

                    // Fix possible timezone error
                    $value = $requestedPeriod['start'];
                    if (strpos($value, '+') === 19 || strpos($value, '.') === 19) {
                        $value = substr($value, 0, 19);
                    }

                    $presentTime = new \DateTimeImmutable($value);

                    $info['present_time'] = $presentTime->format(\DateTimeInterface::ATOM);
                    $row['gap_info'] = $info;
                }
            }
        }

        return $row;
    }
}
