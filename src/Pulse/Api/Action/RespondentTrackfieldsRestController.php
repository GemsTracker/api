<?php


namespace Pulse\Api\Action;

use Gems\Rest\Action\RestControllerAbstract;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Psr\Http\Message\ServerRequestInterface;
use Pulse\Api\Repository\RespondentTrackfieldsRepository;
use Zend\Diactoros\Response\JsonResponse;

class RespondentTrackfieldsRestController extends RestControllerAbstract
{
    /**
     * @var RespondentTrackfieldsRepository
     */
    protected $respondentTrackfieldsRepository;

    public function __construct(RespondentTrackfieldsRepository $respondentTrackfieldsRepository)
    {
        $this->respondentTrackfieldsRepository = $respondentTrackfieldsRepository;
    }

    public function get(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $id = $request->getAttribute('id');

        $trackfields = $this->respondentTrackfieldsRepository->getTrackfields($id);

        $data = [
            'side' => "Rechts",
        ];

        $trackfields = $this->respondentTrackfieldsRepository->setTrackfields($id, $data);

        return new JsonResponse($trackfields);
    }

    public function patch(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $id = $request->getAttribute('id');

        $data = json_decode($request->getBody()->getContents(), true);

        $trackfields = $this->respondentTrackfieldsRepository->setTrackfields($id, $data);

        return new JsonResponse($trackfields);
    }
}