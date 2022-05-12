<?php

declare(strict_types=1);


namespace Pulse\Api\Emma\Fhir\Model\Transformer;


use Gems\Rest\Exception\MissingDataException;
use Gems\Rest\Model\ModelException;
use Pulse\Api\Emma\Fhir\Repository\OrganizationRepository;

/**
 * Get organization from service provider
 */
class EncounterOrganizationTransformer extends \MUtil_Model_ModelTransformerAbstract
{
    /**
     * @var OrganizationRepository
     */
    protected $organizationRepository;

    public function __construct(OrganizationRepository $organizationRepository)
    {
        $this->organizationRepository = $organizationRepository;
    }

    public function transformRowBeforeSave(\MUtil_Model_ModelAbstract $model, array $row)
    {
        if (!isset($row['serviceProvider'], $row['serviceProvider']['reference'], $row['serviceProvider']['display']) || strpos($row['serviceProvider']['reference'], 'Organization/') !== 0) {
            throw new MissingDataException('No service provider set in Encounter');
        }

        $organizationName = $row['serviceProvider']['display'];
        $row['gap_id_organization'] = $this->organizationRepository->getOrganizationId($organizationName);

        if ($row['gap_id_organization'] === null) {
            throw new ModelException(sprintf('Organization %s not found', $organizationName));
        }

        $locationName = $this->organizationRepository->getLocationFromOrganizationName($organizationName);
        $row['gap_id_location'] = $this->organizationRepository->matchLocation($locationName, $row['gap_id_organization'], true);

        return $row;
    }
}
