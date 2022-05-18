<?php

declare(strict_types=1);


namespace Pulse\Api\Emma\Fhir\Model\Transformer;


use Pulse\Api\Emma\Fhir\Repository\ConditionRepository;
use Pulse\Api\Emma\Fhir\Repository\EpdRepository;
use Pulse\Api\Emma\Fhir\Repository\ImportEscrowLinkRepository;

/**
 * Link indication to episode of care
 */
class AppointmentConditionTransformer extends \MUtil_Model_ModelTransformerAbstract
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
        if (!isset($row['indication']) || !is_array($row['indication'])) {
            return $row;
        }

        $row['gap_id_episode'] = null;
        foreach($row['indication'] as $indication) {
            if (isset($indication['reference']) && strpos($indication['reference'], 'Condition/') === 0) {
                $conditionId = str_replace('Condition/', '', $indication['reference']);
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
