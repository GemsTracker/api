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
        $respondentData = $this->respondentRepository->getRespondentInfoFromEpdId($epdId, $this->epdRepository->getEpdName());

        if ($respondentData === null || !isset($respondentData['gr2o_id_user'])) {
            throw new MissingDataException('Patient not found');
        }

        $row['gap_id_user'] = $respondentData['gr2o_id_user'];
        $row['patientNr'] = $respondentData['gr2o_patient_nr'];

        return $row;
    }
}
