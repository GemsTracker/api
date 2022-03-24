<?php

declare(strict_types=1);


namespace Pulse\Api\Emma\Fhir\Model\Transformer;


use Gems\Rest\Exception\MissingDataException;
use Pulse\Api\Emma\Fhir\Repository\ConditionRepository;
use Pulse\Api\Emma\Fhir\Repository\EpdRepository;

/**
 * Match existing condition to the current source ID
 */
class ExistingConditionTransformer extends \MUtil_Model_ModelTransformerAbstract
{
    /**
     * @var ConditionRepository
     */
    protected $conditionRepository;
    /**
     * @var EpdRepository
     */
    protected $epdRepository;

    public function __construct(ConditionRepository $conditionRepository, EpdRepository $epdRepository)
    {
        $this->conditionRepository = $conditionRepository;
        $this->epdRepository = $epdRepository;
    }

    public function transformRowBeforeSave(\MUtil_Model_ModelAbstract $model, array $row)
    {
        if (!isset($row['id'])) {
            throw new MissingDataException('No source ID supplied');
        }

        $row['gmco_id_condition'] = null;
        $condition = $this->conditionRepository->getConditionBySourceId($row['id'], $this->epdRepository->getEpdName());
        if (isset($condition['gmco_id_condition'])) {
            $row['gmco_id_condition'] = $condition['gmco_id_condition'];
            $row['exists'] = true;
        }

        return $row;
    }
}
