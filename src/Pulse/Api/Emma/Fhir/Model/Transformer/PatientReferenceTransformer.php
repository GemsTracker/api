<?php

namespace Pulse\Api\Emma\Fhir\Model\Transformer;


use Gems\Rest\Exception\MissingDataException;
use Pulse\Api\Emma\Fhir\Repository\EpdRepository;
use Pulse\Api\Repository\RespondentRepository;

/**
 * Transform patient reference to respondent ID
 */
class PatientReferenceTransformer extends \MUtil_Model_ModelTransformerAbstract
{
    /**
     * @var RespondentRepository
     */
    protected $respondentRepository;

    /**
     * @var string Target field
     */
    protected $internalField;

    /**
     * @var string Source field
     */
    protected $externalField;
    /**
     * @var EpdRepository
     */
    protected $epdRepository;

    public function __construct(RespondentRepository $respondentRepository, EpdRepository $epdRepository, $externalField, $internalField)
    {
        $this->respondentRepository = $respondentRepository;
        $this->epdRepository = $epdRepository;
        $this->externalField = $externalField;
        $this->internalField = $internalField;

    }

    public function transformRowBeforeSave(\MUtil_Model_ModelAbstract $model, array $row): array
    {
        if (!isset($row[$this->externalField], $row[$this->externalField]['reference']) || !is_array($row[$this->externalField]) || strpos($row[$this->externalField]['reference'], 'Patient/') !== 0) {
            throw new MissingDataException('No patient found');
        }

        $epdId = str_replace('Patient/', '', $row[$this->externalField]['reference']);
        $respondentId = $this->respondentRepository->getRespondentIdFromEpdId($epdId, $this->epdRepository->getEpdName());
        if ($respondentId) {
            $row[$this->internalField] = $respondentId;
        }

        return $row;
    }
}
