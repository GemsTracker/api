<?php

namespace Pulse\Api\Emma\Fhir\Model\Transformer;

/**
 * Transform patient Identifiers
 */
class PatientIdentifierTransformer extends \MUtil_Model_ModelTransformerAbstract
{
    protected $bsnSystem = 'http://fhir.nl/fhir/NamingSystem/bsn';

    protected $emmaNrSystem = 'http://fhir.timeff.com/identifier/patientnummer';

    public function transformRowBeforeSave(\MUtil_Model_ModelAbstract $model, array $row)
    {
        if (isset($row['identifier'])) {
            foreach($row['identifier'] as $identifierItem) {
                if (isset($identifierItem['system'], $identifierItem['value']) && $identifierItem['system'] === $this->bsnSystem) {
                    $ssn = $identifierItem['value'];
                    if (strlen($ssn) === 8) {
                        $ssn = '0' . $identifierItem['value'];
                    }
                    $validator = new \MUtil_Validate_Dutch_Burgerservicenummer();
                    if ($validator->isValid($ssn)) {
                        $row['grs_ssn'] = $ssn;
                    }
                }

                if (isset($identifierItem['system'], $identifierItem['value']) && $identifierItem['system'] === $this->emmaNrSystem) {
                    $row['gr2o_patient_nr'] = $identifierItem['value'];
                }
            }
        }

        return $row;
    }
}
