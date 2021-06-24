<?php

namespace Pulse\Api\Model;


use Gems\Rest\Fhir\Model\Transformer\IntTransformer;

class OutcomeVariableModel extends \Pulse\Model\OutcomeVariableModel
{
    public function applySettings($detailed = true)
    {
        parent::applySettings($detailed);
        $this->addTransformer(new IntTransformer(['pt2o_id', 'pt2o_id_treatment', 'pt2o_id_survey']));
    }
}
