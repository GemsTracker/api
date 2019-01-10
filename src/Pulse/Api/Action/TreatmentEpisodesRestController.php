<?php


namespace Pulse\Api\Action;


use Gems\Rest\Action\RestControllerAbstract;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Psr\Http\Message\ServerRequestInterface;
use Pulse\Api\Repository\TreatmentEpisodesRepository;
use Zend\Diactoros\Response\JsonResponse;

class TreatmentEpisodesRestController extends RestControllerAbstract
{
    public static $definition = [
        'topic' => 'Treatment episodes',
        'methods' => [
            'get' => [
                'params' => [
                    'id' => [
                        'type' => 'int',
                        'required' => true,
                    ]
                ],
                'responses' => [
                    200 => [
                        'gte_id_episode' => 'int',
                        'tracks' => 'array',
                        '~treatment' => 'object',
                        '~treatmentAppointment' => 'datetime',
                        '~side' => 'string',
                    ],
                    400 => 'treatment episode id missing',
                ],
            ],
        ],
    ];

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

        $filters = $request->getQueryParams();

        $treatmentEpisode = $this->treatmentEpisodesRepository->getTreatmentEpisode($id, $filters);

        return new JsonResponse($treatmentEpisode);
    }
}