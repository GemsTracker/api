<?php


namespace Pulse\Api\Action;


use Gems\Rest\Action\RestControllerAbstract;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Psr\Http\Message\ServerRequestInterface;
use Pulse\Api\Repository\TreatmentsWithNormsRepository;
use Zend\Diactoros\Response\JsonResponse;

class TreatmentsWithNormsController extends RestControllerAbstract
{
    /**
     * @var TreatmentsWithNormsRepository
     */
    protected $treatmentsWithNormsRepository;

    public function __construct(TreatmentsWithNormsRepository $treatmentsWithNormsRepository)
    {
        $this->treatmentsWithNormsRepository = $treatmentsWithNormsRepository;
    }

    public function get(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $treatments = $this->treatmentsWithNormsRepository->getTreatments();

        return new JsonResponse($treatments);
    }
}
{

}