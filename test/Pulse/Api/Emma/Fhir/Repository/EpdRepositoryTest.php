<?php

declare(strict_types=1);


namespace PulseTest\Rest\Api\Emma\Fhir\Repository;


use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Pulse\Api\Emma\Fhir\Repository\CurrentUserRepository;
use Pulse\Api\Emma\Fhir\Repository\EpdRepository;

class EpdRepositoryTest extends TestCase
{
    public function testGetEmmaAsEpd()
    {
        $repository = $this->getRepository('emma');

        $result = $repository->getEpdName();

        $this->assertEquals('emma', $result);
    }

    public function testGetHeuvelAsEpd()
    {
        $repository = $this->getRepository('heuvel');

        $result = $repository->getEpdName();

        $this->assertEquals('heuvel', $result);
    }

    public function testGetOtherUsername()
    {
        $repository = $this->getRepository('test123');

        $result = $repository->getEpdName();

        $this->assertEquals('test123', $result);
    }

    protected function getRepository($username)
    {
        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getAttribute('user_name')->willReturn($username);

        $currentUserRepository = new CurrentUserRepository();
        $currentUserRepository->setRequest($requestProphecy->reveal());

        return new EpdRepository($currentUserRepository);
    }
}
