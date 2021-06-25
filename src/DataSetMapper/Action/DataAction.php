<?php


namespace Gems\DataSetMapper\Action;

use Gems\DataSetMapper\Repository\DataSetRepository;
use Prediction\Communication\R\PlumberClient;
use Gems\Rest\Exception\RestException;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Laminas\Diactoros\Response\JsonResponse;

class DataAction implements MiddlewareInterface
{
    /**
     * @var PlumberClient
     */
    protected $client;

    /**
     * @var DataSetRepository
     */
    protected $dataSetRepository;

    public function __construct(DataSetRepository $dataCollectionRepository)
    {
        $this->dataSetRepository = $dataCollectionRepository;
    }

    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $params = $request->getQueryParams();

        $patientNr = null;
        if (isset($params['patientNr'])) {
            $patientNr = $params['patientNr'];
        }

        $organizationId = null;
        if (isset($params['organizationId'])) {
            $organizationId = (int)$params['organizationId'];
        }

        $collectionId = $request->getAttribute('collectionId');
        try {
            $data = $this->dataSetRepository->getDataSet(
                $collectionId,
                (int)$params['respondentTrack'],
                $patientNr,
                $organizationId,
                $params
            );
            //$data = $this->dataCollectionRepository->getPredicationDataInputModel(1, '800101-A001', 70, 1);
        } catch(RestException $e) {
            return $e->generateHttpResponse(new JsonResponse(null));
        }

        return new JsonResponse($data);
    }
}
