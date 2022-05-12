<?php

declare(strict_types=1);


namespace Pulse\Api\Model\Transformer;


use Pulse\Api\Emma\Fhir\Repository\CurrentUserRepository;
use Pulse\Api\Repository\RespondentRepository;

class ActivityLogRespondentTransformer extends \MUtil_Model_ModelTransformerAbstract
{
    /**
     * @var CurrentUserRepository
     */
    protected $currentUserRepository;
    /**
     * @var RespondentRepository
     */
    protected $respondentRepository;

    public function __construct(RespondentRepository $respondentRepository)
    {
        $this->respondentRepository = $respondentRepository;
    }

    public function transformRowBeforeSave(\MUtil_Model_ModelAbstract $model, array $row)
    {
        if (isset($row['patientNr'], $row['gla_organization'])) {
            $row['gla_respondent_id'] = $this->respondentRepository->getRespondentId($row['patientNr'], $row['gla_organization']);
        }

        return $row;
    }
}
