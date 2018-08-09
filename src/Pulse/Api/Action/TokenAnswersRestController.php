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

        $tokenAnswers = [
            'gto_id_token' => $id,
            'answers' => $this->tokenAnswerRepository->getTokenAnswers($id),
        ];

        return new JsonResponse($tokenAnswers);
    }
}