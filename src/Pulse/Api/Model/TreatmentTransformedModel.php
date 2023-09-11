<?php

namespace Pulse\Api\Model;

use Gems\Rest\Fhir\Model\Transformer\IntTransformer;

class TreatmentTransformedModel extends \Ichom\Model\TreatmentTransformedModel
{
    public function applyBrowseSettings()
    {
        parent::applyBrowseSettings();

        $this->set('gtrt_hand_therapy_info', [
            'label' => 'Recovery code',
            'apiName' => 'recoveryCode',
        ]);

        $this->addTransformer(new IntTransformer(['gtrt_hand_therapy_info']));
    }
}