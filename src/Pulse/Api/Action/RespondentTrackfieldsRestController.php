<?php


namespace Pulse\Api\Action;

use Gems\Rest\Action\RestControllerAbstract;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Psr\Http\Message\ServerRequestInterface;
use Pulse\Api\Repository\RespondentTrackfieldsRepository;
use Laminas\Diactoros\Response\JsonResponse;

class RespondentTrackfieldsRestController extends RestControllerAbstract
{

    public static $definition = [
        'topic' => 'Respondent track fields',
        'methods' => [
            'patch' => [
                'params' => [
                    'id' => [
                        'type' => 'int',
                        'required' => true,
                    ]
                ],
                'responses' => [
                    200 => 'track fields object',
                ],
                'body' => 'object with trackfield name as key and value as value',
            ],
        ],
    ];

    /**
     * @var RespondentTrackfieldsRepository
     */
    protected $respondentTrackfieldsRepository;

    public function __construct(RespondentTrackfieldsRepository $respondentTrackfieldsRepository)
    {
        $this->respondentTrackfieldsRepository = $respondentTrackfieldsRepository;
    }

    public function patch(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $id = $request->getAttribute('id');

        $data = json_decode($request->getBody()->getContents(), true);

        $trackfields = $this->respondentTrackfieldsRepository->setTrackfields($id, $data);

        return new JsonResponse($trackfields);
    }
}
