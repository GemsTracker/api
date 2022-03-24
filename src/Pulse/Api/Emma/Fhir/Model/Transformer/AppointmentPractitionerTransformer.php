<?php

namespace Pulse\Api\Emma\Fhir\Model\Transformer;


use Gems\Rest\Exception\MissingDataException;
use Pulse\Api\Emma\Fhir\Repository\AgendaStaffRepository;
use Pulse\Api\Emma\Fhir\Repository\EpdRepository;
use Pulse\Api\Repository\RespondentRepository;

/**
 * Practitioner participant to staff
 */
class AppointmentPractitionerTransformer extends \MUtil_Model_ModelTransformerAbstract
{
    /**
     * @var RespondentRepository
     */
    protected $agendaStaffRepository;
    /**
     * @var EpdRepository
     */
    protected $epdRepository;

    public function __construct(AgendaStaffRepository $agendaStaffRepository, EpdRepository $epdRepository)
    {
        $this->agendaStaffRepository = $agendaStaffRepository;
        $this->epdRepository = $epdRepository;
    }

    public function transformRowBeforeSave(\MUtil_Model_ModelAbstract $model, array $row): array
    {
        if (!isset($row['participant']) || !is_array($row['participant'])) {
            return $row;
        }
        if (!isset($row['gap_id_organization'])) {
            return $row;
        }

        foreach($row['participant'] as $participant) {
            if (isset($participant['actor'], $participant['actor']['reference']) && strpos($participant['actor']['reference'], 'Practitioner/') === 0) {
                $staffName = $participant['actor']['display'];
                $sourceId = str_replace('Practitioner/', '', $participant['actor']['reference']);
                $practitionerId = $this->agendaStaffRepository->matchStaffByNameOrSourceId($staffName, $this->epdRepository->getEpdName(), $sourceId, $row['gap_id_organization']);
                if ($practitionerId) {
                    $row['gap_id_attended_by'] = $practitionerId;
                }
            }
        }

        return $row;
    }
}
