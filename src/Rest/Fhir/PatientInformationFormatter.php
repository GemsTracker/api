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
        $displayNameParts = [];

        if (isset($this->data['grs_last_name'])) {
            $displayNameParts[] = $this->data['grs_last_name'];
        }

        if (isset($this->data['grs_surname_prefix'])) {
            $displayNameParts = array_unshift($displayNameParts, $this->data['grs_surname_prefix']);
        }

        if (isset($this->data['grs_first_name'])) {
            $displayNameParts = array_unshift($displayNameParts, $this->data['grs_first_name']);
        } elseif (isset($this->data['grs_initials_name'])) {
            $displayNameParts = array_unshift($displayNameParts, $this->data['grs_initials_name']);
        }

        return join(' ', $displayNameParts);
    }

    public function getReference()
    {
        return $this->getPatientEndpoint() . $this->getIdentifier();
    }

    public function getPatientEndpoint()
    {
        return Endpoints::PATIENT;
    }
}
