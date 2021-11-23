<?php


namespace Gems\Rest\Middleware;


use Gems\Rest\Repository\RespondentRepository;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Laminas\Diactoros\Response\EmptyResponse;
use Laminas\Diactoros\Response\JsonResponse;

class ApiPatientGateMiddleware implements MiddlewareInterface
{
    /**
     * @var array
     */
    protected $allowedPatientNumbers;

    /**
     * @var array
     */
    protected $config;

    /**
     * @var \Gems_User_User
     */
    protected $currentUser;

    /**
     * @var RespondentRepository
     */
    protected $respondentRepository;

    public function __construct(RespondentRepository $respondentRepository, $config, $LegacyCurrentUser)
    {
        $this->currentUser = $LegacyCurrentUser;
        $this->config = $config;
        $this->respondentRepository = $respondentRepository;
    }

    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $method = $request->getMethod();
        $routeResult = $request->getAttribute('Mezzio\Router\RouteResult');

        $route = $routeResult->getMatchedRoute();
        $routeOptions = $route->getOptions();

        if ($this->currentUser->isStaff() || !isset($routeOptions['patientIdField']) || empty($routeOptions['patientIdField'])) {
            $response = $delegate->process($request);
            return $response;
        }

        $serverParams = $request->getServerParams();
        $serverName = $serverParams['SERVER_NAME'];

        if (isset($this->config, $this->config['patientLogin'], $this->config['patientLogin']['gate'])) {
            if (isset($this->config['patientLogin']['gate']['allowedSites']) && in_array($serverName, $this->config['patientLogin']['gate']['allowedSites'])) {
                $response = $delegate->process($request);
                return $response;
            }
            if (isset($this->config['patientLogin']['gate']['allowedOauthClients']) && in_array($serverName, $this->config['patientLogin']['gate']['allowedOauthClients'])) {
                $response = $delegate->process($request);
                return $response;
            }
        }

        if ($method == 'GET') {
            $filters = $request->getQueryParams();
            $filters = $this->getPatientRouteFilters($filters, $routeOptions, $this->currentUser);
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

    protected function getAllowedPatientNumbers($patientNr, $organizationId)
    {
        if (!$this->allowedPatientNumbers) {
            $this->allowedPatientNumbers = $this->respondentRepository->getOtherPatientNumbers($patientNr, $organizationId);
        }
        return $this->allowedPatientNumbers;
    }

    protected function getPatientRouteFilters($filters, $routeOptions, \Gems_User_User $currentUser)
    {
        $patientNr = $currentUser->getLoginName();
        $organizationId = $currentUser->getBaseOrganizationId();

        $patientIdFields = $routeOptions['patientIdField'];
        if (!is_array($patientIdFields)) {
            $patientIdFields = [$patientIdFields];
        }

        foreach($patientIdFields as $patientIdField) {
            if (!isset($filters[$patientIdField])) {
                $filters[$patientIdField] = $patientNr . '@' . $organizationId;
            } else {
                $allowedPatientNumbers = $this->getAllowedPatientNumbers($patientNr, $organizationId);

                $selectedPatientIds = $filters[$patientIdField];
                if (is_string($selectedPatientIds)) {
                    $selectedPatientIds = explode(',', str_replace(['[', ']'], '', $selectedPatientIds));
                }
                $filteredOrganizationIds = [];
                foreach($selectedPatientIds as $patientCombination) {
                    list($selectedPatientNr, $selectedOrganizationId) = explode('@', $patientCombination);
                    if (isset($allowedPatientNumbers[$selectedOrganizationId]) && $allowedPatientNumbers[$selectedOrganizationId] === $selectedPatientNr) {
                        $filteredOrganizationIds[] = $patientCombination;
                    }
                }
                $filters[$patientIdField] = $filteredOrganizationIds;
            }
        }

        return $filters;
    }
}
