<?php

declare(strict_types=1);


namespace PulseTest\Rest\Api\Emma\Fhir\Repository;


use GemsTest\Rest\Test\DbTestCase;
use GemsTest\Rest\Test\LaminasDbFixtures;
use Prophecy\Argument;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Pulse\Api\Emma\Fhir\Repository\AgendaActivityRepository;
use Pulse\Api\Emma\Fhir\Repository\CurrentUserRepository;

class AgendaActivityRepositoryTest extends DbTestCase
{
    use LaminasDbFixtures;

    public function testMatchNotFoundNoCreate()
    {
        $this->insertFixtures([AgendaActivityFixtures::class]);
        $repository = $this->getRepository();

        $this->assertTableRowCount(6, 'gems__agenda_activities');
        $result = $repository->matchActivity('testActivity999', 1, false);

        $this->assertNull($result);
        $this->assertTableRowCount(6, 'gems__agenda_activities');
    }

    public function testMatchFoundByName()
    {
        $this->insertFixtures([AgendaActivityFixtures::class]);
        $repository = $this->getRepository();

        $result = $repository->matchActivity('testActivity1', 1, false);

        $this->assertEquals(4001, $result);
    }

    public function testMatchFoundButNotActive()
    {
        $this->insertFixtures([AgendaActivityFixtures::class]);
        $repository = $this->getRepository();

        $result = $repository->matchActivity('testActivity2', 1, false);

        $this->assertNull($result);
    }

    public function testMatchFoundMultiFirst()
    {
        $this->insertFixtures([AgendaActivityFixtures::class]);
        $repository = $this->getRepository();

        $result = $repository->matchActivity('testActivity4', 1, false);

        $this->assertEquals(4004, $result);
    }

    public function testMatchFoundMultiLast()
    {
        $this->insertFixtures([AgendaActivityFixtures::class]);
        $repository = $this->getRepository();

        $result = $repository->matchActivity('testActivity5', 1, false);

        $this->assertEquals(4005, $result);
    }

    public function testMatchFoundMultiMiddle()
    {
        $this->insertFixtures([AgendaActivityFixtures::class]);
        $repository = $this->getRepository();

        $result = $repository->matchActivity('testActivity6', 1, false);

        $this->assertEquals(4006, $result);
    }

    public function testMatchNotFoundCreate()
    {
        $this->insertFixtures([AgendaActivityFixtures::class]);
        $repository = $this->getRepository();

        $result = $repository->matchActivity('testActivity10', 1, true);

        $this->assertEquals(4007, $result);
        $this->assertTableRowCount(7, 'gems__agenda_activities');
    }

    protected function getRepository()
    {
        $currentUserRepositoryProphecy = $this->prophesize(CurrentUserRepository::class);
        $currentUserRepositoryProphecy->getUserId(1);


        $cacheProphecy = $this->prophesize(CacheItemPoolInterface::class);
        $cacheItemProphecy = $this->prophesize(CacheItemInterface::class);
        $cacheProphecy->hasItem(Argument::any())->willReturn(false);
        $cacheProphecy->getItem(Argument::any())->willReturn($cacheItemProphecy->reveal());
        $cacheProphecy->save(Argument::type(CacheItemInterface::class))->willReturn(null);

        return new AgendaActivityRepository($this->db, $cacheProphecy->reveal(), $currentUserRepositoryProphecy->reveal());
    }
}
