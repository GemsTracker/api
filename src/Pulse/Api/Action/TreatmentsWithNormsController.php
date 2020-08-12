<?php


namespace Pulse\Api\Action;


use Gems\Rest\Action\RestControllerAbstract;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Psr\Http\Message\ServerRequestInterface;
use Pulse\Api\Repository\TreatmentsWithNormsRepository;
use Laminas\Diactoros\Response\JsonResponse;

class TreatmentsWithNormsController extends RestControllerAbstract
{
    public static $definition = [
        'topic' => 'Treatment with norms',
        'methods' => [
            'get' => [
                'params' => [
                    'gto_id_respondent_track' => [
                        'type' => 'int',
                    ],
                ],
                'responses' => [
                    200 => 'Treatments with norms list',
                ],
            ],
        ],
    ];

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
        $params = $request->getQueryParams();

        $treatments = $this->treatmentsWithNormsRepository->getTreatments($params);

        return new JsonResponse($treatments);
    }
}
{

}
