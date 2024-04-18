<?php

namespace Pulse\Api\Fhir\Model\Transformer;

use Ichom\Repository\Diagnosis2TreatmentRepository;
use Pulse\Api\Fhir\Repository\AppointmentMedicalCategoryRepository;
use Pulse\Api\Model\Emma\RespondentRepository;

class TreatmentAppointmentCarePlanTransformer extends \MUtil_Model_ModelTransformerAbstract
{
    protected $agenda;

    protected $appointmentCarePlanCode = 'treatmentAppointment';

    protected $carePlanStatusFilter = null;


    protected $diagnosis2TreatmentRepository;

    protected $db;

    protected $dbLookup;

    protected $loadAppointmentsAsCarePlans = true;

    protected $medicalCategoryFilter = null;

    protected $medicalCategoryOrganizations = [80];

    /**
     * @var array|null
     */
    protected $patientFilterData = null;

    /**
     * @var array|null
     */
    protected $sedations = null;

    protected $showMedicalCategories = false;

    protected $treatmentAppointmentOrganizations = [80, 79];

    protected $treatmentTrackCodes = ['procto'];
    private $appointmentMedicalCategoryRepository;

    public function __construct(
        \Zend_Db_Adapter_Abstract $db,
        \Pulse_Util_DbLookup $dbLookup,
        \Pulse_Agenda $agenda,
        Diagnosis2TreatmentRepository $diagnosis2TreatmentRepository,
        AppointmentMedicalCategoryRepository $appointmentMedicalCategoryRepository
    )
    {
        $this->agenda = $agenda;
        $this->diagnosis2TreatmentRepository = $diagnosis2TreatmentRepository;
        $this->db = $db;
        $this->dbLookup = $dbLookup;
        $this->appointmentMedicalCategoryRepository = $appointmentMedicalCategoryRepository;
    }

    public function transformFilter(\MUtil_Model_ModelAbstract $model, array $filter)
    {
        $this->checkForPatientInfo($filter);
        if (isset($filter['status'])) {
            $statusFilter = $filter['status'];

            if (!is_array($statusFilter)) {
                $statusFilter = [$statusFilter];
            }
            $this->carePlanStatusFilter = $statusFilter;
        }
        if (isset($filter['code'])) {
            $codes = $filter['code'];
            if (!is_array($codes)) {
                $codes = [$codes];
            }
            if (!in_array($this->appointmentCarePlanCode, $codes)) {
                $this->loadAppointmentsAsCarePlans = false;
            }
        }

        if (isset($filter['mcrtf.gr2t2f_value'])) {
            $this->medicalCategoryFilter = $filter['mcrtf.gr2t2f_value'];
            $this->showMedicalCategories = true;
        }
        return $filter;
    }

    protected function checkForPatientInfo($filter)
    {
        foreach($filter as $key => $filterPart) {
            if (is_numeric($key) && is_array($filterPart) && isset($filterPart[0]['gr2o_patient_nr'])) {
                $pairs = [];
                foreach($filterPart as $patientIdPair) {
                    if (isset($patientIdPair['gr2o_id_organization']) && in_array($patientIdPair['gr2o_id_organization'], $this->treatmentAppointmentOrganizations)) {
                        $pairs[] = $patientIdPair;
                    }
                }
                if (count($pairs)) {
                    $this->patientFilterData = $pairs;
                }
                break;
            }
        }
    }

    public function transformLoad(\MUtil_Model_ModelAbstract $model, array $data, $new = false, $isPostData = false)
    {
        if ($this->patientFilterData === null) {
            return $data;
        }

        if (!$this->hasTreatmentOrganizations()) {
            return $data;
        }

        if (!$this->loadAppointmentsAsCarePlans) {
            return $data;
        }

        $treatmentAppointments = $this->getTreatmentAppointments();

        $trackTreatmentAppointments = $this->getTrackTreatmentAppointmentIds($data);

        foreach ($treatmentAppointments as $treatmentAppointment) {
            // Skip treatment Appointments already linked to a track as treatment appointment
            if (in_array($treatmentAppointment['gap_id_appointment'], $trackTreatmentAppointments)) {
                continue;
            }
            $carePlan = $this->getCarePlanFromTreatmentAppointment($treatmentAppointment);
            $data[] = $carePlan;
        }

        return $data;
    }

    protected function getCarePlanFromTreatmentAppointment($treatmentAppointment)
    {
        $treatmentName = $this->getTreatmentName($treatmentAppointment['pa2t_id_treatment']);

        $sedationId = $this->getSedationFromAppointmentActivity($treatmentAppointment['gaa_name']);


        $diagnosisId = $this->getDiagnosisFromAppointmentActivity($treatmentAppointment['gaa_name']);

        $combinedPatientId = $treatmentAppointment['gr2o_patient_nr'] . '@' . $treatmentAppointment['gap_id_organization'];

        $treatmentDate = $this->getTreatmentDate($treatmentAppointment['gap_admission_time']);

        $carePlan = [
            'id' => 'A' . $treatmentAppointment['gap_id_appointment'],
            'gr2t_created' => $treatmentAppointment['gap_created'],
            'title' => $treatmentName,
            'code' => $this->appointmentCarePlanCode,
            'resourceType' => 'CarePlan',
            'status' => $this->getStatus($treatmentAppointment['gap_status']),
            'staffOnly' => false,
            'subject' => [
                'id' => $combinedPatientId,
                'reference' => 'fhir/patient/' . $combinedPatientId,
                'display' => '',
            ],
            'contributor' => [
                [
                    'id' => $treatmentAppointment['gap_id_organization'],
                    'reference' => 'fhir/organization/' . $treatmentAppointment['gap_id_organization'],
                    'display' => $this->getOrganizationName($treatmentAppointment['gap_id_organization']),
                ]
            ],
            'period' => [
                'start' => $treatmentAppointment['gap_admission_time'],
                'end' => null,
            ],
            'supportingInfo' => [
                [
                    'name' => 'Behandeling',
                    'value' => $treatmentAppointment['pa2t_id_treatment'],
                    'display' => $treatmentName,
                    'code' => 'treatment',
                ],
                [
                    'name' => 'Behandelaar',
                    'value' => $treatmentAppointment['gap_id_attended_by'],
                    'display' => $this->getPhysicianName($treatmentAppointment['gap_id_attended_by']),
                    'code' => 'physician',
                ],
                [
                    'name' => 'Behandeldatum',
                    'value' => $treatmentDate,
                    'code' => 'treatmentdate',
                ],
                [
                    'name' => 'Vestiging',
                    'value' => $treatmentAppointment['gap_id_location'],
                    'display' => $this->getLocationName($treatmentAppointment['gap_id_location']),
                    'code' => 'location',
                ],
                [
                    'name' => 'Behandel afspraak',
                    'type' => 'appointmentField',
                    'value' => [
                        'type' => 'Appointment',
                        'id' => $treatmentAppointment['gap_id_appointment'],
                        'reference' => 'fhir/appointment/' . $treatmentAppointment['gap_id_appointment'],
                    ],
                    'code' => 'treatmentAppointment',
                ],
                [
                    'name' => 'Verdoving',
                    'value' => $sedationId,
                    'display' => $this->getSedationName($sedationId),
                    'code' => 'sedation',
                ]

            ],
        ];

        if ($diagnosisId) {
            $carePlan['supportingInfo'][] = [
                'name' => 'Diagnose',
                'value' => $diagnosisId,
                'display' => $this->getDiagnosisName($diagnosisId),
                'code' => 'diagnosis',
            ];
        }

        if ($this->showMedicalCategories && in_array($treatmentAppointment['gap_id_organization'], $this->medicalCategoryOrganizations)) {
            $medicalCategoryId = $this->appointmentMedicalCategoryRepository->getMedicalCategoryFromAppointmentActivityId($treatmentAppointment['gap_id_activity'], $treatmentAppointment['gap_id_organization']);
            $carePlan['supportingInfo'][] = [
                'name' => 'medicalCategory',
                'value' => $medicalCategoryId,
                'display' => $this->appointmentMedicalCategoryRepository->getMedicalCategoryName($medicalCategoryId),
                'code' => 'medicalCategory',
            ];
        }

        return $carePlan;
    }

    protected function getDiagnosisFromAppointmentActivity($activityName)
    {
        $select = $this->db->select();
        $select->from('pulse__activity2diagnosis', ['pa2d_id_diagnosis'])
            ->where('pa2d_active = 1')
            ->where(new \Zend_Db_Expr("'$activityName' LIKE `pa2d_activity`"));

        $result = $this->db->fetchOne($select);
        if ($result) {
            return $result;
        }
        return null;
    }

    protected function getDiagnosisName($diagnosisId)
    {
        $diagnoses = $this->diagnosis2TreatmentRepository->getAllDiagnoses();
        if (isset($diagnoses[$diagnosisId])) {
            return $diagnoses[$diagnosisId];
        }
        return null;
    }

    protected function getLocationName($locationId)
    {
        $locations = $this->agenda->getLocations();
        if (isset($locations[$locationId])) {
            return $locations[$locationId];
        }
        return null;
    }

    protected function getOrganizationName($organizationId)
    {
        $organizations = $this->dbLookup->getOrganizations();
        if (isset($organizations[$organizationId])) {
            return $organizations[$organizationId];
        }
        return null;
    }

    protected function getPatientsWhere()
    {
        $patientPairOptions = [];
        foreach($this->patientFilterData as $patientsPair) {
            if (!isset($patientsPair['gr2o_patient_nr'], $patientsPair['gr2o_id_organization'])) {
                continue;
            }
            $patientPairOptions[] = sprintf('(gr2o_patient_nr = \'%s\' AND gr2o_id_organization = %d)', $patientsPair['gr2o_patient_nr'], $patientsPair['gr2o_id_organization']);
        }
        return '(' . join(' OR ', $patientPairOptions) . ')';
    }

    protected function getPhysicianName($physicianId)
    {
        $physicians = $this->agenda->getHealthcareStaff();
        if (isset($physicians[$physicianId])) {
            return $physicians[$physicianId];
        }
        return null;
    }

    protected function getSedationFromAppointmentActivity($activityName)
    {
        if (!$this->sedations) {
            $this->sedations = $this->getSedationsInfo();
        }
        $activitySedations = array_column($this->sedations, null, 'pa2s_activity');
        if (isset($activitySedations[$activityName])) {
            return $activitySedations[$activityName]['pse_id_sedation'];
        }
        return null;
    }

    protected function getSedationName($sedationId)
    {
        if (!$this->sedations) {
            $this->sedations = $this->getSedationsInfo();
        }
        $sedations = array_column($this->sedations, 'pse_name', 'pse_id_sedation');
        if (isset($sedations[$sedationId])) {
            return $sedations[$sedationId];
        }
        return null;
    }

    protected function getSedationsInfo()
    {
        $select = $this->db->select();
        $select->from('pulse__activity2sedation', ['pa2s_activity'])
            ->join('pulse__sedations', 'pa2s_id_sedation = pse_id_sedation', ['pse_id_sedation', 'pse_name'])
            ->where('pse_active = 1');

        return $this->db->fetchAll($select);
    }

    public function getStatus($appointmentStatus)
    {
        switch($appointmentStatus) {
            case 'AC':
                return 'active';
            case 'CO':
                return 'completed';
            case 'CA':
            case 'AB':
                return 'revoked';
            default:
                return 'unknown';
        }
    }

    protected function getTrackTreatmentAppointmentIds($data)
    {
        $trackTreatmentAppointments = [];
        foreach($data as $key => $row) {
            if (!isset($row['gtr_code']) || !in_array($row['gtr_code'], $this->treatmentTrackCodes)) {
                continue;
            }
            if (isset($row['supportingInfo'])) {
                foreach($row['supportingInfo'] as $infoItem) {
                    if (isset($infoItem['code']) && $infoItem['code'] === 'treatmentAppointment') {
                        $trackTreatmentAppointments[] = $infoItem['value']['id'];
                    }
                }
            }
        }

        return $trackTreatmentAppointments;
    }

    protected function getTreatmentDate($treatmentDate)
    {
        if ($treatmentDate === null) {
            return null;
        }

        $date = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $treatmentDate);
        if ($date) {
            return $date->format('Y-m-d');
        }
        return null;
    }

    public function getTreatmentName($treatmentId)
    {
        $treatments = $this->diagnosis2TreatmentRepository->getAllTreatments();
        if (isset($treatments[$treatmentId])) {
            return $treatments[$treatmentId];
        }
        return null;
    }
    public function getTreatmentAppointments()
    {
        $select = $this->db->select();
        $select->from('gems__appointments')
            ->join('gems__respondent2org', 'gr2o_id_user = gap_id_user AND gr2o_id_organization = gap_id_organization')
            ->join('gems__agenda_activities', 'gap_id_activity = gaa_id_activity')
            ->join('pulse__activity2treatment', 'pa2t_activity = gaa_name')
            ->where($this->getPatientsWhere())
            ->where('pa2t_id_treatment > 70')
            ->where('pa2t_active = 1');

        if ($this->carePlanStatusFilter) {
            $allowedAppointmentStatuses = [];
            foreach($this->carePlanStatusFilter as $status) {
                if ($status === 'active') {
                    $allowedAppointmentStatuses[] = 'AC';
                    $allowedAppointmentStatuses[] = 'CO';
                }
                if ($status === 'revoked') {
                    $allowedAppointmentStatuses[] = 'CA';
                    $allowedAppointmentStatuses[] = 'AB';
                }
            }
            if (!empty($allowedAppointmentStatuses)) {
                $select->where('gap_status IN (?)', $allowedAppointmentStatuses);
            }
        }

        if ($this->medicalCategoryFilter) {
            $agendaActivities = $this->getActivitiesForMedicalCategories($this->medicalCategoryFilter);
            $select->where('gap_id_activity in (?)', $agendaActivities);
        }

        return $this->db->fetchAll($select);
    }

    protected function getActivitiesForMedicalCategories($medicalCategoryIds)
    {
        $activityIds = [];
        foreach($this->medicalCategoryOrganizations as $medicalCategoryOrganizationId) {
            foreach($medicalCategoryIds as $medicalCategoryId) {
                $activityIds = array_merge($activityIds, $this->appointmentMedicalCategoryRepository->getActivityIdsPerMedicalCategory($medicalCategoryId, $medicalCategoryOrganizationId));
            }
        }

        return $activityIds;
    }

    public function hasTreatmentOrganizations(): bool
    {
        foreach($this->patientFilterData as $patientIdPair) {
            if (isset($patientIdPair['gr2o_id_organization']) && in_array($patientIdPair['gr2o_id_organization'], $this->treatmentAppointmentOrganizations)) {
                return true;
            }
        }
        return false;
    }
}