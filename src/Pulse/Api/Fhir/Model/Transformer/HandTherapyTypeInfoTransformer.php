<?php

namespace Pulse\Api\Fhir\Model\Transformer;

class HandTherapyTypeInfoTransformer extends \MUtil_Model_ModelTransformerAbstract
{
    protected $applyToOrganizationIds = [
        73,
        88,
    ];

    public function transformLoad(\MUtil_Model_ModelAbstract $model, array $data, $new = false, $isPostData = false)
    {
        $availableDates = [];

        foreach($data as $rowKey=>$row) {
            // only apply to specific organizations
            if (!in_array($row['gr2t_id_organization'], $this->applyToOrganizationIds)) {
                continue;
            }

            if (!isset($availableDates[$row['treatment_start_date']])) {
                $availableDates[$row['treatment_start_date']] = $rowKey;

                $data[$rowKey]['info'][] = [
                    'type' => 'handTherapyInfo',
                    'id' => 'HT' . $row['gtrt_hand_therapy_info'],
                ];

                continue;
            }

            $otherRowKey = $availableDates[$row['treatment_start_date']];

            if (isset($row['gtrt_hand_therapy_info']) && $row['gtrt_hand_therapy_info'] < $data[$otherRowKey]['gtrt_hand_therapy_info']) {
                foreach($data[$otherRowKey]['info'] as $infoKey => $infoRow) {
                    if ($infoRow['type'] == 'handTherapyInfo') {
                        unset($data[$otherRowKey]['info'][$infoKey]);
                        break;
                    }
                }

                $data[$rowKey]['info'][] = [
                    'type' => 'handTherapyInfo',
                    'id' => 'HT' . $row['gtrt_hand_therapy_info'],
                ];
                $availableDates[$row['treatment_start_date']] = $rowKey;
            }
        }

        return $data;
    }
}
