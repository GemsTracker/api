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
                $definitiveInfo = [
                    'type' => 'definitiveDate',
                    'value' => false,
                ];
                if ($row['gap_admission_time']) {
                    $definitiveTime = $admissionTime->sub(new \DateInterval('P3D'));
                    if ($definitiveTime <= new \DateTimeImmutable()) {
                        $definitiveInfo['value'] = true;
                    }
                }
                $info[] = $definitiveInfo;
            }

            // Add present time if set in info, otherwise fall back to admission time
            $presentTimeInfo = [
                'type' => 'presentTime',
                'value' => $admissionTime->format(\DateTime::ATOM),
            ];
            if (isset($row['gap_info'])) {
                $appointmentInfo = json_decode(trim(str_replace(['\"', '":"', '+00:00'], ['"', '": "', ''], $row['gap_info']), '"'), true);
                if ($appointmentInfo && isset($appointmentInfo['present_time'])) {
                    try {
                        $presentTime = new \DateTimeImmutable($appointmentInfo['present_time']);
                        $presentTimeInfo['value'] = $presentTime->format(\DateTime::ATOM);
                    } catch (\Exception $e) {
                    }
                }
            }
            $info[] = $presentTimeInfo;

            if (count($info)) {
                $data[$key]['info'] = $info;
            }
        }

        return $data;
    }
}
