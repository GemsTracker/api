<?php


namespace Gems\Rest\Middleware;


use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\EmptyResponse;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Permissions\Acl\Acl;

class ApiOrganizationGateMiddleware implements MiddlewareInterface
{
    /**
     * @var \Gems_User_User
     */
    protected $currentUser;

    public function __construct(Acl $acl, $LegacyCurrentUser)
    {
        $this->acl = $acl;
        $this->currentUser = $LegacyCurrentUser;
    }

    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $method = $request->getMethod();
        $routeResult = $request->getAttribute('Zend\Expressive\Router\RouteResult');
        $route = $routeResult->getMatchedRoute();
        $test = $route->getPath();

        $routeOptions = $route->getOptions();
        if (!isset($routeOptions['organizationId']) || empty($routeOptions['organizationId'])) {
            $response = $delegate->process($request);
            return $response;
        }

        if ($method == 'GET') {
            $allowedOrganizations = array_keys($this->currentUser->getAllowedOrganizations());

            $filters = $request->getQueryParams();
            $filters = $this->getRouteFilters($filters, $routeOptions, $allowedOrganizations);
            $request = $request->withQueryParams($filters);
        } /*elseif ($method == 'POST' || $method == 'PATCH') {
            //$filters = $request->getParsedBody();
            $body = $request->getBody();
            $filters = $body->getContents();

            $filters = $this->getRouteFilters($filters, $routeOptions, $allowedOrganizations);
            $request->withBody($filters);
        }*/

        $response = $delegate->process($request);
        return $response;
    }

    protected function getRouteFilters($filters, $routeOptions, $allowedOrganizations)
    {
        if (!isset($filters[$routeOptions['organizationId']])) {
            $filters[$routeOptions['organizationId']] = $allowedOrganizations;//'['.join(',', $allowedOrganizations).']';
        } else {
            $selectedOrganizationIds = '['.join(',', $filters[$routeOptions['organizationId']]).']';
            $filteredOrganizationIds = [];
            foreach($selectedOrganizationIds as $organizationId) {
                if (in_array($organizationId, $selectedOrganizationIds)) {
                    $filteredOrganizationIds[] = $organizationId;
                }
            }
            $filters[$routeOptions['organizationId']] = $filteredOrganizationIds;
        }
        return $filters;
    }
}