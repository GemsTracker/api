<?php

declare(strict_types=1);


namespace PulseTest\Rest\Api\Emma\Fhir\Repository;


use GemsTest\Rest\Test\DbTestCase;
use GemsTest\Rest\Test\LaminasDbFixtures;
use Pulse\Api\Emma\Fhir\Repository\AgendaStaffRepository;
use Pulse\Api\Emma\Fhir\Repository\CurrentUserRepository;

class AgendaStaffRepositoryTest extends DbTestCase
{
    use LaminasDbFixtures;

    public function testMatchNotFoundNoCreate()
    {
        $this->insertFixtures([AgendaStaffFixtures::class]);
        $repository = $this->getRepository();

        $this->assertTableRowCount(6, 'gems__agenda_staff');
        $result = $repository->matchStaff('testStaff999', 1, false);

        $this->assertNull($result);
        $this->assertTableRowCount(6, 'gems__agenda_staff');
    }

    public function testMatchFoundByName()
    {
        $this->insertFixtures([AgendaStaffFixtures::class]);
        $repository = $this->getRepository();

        $result = $repository->matchStaff('testStaff1', 1, false);

        $this->assertEquals(2001, $result);
    }

    public function testMatchFoundButNotActive()
    {
        $this->insertFixtures([AgendaStaffFixtures::class]);
        $repository = $this->getRepository();

        $result = $repository->matchStaff('testStaff2', 1, false);

        $this->assertNull($result);
    }

    public function testMatchFoundMultiFirst()
    {
        $this->insertFixtures([AgendaStaffFixtures::class]);
        $repository = $this->getRepository();

        $result = $repository->matchStaff('testStaff4', 1, false);

        $this->assertEquals(2004, $result);
    }

    public function testMatchFoundMultiLast()
    {
        $this->insertFixtures([AgendaStaffFixtures::class]);
        $repository = $this->getRepository();

        $result = $repository->matchStaff('testStaff5', 1, false);

        $this->assertEquals(2005, $result);
    }

    public function testMatchFoundMultiMiddle()
    {
        $this->insertFixtures([AgendaStaffFixtures::class]);
        $repository = $this->getRepository();

        $result = $repository->matchStaff('testStaff6', 1, false);

        $this->assertEquals(2006, $result);
    }

    public function testMatchNotFoundCreate()
    {
        $this->insertFixtures([AgendaStaffFixtures::class]);
        $repository = $this->getRepository();

        $result = $repository->matchStaff('testStaff10', 1, true);

        $this->assertEquals(2007, $result);
        $this->assertTableRowCount(7, 'gems__agenda_staff');
    }

    protected function getRepository()
    {
        $currentUserRepositoryProphecy = $this->prophesize(CurrentUserRepository::class);
        $currentUserRepositoryProphecy->getUserId(1);

        return new AgendaStaffRepository($this->db, $currentUserRepositoryProphecy->reveal());
    }
}
