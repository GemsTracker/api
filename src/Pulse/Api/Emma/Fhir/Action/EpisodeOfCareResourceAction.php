<?php

declare(strict_types=1);

namespace Pulse\Api\Emma\Fhir\Action;

use Gems\Event\EventDispatcher;
use Gems\Rest\Repository\AccesslogRepository;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\Response\EmptyResponse;
use Laminas\Diactoros\Response\JsonResponse;
use Mezzio\Helper\UrlHelper;
use Psr\Http\Message\ServerRequestInterface;
use Pulse\Api\Emma\Fhir\Model\EpisodeOfCareModel;
use Pulse\Api\Emma\Fhir\Repository\CurrentUserRepository;
use Pulse\Api\Emma\Fhir\Repository\EpdRepository;
use Pulse\Api\Emma\Fhir\Repository\EpisodeOfCareRepository;
use Zalt\Loader\ProjectOverloader;

class EpisodeOfCareResourceAction extends ResourceActionAbstract
{
    /**
     * @var EpisodeOfCareRepository
     */
    protected $episodeOfCareRepository;
    /**
     * @var EpdRepository
     */
    protected $epdRepository;

    public function __construct(EpisodeOfCareModel      $model,
                                EpisodeOfCareRepository $episodeOfCareRepository,
                                EpdRepository           $epdRepository,
                                CurrentUserRepository   $currentUser,
                                EventDispatcher         $event,
                                AccesslogRepository     $accesslogRepository,
                                ProjectOverloader       $loader,
                                UrlHelper               $urlHelper,
                                                        $LegacyDb)
    {
        $this->model = $model;$this->episodeOfCareRepository = $episodeOfCareRepository;
        $this->epdRepository = $epdRepository;
        parent::__construct($currentUser, $event, $accesslogRepository, $loader, $urlHelper, $LegacyDb);
    }

    public function deleteResourceFromSourceId($sourceId)
    {
        return $this->episodeOfCareRepository->softDeleteEpisodeFromSourceId($sourceId, $this->epdRepository->getEpdName());
    }

    protected function getRespondentIdFromSourceId($sourceId)
    {
        $episode = $this->episodeOfCareRepository->getEpisodeOfCareBySourceId($sourceId);
        if ($episode) {
            return $episode['gec_id_user'];
        }

        return null;
    }
}
