<?php


namespace Pulse\Api\Action;


use Gems\Rest\Action\ModelRestController;
use Gems\Rest\Model\ModelProcessor;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zalt\Loader\ProjectOverloader;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Sql;
use Zend\Diactoros\Response\EmptyResponse;
use Zend\Expressive\Helper\UrlHelper;

class RespondentBulkRestController extends ModelRestController
{
    protected $apiNames = [
        "grs_initials_name" => "initials_name",
        "grs_last_name" => "last_name",
        "grs_ssn" => "ssn",
        "grs_gender" => "gender",
        "grs_birthday" => "birthday",
        "grs_address" => "address",
        "grs_zipcode" => "zipcode",
        "grs_city" => "city",
        "grs_phone_1" => "phone_home",
        "grs_phone_3" => "phone_mobile",

        //"gr2o_id_organization" => "organization",
        "gr2o_email" => "email",
        "gr2o_patient_nr" => "patient_nr",
    ];

    /**
     * @var Adapter
     */
    protected $db;

    protected $organizations;

    public function __construct(ProjectOverloader $loader, UrlHelper $urlHelper, Adapter $db, $LegacyDb)
    {
        $this->db = $db;
        parent::__construct($loader, $urlHelper, $LegacyDb);
    }

    public function post(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        if ($this->checkContentType($request) === false) {
            return new EmptyResponse(415);
        }

        $respondentRow = json_decode($request->getBody()->getContents(), true);

        if (empty($respondentRow)) {
            return new EmptyResponse(400);
        }

        // For now unset the submodels
        unset($respondentRow['episodes']);
        unset($respondentRow['appointments']);

        $row = $this->translateRow($respondentRow, true);

        $row['gr2o_reception_code']  = \GemsEscort::RECEPTION_OK;
        $row['grs_iso_lang'] = 'nl';
        $row['gr2o_readonly'] = 1;

        if (isset($row['deceased']) && $row['deceased'] === true) {
            $row['gr2o_reception_code'] = 'deceased';
        }

        if ($row['grs_ssn']) {
            $bsnComm = false;
            $validator = new \MUtil_Validate_Dutch_Burgerservicenummer();

            if ($validator->isValid($row['grs_ssn'])) {
                $ssnPatNr = $this->getPatientNrBySsn();

                if ($ssnPatNr && ($ssnPatNr != $row['gr2o_patient_nr'])) {
                    unset($row['grs_ssn']);
                    $bsnComm = "\nBSN removed, was duplicate of $ssnPatNr BSN.\n";
                }
            } else {
                $bsnComm = "\nBSN removed, " . $row['grs_ssn'] . " is not a valid BSN.\n";
                unset($row['grs_ssn']);
            }
        }

        $organizations = $this->getOrganizations($row['organizations']);

        foreach($organizations as $organizationId => $organizationName) {
            $row['gr2o_id_organization'] = $organizationId;
            $processor = new ModelProcessor($this->loader, $this->model, $this->userId);
            $new = true;
            if ($patientId = $this->getPatientId($row['gr2o_patient_nr'], $organizationId)) {
                $new = false;
                $row['gr2o_id_user'] = $row['grs_id_user'] = $patientId;
            }
            $this->model->applyEditSettings($new);

            try {
                $processor->save($row, $new);
            } catch(\Exception $e) {
                // Row could not be saved.
                // return JsonResponse
            }
        }

        // Return the route as a link in the header, like in ModelRestControllerAbstract->saveRow()

        return new EmptyResponse(201);
    }


    protected function getPatientId($patientNr, $organizationId)
    {
        $sql = new Sql($this->db);
        $select = $sql->select();
        $select->from('gems__respondent2org')
            ->columns(['gr2o_id_user', 'gr2o_patient_nr', 'gr2o_id_organization'])
            ->where(['gr2o_patient_nr' => $patientNr, 'gr2o_id_organization' => $organizationId]);
        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();

        if ($result->count() > 0) {
            $user = $result->current();
            return $user['gr2o_id_user'];
        }
        return false;
    }

    protected function getPatientNrBySsn($ssn)
    {
        $sql = new Sql($this->db);
        $select = $sql->select();
        $select->from('gems__respondent2org')
            ->join('gems__respondents', 'grs_id_user = gr2o_id_user')
            ->columns(['grs_ssn', 'gr2o_patient_nr'])
            ->where(['grs_ssn' => $ssn]);
        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();

        if ($result->count() > 0) {
            $user = $result->current();
            return $user['gr2o_patient_nr'];
        }
        return false;
    }



    protected function getLocalOrganizations()
    {
        if (!$this->organizations) {
            $sql = new Sql($this->db);
            $select = $sql->select();
            $select->from('gems__organizations')
                ->columns(['gor_id_organization', 'gor_name'])
                ->where(['gor_active' => 1]);

            $statement = $sql->prepareStatementForSqlObject($select);
            $result = $statement->execute();

            $organizations = iterator_to_array($result);
            $filteredOrganizations = [];
            foreach ($organizations as $organization) {
                $filteredOrganizations[$organization['gor_id_organization']] = $organization['gor_name'];
            }

            $this->organizations = $filteredOrganizations;
        }

        return $this->organizations;
    }

    protected function getOrganizations($organizations)
    {
        $localOrganizations = $this->getLocalOrganizations();

        $organizationIds = [];
        foreach($organizations as $organization) {
            $organization = strtolower(trim($organization));
            foreach($localOrganizations as $organizationId => $localOrganization) {
                $localOrganizationCompare = strtolower(trim($localOrganization));
                if (strpos($organization, $localOrganizationCompare) !== false) {
                    $organizationIds[$organizationId] = $localOrganization;
                }
            }
        }

        return $organizationIds;
    }
}