<?php

namespace Pulse\Api\Emma\Fhir\Model\Transformer;


use Gems\Rest\Exception\MissingDataException;
use Pulse\Api\Emma\Fhir\Repository\EpdRepository;
use Pulse\Api\Repository\RespondentRepository;

/**
 *  Patient participant to respondent ID
 */
class AppointmentPatientTransformer extends \MUtil_Model_ModelTransformerAbstract
{
    /**
     * @var RespondentRepository
     */
    protected $respondentRepository;
    /**
     * @var EpdRepository
     */
    protected $epdRepository;

    public function __construct(RespondentRepository $respondentRepository, EpdRepository $epdRepository)
    {
        $this->respondentRepository = $respondentRepository;
        $this->epdRepository = $epdRepository;
    }

    public function transformRowBeforeSave(\MUtil_Model_ModelAbstract $model, array $row): array
    {
        if (!isset($row['participant']) || !is_array($row['participant'])) {
            throw new MissingDataException('No patient found as participant');
        }

        $patientParticipant = false;
        foreach($row['participant'] as $participant) {
            if (isset($participant['actor'], $participant['actor']['reference']) && strpos($participant['actor']['reference'], 'Patient/') === 0) {
                $epdId = str_replace('Patient/', '', $participant['actor']['reference']);
                $respondentId = $this->respondentRepository->getRespondentIdFromEpdId($epdId, $this->epdRepository->getEpdName());
                if ($respondentId) {
                    $patientParticipant = true;
                    $row['gap_id_user'] = $respondentId;
                }
            }
        }

        if ($patientParticipant === false) {
            throw new MissingDataException('Patient not found');
        }

        return $row;
    }
}
