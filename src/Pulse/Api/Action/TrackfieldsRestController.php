<?php


namespace Pulse\Api\Action;


use Gems\Rest\Action\RestControllerAbstract;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Psr\Http\Message\ServerRequestInterface;
use Pulse\Api\Repository\TrackfieldsRepository;
use Laminas\Diactoros\Response\JsonResponse;

class TrackfieldsRestController extends RestControllerAbstract
{
    public static $definition = [
        'topic' => 'Track fields',
        'methods' => [
            'get' => [
                'params' => [
                    'id' => [
                        'type' => 'int',
                        'required' => true,
                    ]
                ],
                'responses' => [
                    200 => 'track fields object',
                    400 => 'treatment episode id missing',
                ],
            ],
        ],
    ];

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
