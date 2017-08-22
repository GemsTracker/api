<?php


namespace Gems\Rest\Auth;

use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface as ServerMiddlewareInterface;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\Entities\UserEntityInterface;
use League\OAuth2\Server\RequestTypes\AuthorizationRequest;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response;
use Zend\Diactoros\Response\HtmlResponse;
use Gems\Rest\Auth\AccessTokenRepository;
use Gems\Rest\Auth\ClientRepository;
use Gems\Rest\Auth\UserRepository;
use Zend\Expressive\Template\TemplateRendererInterface;


class AuthorizeAction implements ServerMiddlewareInterface
{
    /**
     * @var League\OAuth2\Server\AuthorizationServer
     */
    protected $server;

    public function __construct(
        AccessTokenRepository $accessTokenRepository,
        ClientRepository $clientRepository,
        UserRepository $userRepository,
        AuthorizationServer $server,
        TemplateRendererInterface $template = null
    )
    {
        $this->server = $server;

        $this->accessTokenRepository = $accessTokenRepository;
        $this->clientRepository = $clientRepository;
        $this->userRepository = $userRepository;
        $this->template = $template;
    }

    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        if ($request->getMethod() === 'POST') {
            $authRequest = $this->server->validateAuthorizationRequest($request);

            //$scopes = $authRequest->getScopes();

            $body = $request->getParsedBody();

            if (isset($body['username'], $body['password'])) {

                $user = $this->userRepository->getUserEntityByUserCredentials(
                    $request->getParsedBody()['username'],
                    $request->getParsedBody()['password'],
                    $authRequest->getGrantTypeId(),
                    $authRequest->getClient()
                );

                if ($user instanceof UserEntityInterface) {
                    return $this->approveRequest($authRequest, $user);
                }
            }
        }

        return new HtmlResponse($this->template->render('oauth::login'));
    }

    protected function approveRequest(AuthorizationRequest $authRequest, UserEntityInterface $user)
    {
        $authRequest->setUser($user);
        $authRequest->setAuthorizationApproved(true);

        return $this->server->completeAuthorizationRequest($authRequest, new Response);
    }
}