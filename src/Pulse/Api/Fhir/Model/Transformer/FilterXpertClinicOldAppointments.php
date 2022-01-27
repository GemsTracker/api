<?php

namespace Pulse\Api\Fhir\Model\Transformer;


class FilterXpertClinicOldAppointments extends \MUtil_Model_ModelTransformerAbstract
{
    protected $applyToOrganizationIds = [
        73,
        88,
    ];

    protected $oldAppointmentThreshold = '2021-10-14 00:00:00';

    public function transformLoad(\MUtil_Model_ModelAbstract $model, array $data, $new = false, $isPostData = false)
    {
        foreach($data as $key=>$row) {
            if (!in_array($row['gap_id_organization'], $this->applyToOrganizationIds)) {
                continue;
            }
            $admissionTime = new \DateTimeImmutable($row['gap_admission_time']);
            $threshold = new \DateTimeImmutable($this->oldAppointmentThreshold);
            if ($admissionTime < $threshold) {
                unset($data[$key]);
            }
        }

        return $data;
    }
}
