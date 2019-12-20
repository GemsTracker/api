<?php


namespace Pulse\Api\Action;

use Gems\Rest\Action\ModelRestControllerAbstract;
use Gems\Rest\Action\RestControllerAbstract;
use Gems\Rest\Exception\RestException;
use Gems\Rest\Model\RouteOptionsModelFilter;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Psr\Http\Message\ServerRequestInterface;
use Pulse\Api\Repository\TokenRepository;
use Zend\Diactoros\Response\EmptyResponse;
use Zend\Diactoros\Response\JsonResponse;

class LastAnsweredTokenController extends RestControllerAbstract
{
    /**
     * @var TokenRepository
     */
    protected $tokenRepository;

    public function __construct(TokenRepository $tokenRepository)
    {
        $this->tokenRepository = $tokenRepository;
    }

    /**
     * Filter the columns of a row with routeoptions like allowed_fields, disallowed_fields and readonly_fields
     *
     * @param array $row Row with model values
     * @param bool $save Will the row be saved after filter (enables readonly
     * @param bool $useKeys Use keys or values in the filter of the row
     * @return array Filtered array
     */
    protected function filterColumns($row, $save=false, $useKeys=true)
    {
        $row = RouteOptionsModelFilter::filterColumns($row, $this->routeOptions, $save, $useKeys);

        return $row;
    }

    public function get(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $queryParams = $request->getQueryParams();

        if (!isset($queryParams['gr2o_patient_nr'])) {
            throw new RestException('Patient number needs to be supplied', 1, 'patient_nr_missing', 400);
        }

        if (!isset($queryParams['gr2o_id_organization'])) {
            throw new RestException('Organisation id needs to be supplied', 1, 'organization_id_missing', 400);
        }

        // Also filter incoming columns, as there is no model loaded to check on currently
        $params = $this->filterColumns($request->getQueryParams());

        $tokens = $this->tokenRepository->getLatestTokensForSurveyCodes($params);

        if ($tokens == false) {
            return new EmptyResponse();
        }

        $filteredTokens = [];
        foreach($tokens as $key=>$token) {
            $filteredTokens[] = $this->filterColumns($token);
        }

        return new JsonResponse($filteredTokens);

    }
}