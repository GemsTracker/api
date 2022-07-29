<?php

namespace Pulse\Api\Emma\Fhir\Model\Transformer;

use Pulse\Api\Emma\Fhir\Repository\EpdRepository;
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

    /**
     * @var EpdRepository
     */
    protected $epdRepository;

    public function __construct(EpisodeOfCareRepository $episodeOfCareRepository, ImportEscrowLinkRepository $importEscrowLinkRepository, EpdRepository $epdRepository)
    {
        $this->episodeOfCareRepository = $episodeOfCareRepository;
        $this->importEscrowLinkRepository = $importEscrowLinkRepository;
        $this->epdRepository = $epdRepository;
    }

    public function transformRowBeforeSave(\MUtil_Model_ModelAbstract $model, array $row)
    {
        if (!isset($row['context'], $row['context']['reference']) || strpos($row['context']['reference'], 'EpisodeOfCare/') !== 0) {
            return $row;
        }
        $row['episodeOfCareSourceId'] = $episodeSourceId = str_replace('EpisodeOfCare/', '', $row['context']['reference']);


        $episode = $this->episodeOfCareRepository->getEpisodeOfCareBySourceId($episodeSourceId, $this->epdRepository->getEpdName());
        if ($episode && isset($episode['gec_episode_of_care_id'])) {
            $row['gmco_id_episode_of_care'] = $episode['gec_episode_of_care_id'];
        }

        if ($episode === null) {
            $this->importEscrowLinkRepository->addEscrowLink('episodeOfCare', $episodeSourceId, 'condition', $row['id']);
        }

        return $row;
    }
}
