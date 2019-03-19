<?php


namespace Gems\Rest\Auth;


use Gems\Rest\Legacy\CurrentUserRepository;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\ResourceServer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zalt\Loader\ProjectOverloader;
use Zend\Diactoros\Response;
use Zend\Diactoros\Response\JsonResponse;

class AuthorizeGemsAndOauthMiddleware implements MiddlewareInterface
{
    /**
     * @var array Config
     */
    protected $config;

    /**
     * @var CurrentUserRepository
     */
    protected $currentUserRepository;

    /**
     * @var ResourceServer
     */
    protected $server;


    /**
     * @param ResourceServer $server
     */
    public function __construct(ResourceServer $server, CurrentUserRepository $currentUserRepository, $config)
    {
        $this->server = $server;
        $this->currentUserRepository  = $currentUserRepository;
        $this->config = $config;
    }

    /**
     * Process an incoming server request and return a response, optionally delegating
     * to the next middleware component to create the response.
     *
     * @param ServerRequestInterface $request
     * @param DelegateInterface $delegate
     *
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $config = $this->config;
        $sessionName = null;
        $gemsAuth = false;

        if (isset($config['gems_auth'])
            && isset($config['gems_auth']['use_linked_gemstracker_session'])
            && $config['gems_auth']['use_linked_gemstracker_session'] === true
            && isset($config['gems_auth']['linked_gemstracker'])
        ) {
            $gemsProjectNameUc = ucfirst($config['gems_auth']['linked_gemstracker']['project_name']);
            $applicationPath = $config['gems_auth']['linked_gemstracker']['root_dir'] . '/application';
            $sessionName = $gemsProjectNameUc . '_' . md5($applicationPath) . '_SESSID';
            $cookieParams = $request->getCookieParams();
        }

        if ($sessionName != null && isset($cookieParams[$sessionName]) && $currentUser = $this->currentUserRepository->getCurrentUserFromSession()) {


            if (isset($config['gems_auth'], $config['gems_auth']['requested_width_check'])
                && $config['gems_auth']['requested_width_check'] == true
                && $request->getHeaderLine('X-Requested-With') != 'XMLHttpRequest') {
                return new JsonResponse(['error' => 'no_ajax', 'message' => 'XmlHttpRequest needed'], 403);
            }

            if (!$currentUser->hasPrivilege('pr.api')) {
                return new JsonResponse(['error' => 'access_denied', 'message' => 'You do not have the correct privileges to access this.'], 401);
            } else {
                $request = $request->withAttribute('user_id', $currentUser->getUserId());
                $request = $request->withAttribute('user_name', $currentUser->getLoginName());
                $request = $request->withAttribute('user_organization', $currentUser->getBaseOrganizationId());
                $request = $request->withAttribute('user_role', $currentUser->getRole());

                $gemsAuth = true;
            }
        } else {

            try {
                $request = $this->server->validateAuthenticatedRequest($request);
                if ($oauthUserId = $request->getAttribute('oauth_user_id')) {

                    list($userId, $loginName, $loginOrganization) = explode('@', $oauthUserId);
                    $request = $request->withAttribute('user_id', $userId);
                    $request = $request->withAttribute('user_name', $loginName);
                    $request = $request->withAttribute('user_organization', $loginOrganization);

                    $this->currentUserRepository->setCurrentUserCredentials($loginName, $loginOrganization);
                }
            } catch (OAuthServerException $exception) {
                $response = new Response();
                return $exception->generateHttpResponse($response);
                // @codeCoverageIgnoreStart
            } catch (\Exception $exception) {
                $response = new Response();
                return (new OAuthServerException($exception->getMessage(), 0, 'unknown_error', 500))
                    ->generateHttpResponse($response);
                // @codeCoverageIgnoreEnd
            }
        }

        $response = $delegate->process($request);

        if ($gemsAuth && isset($config['gems_auth']['allow_origin_domains']) && is_array($config['gems_auth']['allow_origin_domains'])) {
            $currentSite = null;
            if ($origin = $request->getHeaderLine('origin')) {
                $currentSite = $origin;
            } elseif($referer = $request->getHeaderLine('referer')) {
                $currentSite = $referer;
            }

            foreach($config['gems_auth']['allow_origin_domains'] as $domain) {
                if (strpos($currentSite, $domain) === 0) {
                    $response = $response->withHeader('Access-Control-Allow-Origin', $domain);
                }
            }
        }

        return $response;
    }
}