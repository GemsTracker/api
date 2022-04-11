<?php

namespace Pulse\Api\Emma\Fhir\Model\Transformer;


/**
 * Transform address fields
 */
class PatientAddressTransformer extends \MUtil_Model_ModelTransformerAbstract
{
    protected $currentAddressUse = null;

    protected $preferredAddressUse = 'home';

    protected $countryCodeExtensionUrl = 'http://nictiz.nl/fhir/StructureDefinition/code-specification';

    protected $countryCodeSystem = 'urn:iso:std:iso:3166';

    protected $addressLineStreetName = 'http://hl7.org/fhir/StructureDefinition/iso21090-ADXP-streetName';

    protected $addressLineHouseNumber = 'http://hl7.org/fhir/StructureDefinition/iso21090-ADXP-houseNumber';

    protected $addressLineBuildingNumberSuffix = 'http://hl7.org/fhir/StructureDefinition/iso21090-ADXP-buildingNumberSuffix';

    public function transformRowBeforeSave(\MUtil_Model_ModelAbstract $model, array $row)
    {
        $this->currentAddressUse = null;
        if (isset($row['address']) && is_array($row['address'])) {
            foreach($row['address']  as $addressItem) {
                if ($this->currentAddressUse !== $this->preferredAddressUse) {
                    if (isset($addressItem['city'])) {
                        $row['grs_city'] = ucfirst(strtolower($addressItem['city']));
                    }
                    if (isset($addressItem['postalCode'])) {
                        $row['grs_zipcode'] = $addressItem['postalCode'];
                    }
                    if (isset($addressItem['_country'],
                        $addressItem['_country']['extension'],
                        $addressItem['_country']['extension'][0],
                        $addressItem['_country']['extension'][0]['url'],
                        $addressItem['_country']['extension'][0]['valueCodeableConcept'],
                        $addressItem['_country']['extension'][0]['valueCodeableConcept']['coding']
                    )) {
                        if ($addressItem['_country']['extension'][0]['url'] === $this->countryCodeExtensionUrl) {
                            foreach($addressItem['_country']['extension'][0]['valueCodeableConcept']['coding'] as $countryCoding) {
                                if ($countryCoding['system'] === $this->countryCodeSystem && isset($countryCoding['code'])) {
                                    $row['grs_iso_country'] = strtoupper($countryCoding['code']);
                                }
                            }
                        }
                    }

                    if (isset($addressItem['_line'])) {
                        foreach($addressItem['_line'] as $addressLine) {
                            if (isset($addressLine['extension']) && is_array($addressLine['extension'])) {
                                $addressParts = [];
                                foreach($addressLine['extension'] as $addressLinePart) {
                                    if (isset($addressLinePart['url'], $addressLinePart['valueString'])) {
                                        if ($addressLinePart['url'] === $this->addressLineStreetName) {
                                            $addressParts['streetName'] = $addressLinePart['valueString'];
                                        }
                                        if ($addressLinePart['url'] === $this->addressLineHouseNumber) {
                                            $addressParts['houseNumber'] = $addressLinePart['valueString'];
                                        }
                                        if ($addressLinePart['url'] === $this->addressLineBuildingNumberSuffix) {
                                            $addressParts['houseNumberSuffix'] = $addressLinePart['valueString'];
                                        }
                                    }
                                }
                                if (isset($addressParts['streetName'], $addressParts['houseNumber'])) {
                                    $row['grs_address_1'] = $addressParts['streetName'] . ' ' . $addressParts['houseNumber'];
                                    if (isset($addressParts['houseNumberSuffix'])) {
                                        if (strlen($addressParts['houseNumberSuffix']) > 1) {
                                            $row['grs_address_1'] .= ' ';
                                        }
                                        $row['grs_address_1'] .= $addressParts['houseNumberSuffix'];
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        return $row;
    }
}
