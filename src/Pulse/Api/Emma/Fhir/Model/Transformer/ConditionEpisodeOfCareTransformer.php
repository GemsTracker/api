<?php

namespace Pulse\Api\Emma\Fhir\Model\Transformer;

use Pulse\Api\Emma\Fhir\Repository\EpisodeOfCareRepository;
use Pulse\Api\Emma\Fhir\Repository\ImportEscrowLinkRepository;

/**
 * Condition to episode link
 */
class ConditionEpisodeOfCareTransformer extends \MUtil_Model_ModelTransformerAbstract
{
    /**
     * @var EpisodeOfCareRepository
     */
    protected $episodeOfCareRepository;
    /**
     * @var ImportEscrowLinkRepository
     */
    protected $importEscrowLinkRepository;

    public function __construct(EpisodeOfCareRepository $episodeOfCareRepository, ImportEscrowLinkRepository $importEscrowLinkRepository)
    {
        $this->episodeOfCareRepository = $episodeOfCareRepository;
        $this->importEscrowLinkRepository = $importEscrowLinkRepository;
    }

    public function transformRowBeforeSave(\MUtil_Model_ModelAbstract $model, array $row)
    {
        if (!isset($row['context'], $row['context']['reference']) || strpos($row['context']['reference'], 'EpisodeOfCare/') !== 0) {
            return $row;
        }
        $row['episodeOfCareSourceId'] = $episodeSourceId = str_replace('EpisodeOfCare/', '', $row['context']['reference']);


        $episodeId = $this->episodeOfCareRepository->getEpisodeOfCareBySourceId($episodeSourceId, 'emma');
        $row['gmco_id_episode_of_care'] = $episodeId;

        if ($episodeId === null) {
            $this->importEscrowLinkRepository->addEscrowLink('episodeOfCare', $episodeSourceId, 'condition', $row['id']);
        }

        return $row;
    }
}
