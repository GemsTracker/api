<?php
/**
 * Created by PhpStorm.
 * User: Jasper
 * Date: 25/07/2018
 * Time: 12:04
 */

namespace Rest\Middleware;


use Gems\Rest\Middleware\SecurityHeadersMiddleware;
use Interop\Http\ServerMiddleware\DelegateInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Http\Message\ServerRequestInterface;
use Laminas\Diactoros\Response\EmptyResponse;

class SecurityHeadersMiddlewareTest extends TestCase
{
    public function testMiddlewareReturnsCorrectHeaders()
    {
        $middleware = new SecurityHeadersMiddleware();

        $request = $this->prophesize(ServerRequestInterface::class)->reveal();
        $response = new EmptyResponse(200);

        $delegateProphecy = $this->prophesize(DelegateInterface::class);
        $delegateProphecy->process(Argument::is($request))->willReturn($response);
        $delegate = $delegateProphecy->reveal();

        $updatedResponse = $middleware->process($request, $delegate);

        $this->assertEquals($updatedResponse->getHeaderLine('X-Content-Type-Options'), 'nosniff');
        $this->assertEquals($updatedResponse->getHeaderLine('X-Frame-Options'), 'deny');
    }
}
