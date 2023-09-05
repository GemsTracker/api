<?php

namespace Pulse\Api\Model;

class TreatmentTransformedModel extends \Ichom\Model\TreatmentTransformedModel
{
    public function applyBrowseSettings()
    {
        parent::applyBrowseSettings();

        $this->set('gtrt_hand_therapy_info', [
            'label' => 'Recovery code',
            'apiName' => 'recoveryCode',
        ]);
    }
}