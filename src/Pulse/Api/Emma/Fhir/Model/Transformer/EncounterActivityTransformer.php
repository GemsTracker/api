<?php

declare(strict_types=1);


namespace Pulse\Api\Emma\Fhir\Model\Transformer;


use Pulse\Api\Emma\Fhir\Repository\AgendaActivityRepository;

class EncounterActivityTransformer extends \MUtil_Model_ModelTransformerAbstract
{
    /**
     * @var AgendaActivityRepository
     */
    protected $activityRepository;

    public function __construct(AgendaActivityRepository $activityRepository)
    {
        $this->activityRepository = $activityRepository;
    }

    public function transformRowBeforeSave(\MUtil_Model_ModelAbstract $model, array $row)
    {
        if (isset($row['gap_id_activity'], $row['existingOrganizationId']) && $row['existingOrganizationId'] === 81 && $row['gap_id_organization'] !== 81) {
            $row['gap_id_activity'] = $this->activityRepository->changeActivityOrganization($row['gap_id_activity'], $row['gap_id_organization']);
        }
        return $row;
    }
}
