<?php


namespace Pulse\Api\Action;


use Gems\Rest\Action\RestControllerAbstract;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Psr\Http\Message\ServerRequestInterface;
use Pulse\Api\Repository\TreatmentEpisodesRepository;
use Zend\Diactoros\Response\JsonResponse;

class TreatmentEpisodesRestController extends RestControllerAbstract
{
    /**
     * @var TreatmentEpisodesRepository
     */
    protected $treatmentEpisodesRepository;

    public function __construct(TreatmentEpisodesRepository $treatmentEpisodesRepository)
    {
        $this->treatmentEpisodesRepository = $treatmentEpisodesRepository;
    }

    public function get(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $id = $request->getAttribute('id');
        if ($id === null) {
            throw new RestException('Treatment episode needs an ID in the id parameter', 1, 'treatment_episode_id_missing', 400);
        }

        $treatmentEpisode = $this->treatmentEpisodesRepository->getTreatmentEpisode($id);

        return new JsonResponse($treatmentEpisode);
    }
}