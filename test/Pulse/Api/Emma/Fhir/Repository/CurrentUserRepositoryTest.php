<?php

declare(strict_types=1);


namespace PulseTest\Rest\Api\Emma\Fhir\Repository;


use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Pulse\Api\Emma\Fhir\Repository\CurrentUserRepository;

class CurrentUserRepositoryTest extends TestCase
{
    public function testGetUserId()
    {
        $repository = $this->getRepository();
        $result = $repository->getUserId();

        $this->assertEquals(8413, $result);
    }

    public function testGetUserName()
    {
        $repository = $this->getRepository();
        $result = $repository->getUserName();

        $this->assertEquals('testUser', $result);
    }

    protected function getRepository()
    {
        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getAttribute('user_name')->willReturn('testUser');
        $requestProphecy->getAttribute('user_id')->willReturn(8413);

        $currentUserRepository = new CurrentUserRepository();
        $currentUserRepository->setRequest($requestProphecy->reveal());
        return $currentUserRepository;
    }
}
