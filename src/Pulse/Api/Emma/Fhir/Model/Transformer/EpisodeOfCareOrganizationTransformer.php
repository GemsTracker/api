<?php

declare(strict_types=1);


namespace Pulse\Api\Emma\Fhir\Model\Transformer;


use Gems\Rest\Exception\MissingDataException;
use Gems\Rest\Model\ModelException;
use Pulse\Api\Emma\Fhir\Repository\OrganizationRepository;

/**
 * Translate managingOrganization to organization ID
 */
class EpisodeOfCareOrganizationTransformer extends \MUtil_Model_ModelTransformerAbstract
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
        if (!isset($row['managingOrganization'], $row['managingOrganization']['reference'], $row['managingOrganization']['display']) || strpos($row['managingOrganization']['reference'], 'Organization/') !== 0) {
            throw new MissingDataException('No service provider set in Episode of care');
        }

        $organizationName = $row['managingOrganization']['display'];
        $organizationId = $this->organizationRepository->getOrganizationId($organizationName);
        if ($organizationId === null) {
            throw new ModelException(sprintf('Organization %s not found', $organizationName));
        }

        $locationName = $this->organizationRepository->getLocationFromOrganizationName($organizationName);
        $row['locationId'] = $this->organizationRepository->matchLocation($locationName, $organizationId, true);

        $row['gec_id_organization'] = $organizationId;

        return $row;
    }
}
