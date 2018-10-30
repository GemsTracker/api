<?php


namespace Pulse\Api\Model\Emma;


use Psr\Log\LoggerInterface;
use Pulse\Api\Model\ApiModelTranslator;
use Pulse\Validate\SimplePhpEmail;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Predicate\Predicate;
use Zend\Db\Sql\Sql;

class RespondentImportTranslator extends ApiModelTranslator
{
    /**
     * @var Adapter
     */
    protected $db;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var RespondentRepository
     */
    protected $respondentRepository;

    /**
     * @var array Api translations for the respondent
     */
    public $translations = [
        "grs_initials_name" => "initials_name",
        "grs_last_name" => "last_name",
        "grs_ssn" => "ssn",
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
    ];

    public function __construct(Adapter $db, RespondentRepository $respondentRepository, LoggerInterface $logger)
    {
        $this->db = $db;
        $this->logger = $logger;
        $this->respondentRepository = $respondentRepository;
        parent::__construct(null);
    }

    public function matchRowToExistingPatient($row)
    {
        if (array_key_exists('grs_ssn', $row) && $row['grs_ssn'] !== null) {
            if (is_string($row['grs_ssn']) && strlen($row['grs_ssn']) === 8) {
                $row['grs_ssn'] = '0' . $row['grs_ssn'];
            }

            $validator = new \MUtil_Validate_Dutch_Burgerservicenummer();

            if ($validator->isValid($row['grs_ssn'])) {
                $patients = $this->respondentRepository->getPatientsBySsn($row['grs_ssn']);

                foreach($patients as $patient) {
                    if ($patient['gr2o_id_organization'] == $row['gr2o_id_organization']) {
                        /*if ($patient['gr2o_patient_nr'] != $row['gr2o_patient_nr']) {
                            // A patient already exists under a different patient nr. We will overwrite this patient!
                            $row['grs_id_user'] = $row['gr2o_id_user'] = $patient['grs_id_user'];
                            $row['new'] = false;
                            return;
                        }*/
                        // A patient has been found, create a new user with the same respondent ID
                        $row['grs_id_user'] = $row['gr2o_id_user'] = $patient['grs_id_user'];
                        $row['new_respondent'] = false;
                        return $row;
                    }
                }

                $row['grs_id_user'] = $row['gr2o_id_user'] = $patient['grs_id_user'];
                $row['new_respondent'] = true;
                return $row;


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
        if ($patientId = $this->respondentRepository->getPatientId($row['gr2o_patient_nr'], $row['gr2o_id_organization'])) {
            $row['gr2o_id_user'] = $row['grs_id_user'] = $patientId;
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

    public function translateRow($row, $reversed=false)
    {
        $row = parent::translateRow($row, $reversed);

        //$row['gr2o_reception_code']  = \GemsEscort::RECEPTION_OK;


        $bsnComm = false;
        if (array_key_exists('grs_ssn', $row) && $row['grs_ssn'] !== null) {
            if (is_string($row['grs_ssn']) && strlen($row['grs_ssn']) === 8) {
                $row['grs_ssn'] = '0'.$row['grs_ssn'];
            }

            $validator = new \MUtil_Validate_Dutch_Burgerservicenummer();

            if ($validator->isValid($row['grs_ssn'])) {
                $ssnPatNr = $this->getPatientNrBySsn($row['grs_ssn']);

                if ($ssnPatNr && ($ssnPatNr != $row['gr2o_patient_nr'])) {
                    unset($row['grs_ssn']);
                    $bsnComm = "\nBSN removed, was duplicate of $ssnPatNr BSN.\n";
                }
            } else {
                $bsnComm = "\nBSN removed, " . $row['grs_ssn'] . " is not a valid BSN.\n";
                $row['grs_ssn'] = null;
            }
        }

        if ($bsnComm) {
            $this->logger->notice($bsnComm, ['patientNr' => $row['gr2o_patient_nr']]);
        }

        return $row;
    }
}