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

    public function __construct(Adapter $db, LoggerInterface $logger)
    {
        $this->db = $db;
        $this->logger = $logger;
        parent::__construct(null);
    }

    protected function getPatientNrBySsn($ssn)
    {
        $sql = new Sql($this->db);
        $select = $sql->select();
        $select->from('gems__respondent2org')
            ->join('gems__respondents', 'grs_id_user = gr2o_id_user')
            ->columns(['gr2o_patient_nr'])
            ->where(['grs_ssn' => $ssn,]);
            /*->where($select->where->notEqualTo(
                'gr2o_patient_nr',
                'gr2o_patient_nr2',
                Predicate::TYPE_IDENTIFIER,
                Predicate::TYPE_IDENTIFIER
            ));*/
        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();

        if ($result->valid()) {
            $user = $result->current();
            return $user['gr2o_patient_nr'];
        }
        return false;
    }

    public function translateRow($row, $reversed=false)
    {
        $row = parent::translateRow($row, $reversed);

        $row['gr2o_reception_code']  = \GemsEscort::RECEPTION_OK;
        $row['grs_iso_lang'] = 'nl';
        $row['gr2o_readonly'] = 1;

        if (isset($row['deceased']) && $row['deceased'] === true) {
            $row['gr2o_reception_code'] = 'deceased';
        }

        if (isset($row['gr2o_email']) && $row['gr2o_email']) {
            $validator = new SimplePhpEmail();
            if (!$validator->isValid($row['gr2o_email'])) {
                $this->logger->notice(sprintf('Email removed. Not a valid Email address'), ['patientNr' => $row['gr2o_patient_nr'], 'email' => $row['gr2o_email']]);
                $row['gr2o_email'] = null;
            }
        }

        $bsnComm = false;
        if (isset($row['grs_ssn']) && $row['grs_ssn']) {
            if (strlen($row['grs_ssn']) === 8) {
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