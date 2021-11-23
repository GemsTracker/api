<?php

namespace Pulse\Api\Fhir\Model\Transformer;

class HandTherapyTypeInfoTransformer extends \MUtil_Model_ModelTransformerAbstract
{
    protected $applyToOrganizationIds = [
        73,
        88,
    ];

    protected $applyToStatus = [
        'completed',
        'active',
    ];

    public function transformLoad(\MUtil_Model_ModelAbstract $model, array $data, $new = false, $isPostData = false)
    {
        $sortedTreatmentsTypes = [];

        foreach($data as $rowKey=>$row) {
            // only apply to specific organizations
            if (!in_array($row['gr2t_id_organization'], $this->applyToOrganizationIds)) {
                continue;
            }
            // only apply to specific status
            if (!in_array($row['status'], $this->applyToStatus)) {
                continue;
            }

            $createdDay = substr($row['gr2t_created'], 0, 10);
            if (!isset($sortedTreatmentsTypes[$createdDay])) {
                $sortedTreatmentsTypes[$createdDay] = [];
            }

            if (isset($row['gtrt_hand_therapy_info'])) {
                $sortedTreatmentsTypes[$createdDay][$rowKey] = [
                    'info' => $row['gtrt_hand_therapy_info'],
                    'created' => $row['gr2t_created'],
                ];
            }
        }


        foreach($sortedTreatmentsTypes as $treatments) {
            if (empty($treatments)) {
                continue;
            }
            $therapyInfoTreatmentRowKey = key($treatments);
            $therapyInfoTreatmentRow = reset($treatments);

            if (count($treatments) > 1) {
                foreach ($treatments as $rowKey => $treatment) {
                    if ($rowKey === $therapyInfoTreatmentRowKey) {
                        continue;
                    }
                    if ($treatment['info'] < $therapyInfoTreatmentRow['info']) {
                        $therapyInfoTreatmentRowKey = $rowKey;
                        $therapyInfoTreatmentRow = $treatment;
                    }
                    if ($treatment['info'] === $therapyInfoTreatmentRow['info']) {
                        $current = new \DateTimeImmutable($treatment['created']);
                        $new = new \DateTimeImmutable($therapyInfoTreatmentRow['created']);
                        if ($new > $current) {
                            $therapyInfoTreatmentRowKey = $rowKey;
                            $therapyInfoTreatmentRow = $treatment;
                        }
                    }
                }
            }

            $data[$therapyInfoTreatmentRowKey]['info'][] = [
                'type' => 'hand-therapy-info',
                'id' => 'HT' . $therapyInfoTreatmentRow['info'],
                'value' => 'HT' . $therapyInfoTreatmentRow['info'],
            ];
        }

        return $data;
    }
}
