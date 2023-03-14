<?php

namespace Pulse\Api\Emma\Fhir\Model\Transformer;


use Gems\Rest\Exception\MissingDataException;
use Gems\Rest\Model\ModelException;
use Pulse\Api\Emma\Fhir\Repository\EpdRepository;
use Pulse\Api\Emma\Fhir\Repository\OrganizationRepository;
use Pulse\Api\Repository\RespondentRepository;

/**
 *  Patient participant to respondent ID
 */
class AppointmentOrganizationTransformer extends \MUtil_Model_ModelTransformerAbstract
{
    /**
     * @var OrganizationRepository
     */
    protected $organizationRepository;

    public function __construct(OrganizationRepository $organizationRepository)
    {
        $this->organizationRepository = $organizationRepository;
    }

    public function transformRowBeforeSave(\MUtil_Model_ModelAbstract $model, array $row): array
    {
        if (!isset($row['participant']) || !is_array($row['participant'])) {
            throw new MissingDataException('No organization found as participant');
        }

        $organizationParticipant = false;
        foreach($row['participant'] as $participant) {
            if (isset($participant['actor'], $participant['actor']['reference']) && strpos($participant['actor']['reference'], 'Location/') === 0) {
                //$epdId = str_replace('Patient/', '', $participant['actor']['reference']);
                $organizationName = $participant['actor']['display'];

                $row['gap_id_organization'] = $this->organizationRepository->getOrganizationId($organizationName);

                if ($row['gap_id_organization'] === null) {
                    throw new ModelException(sprintf('Organization %s not found', $organizationName));
                }

                $locationName = $this->organizationRepository->getLocationFromOrganizationName($organizationName);
                $row['gap_id_location'] = $this->organizationRepository->matchLocation($locationName, $row['gap_id_organization'], true);
                $organizationParticipant = true;
            }
        }

        if ($organizationParticipant === false) {
            throw new MissingDataException('Organization not found');
        }

        return $row;
    }
}
