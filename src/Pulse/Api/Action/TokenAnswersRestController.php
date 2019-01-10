<?php


namespace Pulse\Api\Action;


use Gems\Rest\Action\RestControllerAbstract;
use Gems\Rest\Exception\RestException;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Psr\Http\Message\ServerRequestInterface;
use Pulse\Api\Repository\TokenAnswerRepository;
use Zend\Diactoros\Response\JsonResponse;

class TokenAnswersRestController extends RestControllerAbstract
{
    public static $definition = [
        'topic' => 'Token answers',
        'methods' => [
            'get' => [
                'params' => [
                    'id' => [
                        'type' => 'string',
                        'required' => true,
                    ]
                ],
                'responses' => [
                    200 => [
                        'gto_id_token' => 'string',
                        'answers' => 'array',
                    ],
                    400 => 'token id missing',
                ],
            ],
        ],
    ];

    protected $removeAnswerFields = [
        'id',
        'submitdate',
        'lastpage',
        'startlanguage',
        'token',
        'datestamp',
        'startdate',
    ];

    /**
     * @var TokenAnswerRepository
     */
    protected $tokenAnswerRepository;

    public function __construct(TokenAnswerRepository $tokenAnswerRepository)
    {
        $this->tokenAnswerRepository = $tokenAnswerRepository;
    }

    public function get(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $id = strtolower($request->getAttribute('id'));
        if ($id === null) {
            throw new RestException('Token ID missing', 3, 'token_id_missing', 400);
        }

        $removeAnswerFields = array_flip($this->removeAnswerFields);

        $tokenAnswers = [
            'gto_id_token' => $id,
            'answers' => array_diff_key($this->tokenAnswerRepository->getTokenAnswers($id), $removeAnswerFields),
        ];

        return new JsonResponse($tokenAnswers);
    }
}