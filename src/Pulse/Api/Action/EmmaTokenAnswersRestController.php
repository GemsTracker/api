<?php


namespace Pulse\Api\Action;


use Gems\Rest\Action\RestControllerAbstract;
use Gems\Rest\Exception\RestException;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Psr\Http\Message\ServerRequestInterface;
use Pulse\Api\Repository\TokenAnswerRepository;
use Zend\Diactoros\Response\JsonResponse;

class EmmaTokenAnswersRestController extends RestControllerAbstract
{
    public static $definition = [
        'topic' => 'Emma Token answers',
        'methods' => [
            'get' => [
                'params' => [
                    'id' => [
                        'type' => 'string',
                        'required' => true,
                    ]
                ],
                'responses' => [
                    200 => 'Formatted token Answers',
                    400 => 'Token id missing',
                ],
            ],
        ],
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
        $id = $request->getAttribute('id');
        if ($id === null) {
            return new JsonResponse(['error' => 'token_id_missing', 'message' => 'Token ID missing'], 400);
        }

        $id = strtolower($id);

        $answers = $this->tokenAnswerRepository->getFormattedTokenAnswers($id);

        $metaFields = [
            'id',
            'token',
            'startdate',
            'submitdate',
            'datestamp',
            'ipaddr',
            'startlanguage',
            'lastpage',
        ];

        $filteredAnswers = array_diff_key($answers, array_flip($metaFields));

        return new JsonResponse($filteredAnswers);
    }
}