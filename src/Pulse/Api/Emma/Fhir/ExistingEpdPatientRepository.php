<?php

declare(strict_types=1);


namespace Pulse\Api\Emma\Fhir;


use Gems\Rest\Model\ModelTranslateException;
use Pulse\Api\Emma\Fhir\Repository\ImportLogRepository;
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
    /**
     * @var ImportLogRepository
     */
    protected $importLogRepository;

    public function __construct(RespondentRepository $respondentRepository, ImportLogRepository $importLogRepository)
    {
        $this->respondentRepository = $respondentRepository;
        $this->importLogRepository = $importLogRepository;
    }

    public function getExistingPatients($ssn, $patientNr)
    {
        $existingPatients = null;
        if ($ssn !== null) {
            $existingPatients = $this->respondentRepository->getPatientsFromSsn($ssn, $this->currentEpd);
        }

        if ($existingPatients) {
            $deletedExistingPatient = false;
            foreach($existingPatients as $key=>$existingPatient) {
                if (isset($existingPatient['gr2o_patient_nr']) && $existingPatient['gr2o_patient_nr'] != $patientNr) {
                    if ($this->respondentRepository->patientNrExistsInEpd($patientNr, $this->currentEpd)) {
                        if ($deletedExistingPatient === false) {
                            $log = $this->importLogRepository->getImportLogger('respondent-merge');
                            if ($existingPatient['gr2o_reception_code'] !== 'deleted') {
                                $message = sprintf(
                                    'Patient nr %s already exists for epd %s. SSN %s is already known in patient nr %s. Patient %s not saved!!',
                                    $patientNr,
                                    $this->currentEpd,
                                    $ssn,
                                    $existingPatient['gr2o_patient_nr'],
                                    $patientNr
                                );
                                $log->error($message);
                                throw new ModelTranslateException($message);
                            }

                            $comment = $existingPatient['gr2o_comments'] .= sprintf("\nSSN %s removed in favor of patientnr %s", $ssn, $patientNr);
                            $this->respondentRepository->removeSsnFromRespondent($existingPatient['gr2o_id_user'], $comment);
                            $log->alert(
                                sprintf(
                                    'Existing patient nr %s was deleted. Its ssn has been removed. It can be merged with %s',
                                    $existingPatient['gr2o_patient_nr'],
                                    $patientNr
                                ));
                            $deletedExistingPatient = true;
                        }
                        unset($existingPatients[$key]);
                    } else {
                        $copyKey = $this->getKeyCopyName('gr2o_patient_nr');
                        $existingPatients[$key][$copyKey] = $existingPatient['gr2o_patient_nr'];
                        $existingPatients[$key]['gr2o_patient_nr'] = $patientNr;
                    }

                }
            }
        }

        if ($existingPatients === null || count($existingPatients) === 0) {
            $existingPatients = $this->respondentRepository->getPatientsFromPatientNr($patientNr, $this->currentEpd);
            if ($existingPatients) {
                foreach ($existingPatients as $key => $existingPatient) {
                    $existingPatients[$key]['grs_ssn'] = $ssn;
                }
            }
        }

        return $existingPatients;
    }

    public function getExistingPatientsByPatientNumber($patientNr)
    {
        return $this->respondentRepository->getPatientsFromPatientNr($patientNr, $this->currentEpd);
    }

    protected function getKeyCopyName($name)
    {
        return sprintf(\MUtil_Model_DatabaseModelAbstract::KEY_COPIER, $name);
    }
}
