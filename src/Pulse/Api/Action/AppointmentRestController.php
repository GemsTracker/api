<?php


namespace Pulse\Api\Action;


use Gems\Rest\Action\ModelRestController;
use Psr\Http\Message\ServerRequestInterface;
use Zalt\Loader\ProjectOverloader;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Sql;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Expressive\Helper\UrlHelper;

class AppointmentRestController extends ModelRestController
{

    /**
     * @var \Gems_Agenda
     */
    protected $agenda;

    protected $apiNames = [
        'gr2o_patient_nr' => 'patient_nr',
        'gap_id_organization' => 'organization_id',
        'gap_status' => 'status',
        'gap_admission_time' => 'admission_time',
        'gap_discharge_time' => 'discharge_time',
    ];

    /**
     * @var Adapter
     */
    protected $db;

    public function __construct(ProjectOverloader $loader, UrlHelper $urlHelper, Adapter $db, $LegacyDb, $LegacyLoader)
    {
        $this->agenda = $LegacyLoader->getAgenda();
        $this->db = $db;
        parent::__construct($loader, $urlHelper, $LegacyDb);
    }

    protected function getPatientId($patientNr, $organizationId)
    {
        $sql = new Sql($this->db);
        $select = $sql->select();
        $select->from('gems__respondent2org')
            ->columns(['gr2o_id_user'])
            ->where(
                [
                    'gr2o_patient_nr' => $patientNr,
                    'gr2o_id_organization' => $organizationId,
                ]
            );

        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();

        $firstRow = $result->current();

        return $firstRow['gr2o_id_user'];

    }

    public function saveRow(ServerRequestInterface $request, $row)
    {
        $row['gap_source'] = $this->userName;
        $row['gap_code'] = 'A';

        if (isset($row['gr2o_patient_nr'], $row['gap_id_organization'])) {
            $userId = $this->getPatientId($row['gr2o_patient_nr'], $row['gap_id_organization']);
            if ($userId === null) {
                return new JsonResponse(['error' => 'missing_data', 'message' => 'user could not be found']);
            }
            $row['gap_id_user'] = $userId;
        } else {
            return new JsonResponse(['error' => 'missing_data', 'message' => 'patient number and or organization id not found']);
        }

        if (isset($row['activity'])) {
            $this->row['gap_id_activity'] = $this->agenda->matchActivity($row['activity'], $row['gap_id_organization']);
        }
        if (isset($row['procedure'])) {
            $this->row['gap_id_procedure'] = $this->agenda->matchProcedure($row['procedure'], $row['gap_id_organization']);
        }
        if (isset($row['location'])) {
            $this->row['gap_id_location'] = $this->agenda->matchLocation($row['location'], $row['gap_id_organization']);
        }
        if (isset($row['caretaker'])) {
            $this->row['gap_id_attended_by'] = $this->agenda->matchLocation($row['caretaker'], $row['gap_id_organization']);
        }


        return parent::saveRow($request, $row);
    }
}