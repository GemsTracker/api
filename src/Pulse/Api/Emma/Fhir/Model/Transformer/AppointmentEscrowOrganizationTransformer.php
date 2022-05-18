<?php

declare(strict_types=1);


namespace Pulse\Api\Emma\Fhir\Model\Transformer;


use Gems\Rest\Exception\MissingDataException;
use Pulse\Api\Emma\Fhir\Repository\AppointmentRepository;
use Pulse\Api\Emma\Fhir\Repository\EscrowOrganizationRepository;

/**
 * If no organization is known, put it in the escrow organization
 */
class AppointmentEscrowOrganizationTransformer extends \MUtil_Model_ModelTransformerAbstract
{
    /**
     * @var EscrowOrganizationRepository
     */
    protected $escrowOrganizationRepository;

    public function __construct(EscrowOrganizationRepository $escrowOrganizationRepository)
    {
        $this->escrowOrganizationRepository = $escrowOrganizationRepository;
    }

    public function transformRowBeforeSave(\MUtil_Model_ModelAbstract $model, array $row)
    {
        if (!isset($row['gap_id_organization'])) {
            $row['gap_id_organization'] = $this->escrowOrganizationRepository->getId();
        }

        return $row;
    }
}
