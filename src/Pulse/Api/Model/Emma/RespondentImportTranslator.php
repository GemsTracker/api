<?php


namespace Pulse\Api\Model\Emma;


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
                                $row[$copyId] = $patient['gr2o_patient_nr'];

                                // We also need to check if the patient already exists as the current organization, if so we might need to merge!
                                $altPatient = $this->respondentRepository->getPatient($row['gr2o_patient_nr'], $row['gr2o_id_organization']);
                                if ($altPatient) {
                                    // new patientnumber and organization combo also already exists.. we might have to merge!

                                    $message = 'Respondent exists as two respondents in Pulse';
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

                                    exit;

                                    // For now remove ssn and merge into new, but we're going to have to merge the patient completely
                                    $row['grs_id_user'] = $row['gr2o_id_user'] = $altPatient['gr2o_id_user'];
                                    unset($row['grs_ssn']);
                                    $row['new_respondent'] = false;

                                    return $row;

                                }
                            }

                            $row['grs_id_user'] = $row['gr2o_id_user'] = $patient['gr2o_id_user'];
                            $row['new_respondent'] = false;

                            return $row;
                        }
                    }

                    $row['grs_id_user'] = $row['gr2o_id_user'] = $patient['gr2o_id_user'];
                    $altPatient = $this->respondentRepository->getPatient($row['gr2o_patient_nr'], $row['gr2o_id_organization']);
                    if ($altPatient) {
                        $row['grs_id_user'] = $row['gr2o_id_user'] = $altPatient['gr2o_id_user'];
                        unset($row['grs_ssn']);
                    }

                    $row['new_respondent'] = true;
                    return $row;
                }

                $patient = $this->respondentRepository->getPatient($row['gr2o_patient_nr'], $row['gr2o_id_organization']);

                if (is_array($patient) && array_key_exists('grs_ssn', $patient) && $patient['grs_ssn'] !== null) {
                    // SSN doesn't exist, but the patient number does in this organization! Something weird is going on here!
                    throw new ModelTranslateException(
                        sprintf(
                            "SSN %s doesn't exist, but the patient ID %s does exist in organization %s. Patient has not been saved!",
                            $row['grs_ssn'], $row['gr2o_patient_nr'], $row['gr2o_id_organization']));
                    return false;
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

        return $row;
    }
}
