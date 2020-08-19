<?php


namespace Gems\Rest\Fhir\Model\Transformer;


use Gems\Rest\Fhir\PatientInformationFormatter;

class AppointmentParticipantTransformer extends \MUtil_Model_ModelTransformerAbstract
{
    /**
     * This transform function checks the filter for
     * a) retreiving filters to be applied to the transforming data,
     * b) adding filters that are needed
     *
     * @param \MUtil_Model_ModelAbstract $model
     * @param array $filter
     * @return array The (optionally changed) filter
     */
    public function transformFilter(\MUtil_Model_ModelAbstract $model, array $filter)
    {
        if (isset($filter['patient'])) {
            $patientFormatter = new PatientInformationFormatter($filter);
            if (!is_array($filter['patient'])) {
                $filter['patient'] = [$filter['patient']];
            }

            $patientSearchParts = [];
            foreach($filter['patient'] as $patient) {
                $value = explode('@', str_replace($patientFormatter->getPatientEndpoint(), '', $patient));

                if (count($value) === 2) {
                    $patientSearchParts[] = '(gr2o_patient_nr = ' . $value[0] . ' AND gr2o_id_organization = ' . $value[1] . ')';
                }
            }
            if (count($patientSearchParts)) {
                $filter[] = '(' . join(' OR ', $patientSearchParts) . ')';
            }

            unset($filter['patient']);
        }
        if (isset($filter['patient.email'])) {
            $value = $filter['patient.email'];
            unset($filter['patient.email']);

            $filter['gr2o_email'] = $value;
        }

        if (isset($filter['practitioner'])) {
            $value = (int)str_replace($this->getPractitionerEndpoint(), '', $filter['practitioner']);
            $filter['gap_id_attended_by'] = $value;

            unset($filter['practitioner']);
        }
        if (isset($filter['practitioner.name'])) {
            $value = $filter['practitioner.name'];
            $filter[] = "gas_name LIKE '%".$value."'%";

            unset($filter['practitioner.name']);
        }

        if (isset($filter['location'])) {
            $value = (int)str_replace($this->getLocationEndpoint(), '', $filter['location']);
            $filter['gap_id_location'] = $value;

            unset($filter['location']);
        }
        if (isset($filter['location.name'])) {
            $value = $filter['location.name'];
            $filter['glo_name'] = $value;

            unset($filter['location.name']);
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
        foreach($data as $key=>$item) {
            if (isset($item['gap_id_user'])) {
                $patientFormatter = new PatientInformationFormatter($item);

                $participant = [
                    'actor' => [
                        'type' => 'Patient',
                        'id' => $patientFormatter->getIdentifier(),
                        'reference' => $patientFormatter->getReference(),
                        'display' => $patientFormatter->getDisplayName(),
                    ],
                ];
                $data[$key]['participant'][] = $participant;
            }
            if (isset($item['gap_id_attended_by'])) {
                $participant = [
                    'actor' => [
                        'type' => 'Practitioner',
                        'id' => $item['gap_id_attended_by'],
                        'reference' => $this->getPractitionerEndpoint() . $item['gap_id_attended_by'],
                        'display' => $item['gas_name'],
                    ],
                ];
                $data[$key]['participant'][] = $participant;
            }
            if (isset($item['gap_id_location'])) {
                $participant = [
                    'actor' => [
                        'type' => 'Location',
                        'id' => $item['gap_id_location'],
                        'reference' => $this->getLocationEndpoint() . $item['gap_id_location'],
                        'display' => $item['glo_name'],
                    ],
                ];
                $data[$key]['participant'][] = $participant;
            }
        }


        return $data;
    }

    protected function getLocationEndpoint()
    {
        return 'fhir/location/';
    }

    protected function getPractitionerEndpoint()
    {
        return 'fhir/practitioner/';
    }
}
