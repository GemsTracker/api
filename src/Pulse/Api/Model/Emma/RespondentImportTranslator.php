<?php


namespace Pulse\Api\Model\Emma;


use Gems\Rest\Model\ModelException;
use Gems\Rest\Model\ModelTranslateException;
use Psr\Log\LoggerInterface;
use Pulse\Api\Model\ApiModelTranslator;
use Pulse\Validate\SimplePhpEmail;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Sql\Predicate\Predicate;
use Laminas\Db\Sql\Sql;

class RespondentImportTranslator extends ApiModelTranslator
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var LoggerInterface
     */
    protected $respondentErrorLogger;

    /**
     * @var RespondentRepository
     */
    protected $respondentRepository;

    /**
     * @var array Api translations for the respondent
     */
    public $translations = [
        "grs_initials_name" => "initials_name",
        "grs_first_name" => "first_name",
        "grs_last_name" => "last_name",
        "grs_ssn" => "ssn",
        "grs_surname_prefix" => "surname_prefix",
        "grs_gender" => "gender",
        "grs_birthday" => "birthday",
        "grs_address_1" => "address",
        "grs_zipcode" => "zipcode",
        "grs_city" => "city",
        "grs_phone_1" => "phone_home",
        "grs_phone_3" => "phone_mobile",

        //"gr2o_id_organization" => "organization",
        "gr2o_email" => "email",
        "gr2o_patient_nr" => "patient_nr",
        "gr2o_epd_id" => "id",
    ];

    public function __construct(RespondentRepository $respondentRepository, LoggerInterface $logger, LoggerInterface $respondentErrorLogger)
    {
        $this->logger = $logger;
        $this->respondentErrorLogger = $respondentErrorLogger;
        $this->respondentRepository = $respondentRepository;
        parent::__construct(null);
    }

    public function matchRowToExistingPatient($row, \MUtil_Model_DatabaseModelAbstract $model)
    {
        if (array_key_exists('grs_ssn', $row) && $row['grs_ssn'] !== null) {
            if (is_string($row['grs_ssn']) && strlen($row['grs_ssn']) === 8) {
                $row['grs_ssn'] = '0' . $row['grs_ssn'];
            }

            $validator = new \MUtil_Validate_Dutch_Burgerservicenummer();

            if ($validator->isValid($row['grs_ssn'])) {
                if ($patients = $this->respondentRepository->getPatientsBySsn($row['grs_ssn'])) {

                    foreach ($patients as $patient) {
                        if ($patient['gr2o_id_organization'] == $row['gr2o_id_organization']) {
                            if ($patient['gr2o_patient_nr'] != $row['gr2o_patient_nr']) {
                                // A patient already exists under a different patient nr. We will overwrite this patient!
                                $copyId = $model->getKeyCopyName('gr2o_patient_nr');
                                if ($copyId) {
                                    $row[$copyId] = $patient['gr2o_patient_nr'];
                                }

                                // We also need to check if the patient already exists as the current organization, if so we might need to merge!
                                $altPatient = $this->respondentRepository->getPatient($row['gr2o_patient_nr'], $row['gr2o_id_organization']);
                                if ($altPatient) {
                                    // new patientnumber and organization combo also already exists.. we might have to merge!
                                    // CASE 5

                                    $message = 'Patient exists as two respondents in this organization in Pulse. One with the ssn, the other with the patient number';
                                    $context = [
                                        'patient1' => [
                                            'patientNr'     => $patient['gr2o_patient_nr'],
                                            'organization'  => $patient['gr2o_id_organization'],
                                            'respondentId'  => $patient['gr2o_id_user'],
                                            'ssn'           => $patient['grs_ssn'],
                                        ],
                                        'patient2' => [
                                            'patientNr'     => $altPatient['gr2o_patient_nr'],
                                            'organization'  => $altPatient['gr2o_id_organization'],
                                            'respondentId'  => $altPatient['gr2o_id_user'],
                                            'ssn'           => $altPatient['grs_ssn'],
                                        ],
                                    ];

                                    $this->logger->debug($message, $context);
                                    $this->respondentErrorLogger->error($message, $context);

                                    throw new ModelTranslateException(
                                        sprintf(
                                            "Patient nr %s already exists in organization %s but without SSN. SSN %s is already known in patient nr %s",
                                            $row['gr2o_patient_nr'], $row['gr2o_id_organization'],
                                            $row['grs_ssn'], $patient['gr2o_patient_nr']
                                        )
                                    );

                                    // For now remove ssn and merge into new, but we're going to have to merge the patient completely
                                    /*$row['grs_id_user'] = $row['gr2o_id_user'] = $altPatient['gr2o_id_user'];
                                    unset($row['grs_ssn']);
                                    $row['new_respondent'] = false;

                                    return $row;*/

                                }

                                //Patient is known with this ssn but not with this patient nr.
                                //Overwrite the current patient number, but log the instance
                                //CASE 4

                                $message = 'New patient imported, overwriting an existing Patient nr';
                                $context = [
                                    'existingPatient' => [
                                        'patientNr'     => $patient['gr2o_patient_nr'],
                                        'organization'  => $patient['gr2o_id_organization'],
                                        'respondentId'  => $patient['gr2o_id_user'],
                                        'ssn'           => $patient['grs_ssn'],
                                    ],
                                    'newPatient' => [
                                        'patientNr'     => $row['gr2o_patient_nr'],
                                    ],
                                ];
                                $this->logger->debug($message, $context);
                                $this->respondentErrorLogger->notice($message, $context);
                            }

                            // Patient is known with this ssn and patient nr. Just edit the patient

                            $row['grs_id_user'] = $row['gr2o_id_user'] = $patient['gr2o_id_user'];
                            $row['new_respondent'] = false;

                            return $row;
                        }
                    }

                    // SSN is not known to this Patient in this organisation, but does exist
                    $row['grs_id_user'] = $row['gr2o_id_user'] = $patient['gr2o_id_user'];
                    $altPatient = $this->respondentRepository->getPatient($row['gr2o_patient_nr'], $row['gr2o_id_organization']);
                    if ($altPatient) {
                        // Patient with this patient nr already exists!
                        // CASE 6
                        $row['grs_id_user'] = $row['gr2o_id_user'] = $altPatient['gr2o_id_user'];

                        $message = 'Patient 2 already exists without ssn, but ssn is already in use with patient 1. SSN will be removed.';
                        $context = [
                            'patient1' => [
                                'patientNr'     => $patient['gr2o_patient_nr'],
                                'organization'  => $patient['gr2o_id_organization'],
                                'respondentId'  => $patient['gr2o_id_user'],
                                'ssn'           => $patient['grs_ssn'],
                            ],
                            'patient2' => [
                                'patientNr'     => $altPatient['gr2o_patient_nr'],
                                'organization'  => $altPatient['gr2o_id_organization'],
                                'respondentId'  => $altPatient['gr2o_id_user'],
                                'ssn'           => $altPatient['grs_ssn'],
                            ],
                        ];

                        $this->logger->debug($message, $context);
                        $this->respondentErrorLogger->error($message, $context);

                        unset($row['grs_ssn']);

                        $row['new_respondent'] = false;
                        return $row;
                    }

                    // Patient is new in this organisation. We can safely merge
                    $row['new_respondent'] = true;
                    return $row;
                }

                $patient = $this->respondentRepository->getPatient($row['gr2o_patient_nr'], $row['gr2o_id_organization']);

                if (is_array($patient) && array_key_exists('grs_ssn', $patient) && $patient['grs_ssn'] !== null) {
                    // SSN doesn't exist but the patient number does in this organization! Save the new SSN

                    $row['grs_id_user'] = $row['gr2o_id_user'] = $patient['gr2o_id_user'];
                    $row['new_respondent'] = false;

                    return $row;

                    // SSN doesn't exist, but the patient number does in this organization! Something weird is going on here!
                    /*throw new ModelTranslateException(
                        sprintf(
                            "SSN %s doesn't exist, but the patient ID %s does exist in organization %s. Patient has not been saved!",
                            $row['grs_ssn'], $row['gr2o_patient_nr'], $row['gr2o_id_organization']));
                    return false;*/
                }
                /*if ($ssnPatNr && ($ssnPatNr != $row['gr2o_patient_nr'])) {
                    unset($row['grs_ssn']);
                    $bsnComm = "\nBSN removed, was duplicate of $ssnPatNr BSN.\n";
                }*/
            } else {
                $bsnComm = "\nBSN removed, " . $row['grs_ssn'] . " is not a valid BSN.\n";
                $this->logger->notice($bsnComm, ['patientNr' => $row['gr2o_patient_nr']]);
                $row['grs_ssn'] = null;
            }
        }

        // No BSN, see if the patient exists as Patient number
        if ($patient = $this->respondentRepository->getPatient($row['gr2o_patient_nr'], $row['gr2o_id_organization'])) {
            $row['gr2o_id_user'] = $row['grs_id_user'] = $patient['gr2o_id_user'];
            if (array_key_exists('grs_ssn', $row)) {
                unset($row['grs_ssn']);
            }
            $row['new_respondent'] = false;
        }

        return $row;
    }

    public function translateRowOnce($row)
    {
        $row = parent::translateRow($row, true);
        $row['grs_iso_lang'] = 'nl';
        $row['gr2o_readonly'] = 1;

        if (isset($row['deceased']) && $row['deceased'] === true) {
            $row['gr2o_reception_code'] = 'deceased';
        }

        if (array_key_exists('gr2o_email', $row) && $row['gr2o_email'] !== null) {
            $row['gr2o_email'] = trim($row['gr2o_email']);
            if ($row['gr2o_email'] === '') {
                $row['gr2o_email'] = null;
            }
            $validator = new \Pulse_Validate_SimplePhpEmail();
            if (!$validator->isValid($row['gr2o_email'])) {
                $this->logger->notice(sprintf('Email removed. Not a valid Email address'), ['patientNr' => $row['gr2o_patient_nr'], 'email' => $row['gr2o_email']]);
                $row['gr2o_email'] = null;
            }
        }

        if (!array_key_exists('grs_surname_prefix', $row)) {
            $row['grs_surname_prefix'] = null;
        }

        return $row;
    }
}
