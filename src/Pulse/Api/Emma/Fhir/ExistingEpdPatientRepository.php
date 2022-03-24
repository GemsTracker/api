<?php

declare(strict_types=1);


namespace Pulse\Api\Emma\Fhir;


use Gems\Rest\Model\ModelTranslateException;
use Pulse\Api\Repository\RespondentRepository;

class ExistingEpdPatientRepository
{
    /**
     * @var \MUtil_Model_DatabaseModelAbstract
     */
    protected $model;

    /**
     * @var RespondentRepository
     */
    protected $respondentRepository;

    protected $currentEpd = 'emma';

    public function __construct(RespondentRepository $respondentRepository)
    {
        $this->respondentRepository = $respondentRepository;
    }

    public function getExistingPatients($ssn, $patientNr)
    {
        $existingPatients = $this->respondentRepository->getPatientsFromSsn($ssn, $this->currentEpd);
        if ($existingPatients) {
            foreach($existingPatients as $key=>$existingPatient) {
                if (isset($existingPatient['gr2o_patient_nr']) && $existingPatient['gr2o_patient_nr'] != $patientNr) {
                    if ($this->respondentRepository->patientNrExistsInEpd($patientNr, $this->currentEpd)) {
                        throw new ModelTranslateException(
                            sprintf(
                                'Patient nr %s already exists for epd %s. SSN %s is already known in patient nr %s',
                                $patientNr, $this->currentEpd,
                                $ssn, $existingPatient['gr2o_patient_nr']
                            )
                        );
                    }

                    $copyKey = $this->getKeyCopyName('gr2o_patient_nr');
                    $existingPatients[$key][$copyKey] = $existingPatient['gr2o_patient_nr'];
                    $existingPatients[$key]['gr2o_patient_nr'] = $patientNr;
                }
            }
        } else {
            $existingPatients = $this->respondentRepository->getPatientsFromPatientNr($patientNr, $this->currentEpd);
            if ($existingPatients) {
                foreach ($existingPatients as $key => $existingPatient) {
                    $existingPatients[$key]['grs_ssn'] = $ssn;
                }
            }
        }

        return $existingPatients;
    }

    protected function getKeyCopyName($name)
    {
        return sprintf(\MUtil_Model_DatabaseModelAbstract::KEY_COPIER, $name);
    }
}
