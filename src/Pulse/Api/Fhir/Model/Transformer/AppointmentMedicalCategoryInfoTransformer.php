<?php

namespace Pulse\Api\Fhir\Model\Transformer;

use Pulse\Api\Fhir\Repository\AppointmentMedicalCategoryRepository;

class AppointmentMedicalCategoryInfoTransformer extends \MUtil_Model_ModelTransformerAbstract
{
    protected $medicalCategoryOrganizations = [80];
    protected AppointmentMedicalCategoryRepository $appointmentMedicalCategoryRepository;

    protected $showMedicalCategory = false;

    public function __construct(
        AppointmentMedicalCategoryRepository $appointmentMedicalCategoryRepository
    )
    {
        $this->appointmentMedicalCategoryRepository = $appointmentMedicalCategoryRepository;
    }

    public function transformFilter(\MUtil_Model_ModelAbstract $model, array $filter)
    {
        $medicalCategoryFilter = null;
        if (isset($filter['medical-category'])) {
            $medicalCategoryFilter = $filter['medical-category'];
            unset($filter['medical-category']);

        }
        if (isset($filter['medicalCategory'])) {
            $medicalCategoryFilter = $filter['medicalCategory'];
            unset($filter['medicalCategory']);
        }

        if ($medicalCategoryFilter === null) {
            return $filter;
        }
        if (!is_array($medicalCategoryFilter)) {
            $medicalCategoryFilter = [$medicalCategoryFilter];
        }

        $organizationIds = [];
        foreach($filter as $key => $filterPart) {
            if (is_numeric($key) && is_array($filterPart) && isset($filterPart[0]['gr2o_id_organization'])) {
                foreach($filterPart as $patientIdPair) {
                    if (in_array($patientIdPair['gr2o_id_organization'], $this->medicalCategoryOrganizations)) {
                        $organizationIds[] = $patientIdPair['gr2o_id_organization'];
                    }
                }
            }
        }
        if (!count($organizationIds)) {
            return $filter;
        }

        $appointmentActivityIds = [];
        foreach($medicalCategoryFilter as $medicalCategoryId) {
            foreach($organizationIds as $organizationId) {
                $appointmentActivityIds = array_merge($appointmentActivityIds, $this->appointmentMedicalCategoryRepository->getActivityIdsPerMedicalCategory($medicalCategoryId, $organizationId));
            }
        }

        if (!$appointmentActivityIds) {
            $appointmentActivityIds = [0];
        }

        $this->showMedicalCategory = true;


        $filter['gap_id_activity'] = $appointmentActivityIds;


        return $filter;
    }

    public function transformLoad(\MUtil_Model_ModelAbstract $model, array $data, $new = false, $isPostData = false)
    {
        foreach($data as $key => $row) {
            if (!$this->showMedicalCategory) {
                continue;
            }

            // no organization or activity known? skip!
            if (!isset($row['gap_id_organization'], $row['gap_id_activity'])) {
                continue;
            }
            // is this an organization we want to add medical categories to?
            if (!in_array($row['gap_id_organization'], $this->medicalCategoryOrganizations)) {
                continue;
            }

            $medicalCategoryId = $this->appointmentMedicalCategoryRepository->getMedicalCategoryFromAppointmentActivityId($row['gap_id_activity'], $row['gap_id_organization']);
            $medicalCategoryInfo = [
                'type' => 'medicalCategory',
                'identifier' => $medicalCategoryId,
            ];
            if ($medicalCategoryName = $this->appointmentMedicalCategoryRepository->getMedicalCategoryName($medicalCategoryId)) {
                $medicalCategoryInfo['display'] = $medicalCategoryName;
            }

            $data[$key]['info'][] = $medicalCategoryInfo;
        }
        return $data;
    }
}