<?php

namespace Pulse\Api\Emma\Fhir\Model\Transformer;


use Gems\Rest\Exception\MissingDataException;
use Pulse\Api\Emma\Fhir\Repository\EpdRepository;
use Pulse\Api\Repository\RespondentRepository;

/**
 * Patient to respondent ID
 */
class EncounterPatientTransformer extends \MUtil_Model_ModelTransformerAbstract
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
        if (!isset($row['subject'], $row['subject']['reference']) || !is_array($row['subject']) || strpos($row['subject']['reference'], 'Patient/') !== 0) {
            throw new MissingDataException('No patient found as subject');
        }

        $epdId = str_replace('Patient/', '', $row['subject']['reference']);
        $respondentId = $this->respondentRepository->getRespondentIdFromEpdId($epdId, $this->epdRepository->getEpdName());

        if ($respondentId === null) {
            throw new MissingDataException('Patient not found');
        }

        $row['gap_id_user'] = $respondentId;

        return $row;
    }
}
