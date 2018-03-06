<?php


namespace Dev\Action;

use Gems\Prediction\Communication\R\PlumberClient;
use Gems\Prediction\Model\DataCollectionRepository;
use Gems\Rest\Exception\RestException;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Diactoros\Response\JsonResponse;

class DevAction implements MiddlewareInterface
{
    /**
     * @var PlumberClient
     */
    protected $client;

    /**
     * @var DataCollectionRepository
     */
    protected $dataCollectionRepository;

    public function __construct(DataCollectionRepository $dataCollectionRepository, PlumberClient $client)
    {
        $this->dataCollectionRepository = $dataCollectionRepository;
        $this->client = $client;
    }

    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $params = $request->getQueryParams();

        try {
            $data = $this->dataCollectionRepository->getPredicationDataInputModel(
                $params['modelId'],
                $params['patientNr'],
                (int)$params['organizationId'],
                (int)$params['respondentTrack']
            );
            //$data = $this->dataCollectionRepository->getPredicationDataInputModel(1, '800101-A001', 70, 1);
        } catch(RestException $e) {
            $e->generateHttpResponse(new JsonResponse(null));
        }
        //var_dump($data);

        $response = $this->client->request('/prediction1/cmc1', 'POST', $data);

        $body = $response->getBody()->getContents();
        $plotlyData = json_decode($body, true);
        if (isset($plotlyData['config'])) {
            unset($plotlyData['config']);
        }




        if ($response instanceof JsonResponse) {
            return $response;
        }

        //return new HtmlResponse('test');

        return new JsonResponse($plotlyData);
    }
}