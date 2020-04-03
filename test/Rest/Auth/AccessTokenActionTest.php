<?php


namespace GemsTest\Rest\Auth;


use Gems\Rest\Auth\AccessTokenAction;
use Interop\Http\ServerMiddleware\DelegateInterface;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\Exception\OAuthServerException;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Http\Message\ServerRequestInterface;
use Laminas\Diactoros\Response\JsonResponse;

class AccessTokenActionTest extends TestCase
{
    public function testRespondToAccessTokenRequest()
    {
        $request = $this->prophesize(ServerRequestInterface::class)->reveal();
        $authorizationServerProphesy = $this->prophesize(AuthorizationServer::class);
        $authorizationServerProphesy->respondToAccessTokenRequest(
            $request,
            Argument::type(JsonResponse::class)
        )->willReturn('AccessTokenRequest response');

        $delegate = $this->prophesize(DelegateInterface::class)->reveal();
        $accessTokenAction = new AccessTokenAction($authorizationServerProphesy->reveal());
        $response = $accessTokenAction->process($request, $delegate);

        $this->assertEquals('AccessTokenRequest response', $response);
    }

    public function testOAuthServerException()
    {
        $request = $this->prophesize(ServerRequestInterface::class)->reveal();

        $exception = new OAuthServerException('Oops', 0, 'test_error');

        $authorizationServerProphesy = $this->prophesize(AuthorizationServer::class);
        $authorizationServerProphesy->respondToAccessTokenRequest(
            $request,
            Argument::type(JsonResponse::class)
        )->willThrow($exception);

        $delegate = $this->prophesize(DelegateInterface::class)->reveal();
        $accessTokenAction = new AccessTokenAction($authorizationServerProphesy->reveal());
        $response = $accessTokenAction->process($request, $delegate);

        $this->assertInstanceOf(JsonResponse::class, $response, 'Response not instance of ' . JsonResponse::class);
        $this->assertSame(400, $response->getStatusCode(), 'Status code is not ' . 400);
    }

    public function testException()
    {
        $request = $this->prophesize(ServerRequestInterface::class)->reveal();

        $exception = new \Exception('Oops');

        $authorizationServerProphesy = $this->prophesize(AuthorizationServer::class);
        $authorizationServerProphesy->respondToAccessTokenRequest(
            $request,
            Argument::type(JsonResponse::class)
        )->willThrow($exception);

        $delegate = $this->prophesize(DelegateInterface::class)->reveal();
        $accessTokenAction = new AccessTokenAction($authorizationServerProphesy->reveal());
        $response = $accessTokenAction->process($request, $delegate);

        $this->assertInstanceOf(JsonResponse::class, $response, 'Response not instance of ' . JsonResponse::class);
        $this->assertSame(500, $response->getStatusCode(), 'Status code is not ' . 500);
    }
}
