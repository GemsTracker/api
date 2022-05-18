<?php

declare(strict_types=1);


namespace Pulse\Api\Emma\Fhir\Model\Transformer;


use Pulse\Api\Emma\Fhir\Repository\AgendaActivityRepository;
use Pulse\Api\Emma\Fhir\Repository\EscrowOrganizationRepository;

class EncounterActivityTransformer extends \MUtil_Model_ModelTransformerAbstract
{
    /**
     * @var AgendaActivityRepository
     */
    protected $activityRepository;
    /**
     * @var EscrowOrganizationRepository
     */
    protected $escrowOrganizationRepository;

    public function __construct(AgendaActivityRepository $activityRepository, EscrowOrganizationRepository $escrowOrganizationRepository)
    {
        $this->activityRepository = $activityRepository;
        $this->escrowOrganizationRepository = $escrowOrganizationRepository;
    }

    public function transformRowBeforeSave(\MUtil_Model_ModelAbstract $model, array $row)
    {

        if (isset($row['gap_id_activity'], $row['existingOrganizationId'])
            && $row['existingOrganizationId'] === $this->escrowOrganizationRepository->getId()
            && $row['gap_id_organization'] !== $this->escrowOrganizationRepository->getId()) {

            $row['gap_id_activity'] = $this->activityRepository->changeActivityOrganization($row['gap_id_activity'], $row['gap_id_organization']);
        }
        return $row;
    }
}
