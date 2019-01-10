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
        $queryParams = $request->getQueryParams();

        $currentRole = 'super';
        if (isset($queryParams['role'])) {
            $currentRole = $queryParams['role'];
        }

        $definition = $this->apiDefinitionRepository->getDefinition($request, $currentRole);
        return new JsonResponse($definition, 200);
    }
}