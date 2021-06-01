<?php


namespace Pulse\Api\Fhir\Model;


use Gems\Rest\Fhir\Model\Transformer\PatientReferenceTransformer;
use Pulse\Api\Fhir\Model\Transformer\PrefixedTreatmentInfoTransformer;
use Pulse\Api\Fhir\Model\Transformer\TreatmentIdTransformer;
use Pulse\Api\Fhir\Model\Transformer\TreatmentStatusTransformer;

class PrefixedCodeTreatmentModel extends TreatmentModel
{
    protected function addTransformers()
    {
        $this->addTransformer(new PatientReferenceTransformer('subject'));
        $this->addTransformer(new TreatmentIdTransformer());
        $this->addTransformer(new TreatmentStatusTransformer(self::RESPONDENTTRACKMODEL));
        $this->addTransformer(new PrefixedTreatmentInfoTransformer());
    }
}
