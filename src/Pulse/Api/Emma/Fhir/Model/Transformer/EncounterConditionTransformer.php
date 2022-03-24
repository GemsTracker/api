<?php

declare(strict_types=1);


namespace Pulse\Api\Emma\Fhir\Model\Transformer;


use Pulse\Api\Emma\Fhir\Repository\ConditionRepository;
use Pulse\Api\Emma\Fhir\Repository\EpdRepository;
use Pulse\Api\Emma\Fhir\Repository\ImportEscrowLinkRepository;

/**
 * Link diagnosis to episode of care
 */
class EncounterConditionTransformer extends \MUtil_Model_ModelTransformerAbstract
{
    /**
     * @var ConditionRepository
     */
    protected $conditionRepository;
    /**
     * @var ImportEscrowLinkRepository
     */
    protected $importEscrowLinkRepository;
    /**
     * @var EpdRepository
     */
    protected $epdRepository;

    public function __construct(ConditionRepository $conditionRepository, EpdRepository $epdRepository, ImportEscrowLinkRepository $importEscrowLinkRepository)
    {
        $this->conditionRepository = $conditionRepository;
        $this->epdRepository = $epdRepository;
        $this->importEscrowLinkRepository = $importEscrowLinkRepository;
    }

    public function transformRowBeforeSave(\MUtil_Model_ModelAbstract $model, array $row)
    {
        if (!isset($row['diagnosis']) || !is_array($row['diagnosis'])) {
            return $row;
        }

        foreach($row['diagnosis'] as $condition) {
            if (isset($condition['condition']['reference']) && strpos($condition['condition']['reference'], 'Condition/') === 0) {
                $conditionId = str_replace('Condition/', '', $condition['condition']['reference']);
                $episodeOfCareId = $this->conditionRepository->getEpisodeOfCareIdFromConditionBySourceId($conditionId, $this->epdRepository->getEpdName());
                $row['gap_id_episode'] = $episodeOfCareId;

                if ($episodeOfCareId === null) {
                    $this->importEscrowLinkRepository->addEscrowLink('condition', $conditionId, 'appointment', $row['id']);
                }
            }
        }

        return $row;
    }
}
