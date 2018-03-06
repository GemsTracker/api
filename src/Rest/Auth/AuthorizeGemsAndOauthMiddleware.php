<?php


namespace Gems\Rest\Auth;


use Gems\Rest\Legacy\CurrentUserRepository;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\ResourceServer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zalt\Loader\ProjectOverloader;
use Zend\Diactoros\Response\JsonResponse;

class AuthorizeGemsAndOauthMiddleware
{
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
     * @param ServerRequestInterface $request
     * @param ResponseInterface      $response
     * @param callable               $next
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        $config = $this->config;
        $sessionName = null;

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

            if (!$currentUser->hasPrivilege('pr.api')) {
                return new JsonResponse(['error' => 'access_denied', 'message' => 'You do not have the correct privileges to access this.'], 401);
            } else {
                $request->withAttribute('user_id', $currentUser->getLoginName() . '@' . $currentUser->getBaseOrganizationId());
            }
        } else {

            try {
                $request = $this->server->validateAuthenticatedRequest($request);
                if ($userId = $request->getAttribute('oauth_user_id')) {
                    $request->withAttribute('user_id', $userId);
                    list($loginName, $loginOrganization) = explode('@', $userId);
                    $this->currentUserRepository->setCurrentUserCredentials($loginName, $loginOrganization);
                }
            } catch (OAuthServerException $exception) {
                return $exception->generateHttpResponse($response);
                // @codeCoverageIgnoreStart
            } catch (\Exception $exception) {
                return (new OAuthServerException($exception->getMessage(), 0, 'unknown_error', 500))
                    ->generateHttpResponse($response);
                // @codeCoverageIgnoreEnd
            }
        }

        // Pass the request and response on to the next responder in the chain
        return $next($request, $response);
    }
}