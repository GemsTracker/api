<?php

namespace Pulse\Api\Fhir\Model\Transformer;


class AppointmentInfoTransformer extends \MUtil_Model_ModelTransformerAbstract
{
    public function transformLoad(\MUtil_Model_ModelAbstract $model, array $data, $new = false, $isPostData = false)
    {
        foreach($data as $key=>$row) {
            $info = [];

            $admissionTime = new \DateTimeImmutable($row['gap_admission_time']);

            // Add definitiveDate if OK appointment
            if ($row['gaa_name'] !== null && strpos($row['gaa_name'], 'OK ') === 0) {
                $info['definitiveDate'] = false;
                if ($row['gap_admission_time']) {
                    $definitiveTime = $admissionTime->sub(new \DateInterval('P3D'));
                    if ($definitiveTime <= new \DateTimeImmutable()) {
                        $info['definitiveDate'] = true;
                    }
                }
            }

            // Add present time if set in info, otherwise fall back to admission time
            $info['presentTime'] = $admissionTime->format(\DateTime::ATOM);
            if (isset($row['gap_info'])) {
                $appointmentInfo = json_decode($row['gap_info'], true);
                if ($appointmentInfo && isset($appointmentInfo['present_time'])) {
                    try {
                        $presentTime = new \DateTimeImmutable($appointmentInfo['present_time']);
                        $info['presentTime'] = $presentTime->format(\DateTime::ATOM);
                    } catch (\Exception $e) {
                    }
                }
            }

            if (count($info)) {
                $data[$key]['info'] = $info;
            }
        }

        return $data;
    }
}
