<?php


namespace Gems\DataSetMapper\Action;

use Gems\DataSetMapper\Repository\DataSetRepository;
use Prediction\Communication\R\PlumberClient;
use Gems\DataSetMapper\Repository\DataCollectionRepository;
use Gems\Rest\Exception\RestException;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Laminas\Diactoros\Response\JsonResponse;

class CalculatedDataAction implements MiddlewareInterface
{
    /**
     * @var PlumberClient
     */
    protected $client;

    /**
     * @var DataSetRepository
     */
    protected $dataSetRepository;

    public function __construct(DataSetRepository $dataCollectionRepository, PlumberClient $client)
    {
        $this->dataSetRepository = $dataCollectionRepository;
        $this->client = $client;
    }

    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $params = $request->getQueryParams();

        $collectionId = $request->getAttribute('collectionId');
        try {
            $data = $this->dataSetRepository->getDataSet(
                $collectionId,
                $params['patientNr'],
                (int)$params['organizationId'],
                (int)$params['respondentTrack']
            );
        } catch(\Exception $e) {
            return $e->generateHttpResponse(new JsonResponse(null));
        }

        $response = $this->client->request('/prediction1/model/' . $collectionId, 'POST', $data);

        $body = $response->getBody()->getContents();
        $plotlyData = json_decode($body, true);
        if (isset($plotlyData['config'])) {
            unset($plotlyData['config']);
        }

        if ($response instanceof JsonResponse) {
            return $response;
        }

        return new JsonResponse($plotlyData);
    }
}
