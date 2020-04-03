<?php


namespace Prediction\Action;


use Prediction\Model\DataCollectionRepository;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Laminas\Diactoros\Response\JsonResponse;

class ChartsDefinitionsAction implements MiddlewareInterface
{
    /**
     * @var DataCollectionRepository
     */
    protected $dataCollectionRepository;

    public function __construct(DataCollectionRepository $dataCollectionRepository)
    {
        $this->dataCollectionRepository = $dataCollectionRepository;
    }

    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $params = $request->getQueryParams();
        if (!isset($params['patientNr'], $params['organizationId'])) {
            return new JsonResponse(
                [
                    'error' => 'missing_data',
                    'message' => 'You need to supply patientNr and organizationId as parameters'
                ], 400
            );
        }

        $data = $this->dataCollectionRepository->getPredictionChartsData($params['patientNr'], $params['organizationId']);

        return new JsonResponse($data);
    }
}
