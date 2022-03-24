<?php

declare(strict_types=1);


namespace Pulse\Api\Emma\Fhir\Model\Transformer;


use Gems\Rest\Exception\MissingDataException;
use Pulse\Api\Emma\Fhir\Repository\EpdRepository;
use Pulse\Api\Emma\Fhir\Repository\EpisodeOfCareRepository;

/*
 * Find existing episode from the source ID
 */
class ExistingEpisodeOfCareTransformer extends \MUtil_Model_ModelTransformerAbstract
{
    /**
     * @var EpisodeOfCareRepository
     */
    protected $episodeOfCareRepository;
    /**
     * @var EpdRepository
     */
    protected $epdRepository;

    public function __construct(EpisodeOfCareRepository $episodeOfCareRepository, EpdRepository $epdRepository)
    {
        $this->episodeOfCareRepository = $episodeOfCareRepository;
        $this->epdRepository = $epdRepository;
    }

    public function transformRowBeforeSave(\MUtil_Model_ModelAbstract $model, array $row)
    {
        if (!isset($row['id'])) {
            throw new MissingDataException('No source ID supplied');
        }

        $episodeOfCareId = $this->episodeOfCareRepository->getEpisodeOfCareBySourceId($row['id'], $this->epdRepository->getEpdName());
        $row['gec_episode_of_care_id'] = $episodeOfCareId;
        if ($episodeOfCareId) {
            $row['exists'] = true;
        }

        return $row;
    }
}
