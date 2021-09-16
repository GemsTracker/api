<?php

namespace Pulse\Api\Model;


use Gems\Rest\Fhir\Model\Transformer\IntTransformer;
use Pulse\Api\Model\Transformer\OutcomeDiagnosisTreatmentTransformer;

class OutcomeVariableModel extends \Pulse\Model\OutcomeVariableModel
{
    public function applySettings($detailed = true)
    {
        parent::applySettings($detailed);
        $this->addTransformer(new IntTransformer(['pt2o_id', 'pt2o_id_diagnosis', 'pt2o_id_treatment', 'pt2o_id_survey', 'pt2o_order']));
        $this->addTransformer(new OutcomeDiagnosisTreatmentTransformer());
    }
}
