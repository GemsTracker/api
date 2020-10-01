<?php


namespace Gems\Rest\Fhir\Model\Transformer;

use Gems\Rest\Fhir\Endpoints;
use Gems\Rest\Fhir\PatientInformationFormatter;

class QuestionnaireTaskOwnerTransformer extends \MUtil_Model_ModelTransformerAbstract
{
    protected $fieldName = 'owner';

    public function transformFilter(\MUtil_Model_ModelAbstract $model, array $filter)
    {
        if (isset($filter['owner_name'])) {
            $name = $filter['owner_name'];

            if (isset($filter['owner_type'])) {
                switch(strtolower($filter['owner_type'])) {
                    case 'patient':
                        $filter[] = "(grs_first_name = '".$name."')
                         OR (grs_initials_name = '".$name."')
                         OR (grs_last_name = '".$name."')
                         OR (grs_surname_prefix = '".$name."')
                        ";
                        break;
                    case 'relatedperson':
                        $filter[] = "(grr_first_name = '".$name."')
                         OR (grr_last_name = '".$name."')
                        ";
                        break;
                    case 'organization':
                        $filter[] = "(gor_name = '".$name."')";
                        break;
                    case 'practitioner':
                        $filter[] = "(gas_name = '".$name."')
                         OR (gsf_first_name = '".$name."')
                         OR (gsf_last_name = '".$name."')
                         OR (gsf_surname_prefix = '".$name."')
                        ";
                        break;
                }
            } else {
                // Assume search for patient name
                $filter[] = "(grs_first_name = '".$name."')
                 OR (grs_initials_name = '".$name."')
                 OR (grs_last_name = '".$name."')
                 OR (grs_surname_prefix = '".$name."')
                ";
            }

            unset($filter['owner_name']);
        }

        if (isset($filter['owner'])) {
            $id = $filter['owner'];

            $ownerType = null;
            if (isset($filter['owner_type'])) {
                $ownerType = strtolower($filter['owner_type']);
            }

            if (strpos($id,'Patient/') === 0 || strpos($id,Endpoints::PATIENT) === 0 || $ownerType == 'patient') {
                list($patientNr, $organizationId) = str_replace(['Patient/', Endpoints::PATIENT], '', $id);
                $filter['gr2o_patient_nr'] = $patientNr;
                $filter['gr2o_id_organization'] = $organizationId;

                $filter['owner_type'] = 'patient';

            } elseif (strpos($id,'RelatedPerson/') === 0 || strpos($id,Endpoints::RELATED_PERSON) === 0 || $ownerType == 'relatedperson') {
                $id = str_replace(['RelatedPerson/', Endpoints::RELATED_PERSON], '', $id);
                $filter['grr_id_relation'] = $id;

                $filter['owner_type'] = 'relatedperson';

            } elseif (strpos($id,'Organization/') === 0 || strpos($id,Endpoints::ORGANIZATION) === 0 || $ownerType == 'organization') {
                $id = str_replace(['Organization/', Endpoints::ORGANIZATION], '', $id);
                $filter['gto_id_organization'] = $id;

                $filter['owner_type'] = 'organization';

            } elseif (strpos($id,'Practitioner/') === 0 || strpos($id,Endpoints::PRACTITIONER) === 0 || $ownerType == 'practitioner') {
                $id = str_replace(['Practitioner/', Endpoints::PRACTITIONER], '', $id);
                $filter['gas_id_user'] = $id;

                $filter['owner_type'] = 'practitioner';

            } elseif (strpos($id, '@') !== false) {
                // Assume patient if delimiter is used
                list($patientNr, $organizationId) = str_replace(['Patient/', Endpoints::PATIENT], '', $id);
                $filter['gr2o_patient_nr'] = $patientNr;
                $filter['gr2o_id_organization'] = $organizationId;

                $filter['owner_type'] = 'patient';
            }
        }

        if (isset($filter['owner_type'])) {
            switch(strtolower($filter['owner_type'])) {
                case 'patient':
                    $filter['ggp_respondent_members'] = 1;
                    $filter[] = 'gto_id_relation IS NULL';
                    break;
                case 'relatedperson':
                    $filter['ggp_respondent_members'] = 1;
                    $filter[] = 'gto_id_relationfield IS NOT NULL';
                    $filter['gto_id_relation > 0'];
                    break;
                case 'organization':
                    $filter['ggp_staff_members'] = 1;
                    $filter[] = 'gto_by IS NULL';
                    break;
                case 'practitioner':
                    $filter['ggp_staff_members'] = 1;
                    $filter[] = 'gto_by IS NOT NULL';
                    $filter[] = 'gas_id_user IS NOT NULL';
                    break;
            }
            unset($filter['owner_type']);
        }



        return $filter;
    }

    /**
     * The transform function performs the actual transformation of the data and is called after
     * the loading of the data in the source model.
     *
     * @param \MUtil_Model_ModelAbstract $model The parent model
     * @param array $data Nested array
     * @param boolean $new True when loading a new item
     * @param boolean $isPostData With post data, unselected multiOptions values are not set so should be added
     * @return array Nested array containing (optionally) transformed data
     */
    public function transformLoad(\MUtil_Model_ModelAbstract $model, array $data, $new = false, $isPostData = false)
    {
        foreach ($data as $key => $row) {

            if ($row['ggp_respondent_members'] == 1) {
                if ($row['gto_id_relationfield'] !== null && $row['gto_id_relation'] > 0) {
                    $data[$key][$this->fieldName] = $this->getRelationReference($row);
                } else {
                    $data[$key][$this->fieldName] = $this->getPatientReference($row);

                }
            } elseif ($row['ggp_staff_members'] == 1) {
                if ($row['gto_by'] !== null && $row['gas_id_user'] !== null) {
                    $data[$key][$this->fieldName] = $this->getPractitionerReference($row);
                } else {
                    $data[$key][$this->fieldName] = $this->getOrganizationReference($row);
                }
            }

        }

        return $data;
    }

    protected function getOrganizationReference($row)
    {
        return [
            'type' => 'Organization',
            'id' => $row['gto_id_organization'],
            'reference' => Endpoints::ORGANIZATION . $row['gto_id_organization'],
            'display' => $row['gor_name'],
        ];
    }

    protected function getPatientReference($row)
    {
        $information = new PatientInformationFormatter($row);
        return [
            'type' => 'Patient',
            'id' => $information->getIdentifier(),
            'reference' => $information->getReference(),
            'display' => $information->getDisplayName(),
        ];
    }

    protected function getPractitionerReference($row)
    {
        return [
            'type' => 'Practitioner',
            'id' => $row['gas_id_user'],
            'reference' => Endpoints::PRACTITIONER . $row['gas_id_user'],
            'display' => $row['gas_name'],
        ];
    }

    protected function getRelationReference($row)
    {
        $name = '';

        if (isset($row['grr_first_name'])) {
            $name .= $row['grr_first_name'] . ' ';
        }

        if (isset($row['grr_last_name'])) {
            $name .= $row['grr_last_name'];
        }

        $relationship = 'unknown';
        if (isset($row['grr_type'])) {
            $relationship = $row['grr_type'];
        }

        return [
            'type' => 'RelatedPerson',
            'id' => $row['gto_id_relation'],
            'reference' => Endpoints::RELATED_PERSON . $row['gto_id_relation'],
            'display' => $name,
            'relationship' => $relationship,
        ];
    }
}
