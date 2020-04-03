<?php


namespace Gems\Rest\Middleware;


use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Laminas\Diactoros\Response\EmptyResponse;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Permissions\Acl\Acl;

class ApiGateMiddleware implements MiddlewareInterface
{
    /**
     * @var Gems_User_User
     */
    protected $currentUser;

    public function __construct(Acl $acl, $LegacyCurrentUser)
    {
        $this->acl = $acl;
        $this->currentUser = $LegacyCurrentUser;
    }

    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $userRole = $this->currentUser->getRole(true);
        $method = $request->getMethod();
        $routeResult = $request->getAttribute('Mezzio\Router\RouteResult');
        $route = $routeResult->getMatchedRoute();
        $routeName = str_replace(['.structure', '.get', '.fixed'], '', $route->getName());

        if (!$this->acl->isAllowed($userRole, $routeName, $method)) {
            return new JsonResponse(['message' => 'User does not have the correct privileges for this request'],401);
        }

        $response = $delegate->process($request);
        return $response;
    }
}
