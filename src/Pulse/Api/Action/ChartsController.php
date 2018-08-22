<?php


namespace Pulse\Api\Action;


use Gems\Rest\Action\RestControllerAbstract;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Psr\Http\Message\ServerRequestInterface;
use Pulse\Api\Repository\ChartRepository;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Diactoros\Response\JsonResponse;

class ChartsController extends RestControllerAbstract
{
    /**
     * @var ChartRepository
     */
    protected $chartRepository;

    public function __construct(ChartRepository $chartRepository)
    {
        $this->chartRepository = $chartRepository;
    }

    public function get(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $id = $request->getAttribute('id');
        $respondentTrackId = $request->getAttribute('respondentTrackId');

        $outcomeVariable = $this->chartRepository->getChart($id, $respondentTrackId);

        return new JsonResponse($outcomeVariable, 200);
    }
}