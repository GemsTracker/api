<?php

namespace Pulse\Api\Fhir\Model\Transformer;

class HandTherapyAllowedTrackCodesTransformer extends \MUtil_Model_ModelTransformerAbstract
{
    protected $applyToOrganizationIds = [
        73,
        88,
    ];

    protected $allowedTrackCodes = [
        'finger',
        'nerve',
        'thumb',
        'trauma',
        'wrist',
    ];

    public function transformLoad(\MUtil_Model_ModelAbstract $model, array $data, $new = false, $isPostData = false)
    {
        foreach($data as $rowKey=>$row) {
            // only apply to specific organizations
            if (!in_array($row['gr2t_id_organization'], $this->applyToOrganizationIds)) {
                continue;
            }
            // only SHOW specific tracks
            if (!in_array($row['gtr_code'], $this->allowedTrackCodes)) {
                unset($data[$rowKey]);
                continue;
            }
        }

        return array_values($data);
    }

}
