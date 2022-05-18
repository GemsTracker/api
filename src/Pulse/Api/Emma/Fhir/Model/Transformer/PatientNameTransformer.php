<?php

namespace Pulse\Api\Emma\Fhir\Model\Transformer;


use Gems\Rest\Exception\IncorrectDataException;
use Gems\Rest\Exception\MissingDataException;

/**
 * Transform patient names
 */
class PatientNameTransformer extends \MUtil_Model_ModelTransformerAbstract
{
    protected $humanNameExtensionOrderUrl = 'http://hl7.org/fhir/StructureDefinition/humanname-assembly-order';

    protected $humanNameOwnNameUrl = 'http://hl7.org/fhir/StructureDefinition/humanname-own-name';

    protected $humanNameOwnPrefixUrl = 'http://hl7.org/fhir/StructureDefinition/humanname-own-prefix';

    protected $humanNamePartnerNameUrl = 'http://hl7.org/fhir/StructureDefinition/humanname-partner-name';

    protected $humanNamePartnerPrefixUrl = 'http://hl7.org/fhir/StructureDefinition/humanname-partner-prefix';

    protected $humanNameQualifierUrl = 'http://hl7.org/fhir/StructureDefinition/iso21090-EN-qualifier';


    public function transformRowBeforeSave(\MUtil_Model_ModelAbstract $model, array $row)
    {
        if (!isset($row['name']) || !is_array($row['name'])) {
            throw new MissingDataException('name is missing');
        }

        foreach($row['name'] as $name) {
            if (!isset($name['family'])) {
                throw new MissingDataException('family name is missing');
            }

            $row['grs_raw_last_name'] = null;
            $row['grs_raw_surname_prefix'] = null;
            $row['grs_partner_last_name'] = null;
            $row['grs_partner_surname_prefix'] = null;
            $row['grs_last_name_order'] = null;
            $row['grs_surname_prefix'] = null;
            $row['grs_last_name'] = null;
            $row['grs_first_name'] = null;
            $row['grs_initials_name'] = null;


            $familyNameData = $this->getFamilyName($name);
            $row = array_merge($row, $familyNameData);

            if (isset($name['given'])) {
                $givenNameData = $this->getGivenName($name);
                $row = array_merge($row, $givenNameData);
            }
        }

        return $row;
    }

    public function getFamilyName($name)
    {
        $row = [];
        if (!is_array($name['family'])) {
            $row['grs_last_name'] = $name['family'];
        }

        if (isset($name['_family'], $name['_family']['extension'])) {
            foreach($name['_family']['extension'] as $familyNameItem) {
                if (isset($familyNameItem['url'], $familyNameItem['valueString'])) {
                    switch($familyNameItem['url']) {
                        case $this->humanNameOwnNameUrl:
                            $row['grs_raw_last_name'] = $familyNameItem['valueString'];
                            break;
                        case $this->humanNameOwnPrefixUrl:
                            $row['grs_raw_surname_prefix'] = $familyNameItem['valueString'];
                            break;
                        case $this->humanNamePartnerNameUrl:
                            $row['grs_partner_last_name'] = $familyNameItem['valueString'];
                            break;
                        case $this->humanNamePartnerPrefixUrl:
                            $row['grs_partner_surname_prefix'] = $familyNameItem['valueString'];
                            break;
                        default:
                            break;
                    }
                }
            }
        }

        $row['grs_last_name_order'] = 'surname';
        $row['grs_surname_prefix'] = null;
        if (isset($name['extension'])) {
            foreach($name['extension'] as $extension) {
                if (isset($extension['url']) && $extension['url'] === $this->humanNameExtensionOrderUrl) {
                    switch($extension['valueCode']) {
                        case 'NL1':
                            $row['grs_last_name_order'] = 'surname';
                            if (isset($row['grs_raw_last_name'])) {
                                $row['grs_last_name'] = $row['grs_raw_last_name'];
                            }
                            if (isset($row['grs_raw_surname_prefix'])) {
                                $row['grs_surname_prefix'] = $row['grs_raw_surname_prefix'];
                            }
                            break;
                        case 'NL2':
                            $row['grs_last_name_order'] = 'partner name';
                            if (isset($row['grs_partner_last_name'])) {
                                $row['grs_last_name'] = $row['grs_partner_last_name'];
                            }
                            if (isset($row['grs_partner_surname_prefix'])) {
                                $row['grs_surname_prefix'] = $row['grs_partner_surname_prefix'];
                            }
                            break;
                        case 'NL3':
                            $row['grs_last_name_order'] = 'partner name, surname';
                            if (isset($row['grs_partner_last_name'])) {
                                $row['grs_last_name'] = $row['grs_partner_last_name'];

                                if (isset($row['grs_partner_surname_prefix'])) {
                                    $row['grs_surname_prefix'] = $row['grs_partner_surname_prefix'];
                                }
                                if (isset($row['grs_last_name'])) {
                                    $row['grs_last_name'] .= ' -';
                                    if (isset($row['grs_raw_surname_prefix'])) {
                                        $row['grs_last_name'] .= ' ' . $row['grs_raw_surname_prefix'];
                                    }
                                    $row['grs_last_name'] .= ' ' . $row['grs_raw_last_name'];
                                }
                            }
                            break;
                        case 'NL4':
                            $row['grs_last_name_order'] = 'surname, partner name';
                            if (isset($row['grs_raw_last_name'])) {
                                $row['grs_last_name'] = $row['grs_raw_last_name'];
                            }
                            if (isset($row['grs_raw_surname_prefix'])) {
                                $row['grs_surname_prefix'] = $row['grs_raw_surname_prefix'];
                            }
                            if (isset($row['grs_partner_last_name'])) {
                                $row['grs_last_name'] .= ' -';
                                if (isset($row['grs_partner_surname_prefix'])) {
                                    $row['grs_last_name'] .= ' ' . $row['grs_partner_surname_prefix'];
                                }
                                $row['grs_last_name'] .= ' ' . $row['grs_partner_last_name'];
                            }
                            break;
                        default:
                            break;
                    }
                }
            }
        }

        return $row;
    }

    protected function getGivenName($name)
    {
        $row = [];
        if (!is_array($name['given'])) {
            $row['grs_first_name'] = $name['given'];
        } else {
            if (!isset($name['_given'])) {
                throw new IncorrectDataException('No given name helpers found while given is an array');
            }
            foreach($name['_given'] as $helperIndex => $givenNameHelper) {
                if (isset($givenNameHelper['extension'], $givenNameHelper['extension'][0], $givenNameHelper['extension'][0]['url'], $givenNameHelper['extension'][0]['valueCode'], $name['given'][$helperIndex])) {
                    if ($givenNameHelper['extension'][0]['url'] === $this->humanNameQualifierUrl) {
                        switch($givenNameHelper['extension'][0]['valueCode']) {
                            case 'BR':
                                $row['grs_first_name'] = $name['given'][$helperIndex];
                                break;
                            case 'IN':
                                $row['grs_initials_name'] = $name['given'][$helperIndex];
                            default:
                                break;
                        }
                    }
                }
            }
        }


        return $row;
    }
}
