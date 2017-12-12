<?php


namespace Gemstest\Rest\Auth;

use Gems\Rest\Auth\MergeUsernameOrganizationMiddleware;
use Interop\Http\ServerMiddleware\DelegateInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Http\Message\ServerRequestInterface;

class MergeUsernameOrganizationMiddlewareTest extends TestCase
{
    public function testMergeProcess()
    {
        $serverRequest = $this->prophesize(ServerRequestInterface::class);
        $serverRequest->getParsedBody()->willReturn(
            [
                'username' => 'testUser',
                'organization' => 'testOrganization',
            ]
        );

        $serverRequest->withParsedBody(Argument::type('array'))->will(function($args) {
            $this->getParsedBody()->willReturn($args[0]);
            return $this;
        });

        $delegate = $this->prophesize(DelegateInterface::class);
        $delegate->process(Argument::any())->will(function($args) {
            return $args[0];
        });

        $middleware = new MergeUsernameOrganizationMiddleware();
        $request = $middleware->process($serverRequest->reveal(), $delegate->reveal());

        $this->assertInstanceOf(ServerRequestInterface::class, $request, 'Request not instance of ' . ServerRequestInterface::class);
        $this->assertEquals(['username' => 'testUser@testOrganization'], $request->getParsedBody(), 'Request body does not contain correct variables');
    }
}