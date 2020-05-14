<?php


namespace Gems\Rest\Fhir;


class PatientInformationFormatter
{
    /**
     * @var array
     */
    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function getIdentifier()
    {
        if (isset($this->data['gr2o_patient_nr'], $this->data['gr2o_id_organization'])) {
            return $this->data['gr2o_patient_nr'] . '@' . $this->data['gr2o_id_organization'];
        }
        return null;
    }

    public function getDisplayName()
    {
        $displayName = null;

        if (isset($this->data['grs_last_name'])) {
            $displayName = $this->data['grs_last_name'];
        }

        if (isset($this->data['grs_surname_prefix'])) {
            $displayName = $this->data['grs_surname_prefix'] . $displayName;
        }

        if (isset($this->data['grs_first_name'])) {
            $displayName = $this->data['grs_first_name'] . $displayName;
        } elseif (isset($this->data['grs_initials_name'])) {
            $displayName = $this->data['grs_initials_name'] . $displayName;
        }

        return $displayName;
    }

    public function getReference()
    {
        return $this->getPatientEndpoint() . $this->getIdentifier();
    }

    public function getPatientEndpoint()
    {
        return 'fhir/patient/';
    }
}
