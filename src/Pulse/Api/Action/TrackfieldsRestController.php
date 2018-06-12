<?php


namespace Pulse\Api\Action;


use Gems\Rest\Action\RestControllerAbstract;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Psr\Http\Message\ServerRequestInterface;
use Pulse\Api\Repository\TrackfieldsRepository;
use Zend\Diactoros\Response\JsonResponse;

class TrackfieldsRestController extends RestControllerAbstract
{
    /**
     * @var TrackfieldsRepository
     */
    protected $trackfieldsRepository;

    public function __construct(TrackfieldsRepository $trackfieldsRepository)
    {
        $this->trackfieldsRepository = $trackfieldsRepository;
    }

    public function get(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $id = $request->getAttribute('id');

        $trackfields = $this->trackfieldsRepository->getTrackfields($id);

        return new JsonResponse($trackfields);
    }
}