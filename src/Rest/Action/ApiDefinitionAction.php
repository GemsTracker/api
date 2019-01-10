<?php


namespace Gems\Rest\Action;


use Gems\Rest\Repository\ApiDefinitionRepository;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\JsonResponse;

class ApiDefinitionAction implements MiddlewareInterface
{
    /**
     * @var ApiDefinitionRepository
     */
    protected $apiDefinitionRepository;

    public function __construct(ApiDefinitionRepository $apiDefinitionRepository)
    {
        $this->apiDefinitionRepository = $apiDefinitionRepository;
    }

    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $definition = $this->apiDefinitionRepository->getDefinition($request, 'super');
        return new JsonResponse($definition, 200);
    }
}