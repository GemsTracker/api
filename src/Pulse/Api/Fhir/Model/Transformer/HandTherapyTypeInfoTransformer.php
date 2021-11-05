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

            if (!isset($availableDates[$row['gr2t_created']])) {
                $availableDates[$row['gr2t_created']] = $rowKey;

                $data[$rowKey]['info'][] = [
                    'type' => 'hand-therapy-info',
                    'id' => 'HT' . $row['gtrt_hand_therapy_info'],
                    'value' => 'HT' . $row['gtrt_hand_therapy_info'],
                ];

                continue;
            }

            $otherRowKey = $availableDates[$row['gr2t_created']];

            if (isset($row['gtrt_hand_therapy_info']) && $row['gtrt_hand_therapy_info'] < $data[$otherRowKey]['gtrt_hand_therapy_info']) {
                foreach($data[$otherRowKey]['info'] as $infoKey => $infoRow) {
                    if ($infoRow['type'] == 'hand-therapy-info') {
                        unset($data[$otherRowKey]['info'][$infoKey]);
                        break;
                    }
                }

                $data[$rowKey]['info'][] = [
                    'type' => 'hand-therapy-info',
                    'id' => 'HT' . $row['gtrt_hand_therapy_info'],
                    'value' => 'HT' . $row['gtrt_hand_therapy_info'],
                ];
                $availableDates[$row['gr2t_created']] = $rowKey;
            }
        }

        return $data;
    }
}