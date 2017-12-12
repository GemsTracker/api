<?php


namespace Gemstest\Rest\Auth;


use Gems\Rest\Auth\AccessTokenRepository;
use Gems\Rest\Auth\AuthorizeAction;
use Gems\Rest\Auth\ClientRepository;
use Gems\Rest\Auth\UserEntity;
use Gems\Rest\Auth\UserRepository;
use Interop\Http\ServerMiddleware\DelegateInterface;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\UserEntityInterface;
use League\OAuth2\Server\RequestTypes\AuthorizationRequest;
use League\OAuth2\Server\ResponseTypes\RedirectResponse;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Expressive\Template\TemplateRendererInterface;

class AuthorizeActionTest extends TestCase
{
    public function testProcessAuthorization()
    {
        $accessTokenRepository = $this->prophesize(AccessTokenRepository::class);

        $clientRepository = $this->prophesize(ClientRepository::class);
        $clientEntity = $this->prophesize(ClientEntityInterface::class);

        $userEntity = $this->prophesize(UserEntity::class);
        $userRepository = $this->prophesize(UserRepository::class);

        $userRepository->getUserEntityByUserCredentials(
            Argument::type('string'),
            Argument::type('string'),
            Argument::type('string'),
            Argument::type(ClientEntityInterface::class)
        )->willReturn($userEntity->reveal());

        $authRequest = $this->prophesize(AuthorizationRequest::class);
        $authRequest->getGrantTypeId()->willReturn('GrantTypeId');
        $authRequest->getClient()->willReturn($clientEntity->reveal());
        $authRequest->setUser(Argument::type(UserEntityInterface::class))->willReturn(null);
        $authRequest->setAuthorizationApproved(Argument::type('bool'))->willReturn(null);

        $authorizationServer = $this->prophesize(AuthorizationServer::class);
        $authorizationServer->validateAuthorizationRequest(Argument::type(ServerRequestInterface::class))->willReturn($authRequest->reveal());
        $authorizationServer->completeAuthorizationRequest(
            Argument::type(AuthorizationRequest::class),
            Argument::type(Response::class)
        )->willReturn($this->prophesize(RedirectResponse::class)->reveal());

        $templateRenderer = $this->prophesize(TemplateRendererInterface::class);
        $templateRenderer->render(Argument::type('string'))->willReturn('Test content!');

        $authorizeAction = new AuthorizeAction(
            $accessTokenRepository->reveal(),
            $clientRepository->reveal(),
            $userRepository->reveal(),
            $authorizationServer->reveal(),
            $templateRenderer->reveal()
        );

        $serverRequest = $this->prophesize(ServerRequestInterface::class);
        $serverRequest->getMethod()->willReturn('POST');
        $serverRequest->getParsedBody()->willReturn(['username' => 'testUser', 'password' => 'testPassword']);


        $delegate = $this->prophesize(DelegateInterface::class);

        $response = $authorizeAction->process($serverRequest->reveal(), $delegate->reveal());

        $this->assertInstanceOf(RedirectResponse::class, $response, 'Response is not a redirect');

        $serverRequest = $this->prophesize(ServerRequestInterface::class);
        $serverRequest->getMethod()->willReturn('GET');

        $response = $authorizeAction->process($serverRequest->reveal(), $delegate->reveal());

        $this->assertInstanceOf(HtmlResponse::class, $response, 'Response is not an HTML response');
    }
}