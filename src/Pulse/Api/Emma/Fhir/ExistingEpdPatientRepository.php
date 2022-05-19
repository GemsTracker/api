<?php

declare(strict_types=1);


namespace Pulse\Api\Emma\Fhir;


use Gems\Event\EventDispatcher;
use Gems\Rest\Model\ModelTranslateException;
use Pulse\Api\Emma\Fhir\Event\RespondentMergeEvent;
use Pulse\Api\Emma\Fhir\Repository\ImportLogRepository;
use Pulse\Api\Repository\RespondentRepository;

class ExistingEpdPatientRepository
{
    protected $mergeRespondents = [];

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
     * @var EventDispatcher
     */
    protected $event;

    public function __construct(RespondentRepository $respondentRepository, EventDispatcher $event)
    {
        $this->respondentRepository = $respondentRepository;
        $this->event = $event;
    }

    public function getExistingPatients($ssn, $patientNr)
    {
        $existingPatients = null;
        $removeNewSsn = false;
        if ($ssn !== null) {
            $existingPatients = $this->respondentRepository->getPatientsFromSsn($ssn /*$this->currentEpd*/);
        }

        if ($existingPatients) {
            $deletedExistingPatient = false;
            foreach($existingPatients as $key=>$existingPatient) {
                if (isset($existingPatient['gr2o_patient_nr']) && $existingPatient['gr2o_patient_nr'] != $patientNr) {
                    if ($existingPatient['gor_epd'] == $this->currentEpd && $this->respondentRepository->patientNrExistsInEpd($patientNr, $this->currentEpd)) {

                        $respondentMergeEvent = new RespondentMergeEvent();
                        $respondentMergeEvent->setOldPatientNr($existingPatient['gr2o_patient_nr']);
                        $respondentMergeEvent->setNewPatientNr($patientNr);
                        $respondentMergeEvent->setSsn($ssn);
                        $respondentMergeEvent->setEpd($this->currentEpd);

                        if (!in_array($existingPatient['gr2o_id_user'], $this->mergeRespondents) && $deletedExistingPatient === false) {
                            if ($existingPatient['gr2o_reception_code'] === 'deleted') {
                                $respondentMergeEvent->setStatus('old-deleted');
                                $comment = $existingPatient['gr2o_comments'] .= sprintf("\nSSN %s removed in favor of patientnr %s", $ssn, $patientNr);
                                $this->respondentRepository->removeSsnFromRespondent($existingPatient['gr2o_id_user'], $comment);
                                $deletedExistingPatient = true;
                            } else {
                                $respondentMergeEvent->setStatus('new-ssn-removed');
                                $removeNewSsn = true;
                            }

                            $this->event->dispatch($respondentMergeEvent, 'respondent.merge');
                            $this->mergeRespondents[] = $existingPatient['gr2o_id_user'];
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

        if ($removeNewSsn) {
            $existingPatients['removeNewSsn'] = true;
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
