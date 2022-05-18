<?php

declare(strict_types=1);


namespace PulseTest\Rest\Api\Emma\Fhir\Repository;


use GemsTest\Rest\Test\DbTestCase;
use GemsTest\Rest\Test\LaminasDbFixtures;
use Pulse\Api\Emma\Fhir\Repository\AppointmentRepository;

class AppointmentRepositoryTest extends DbTestCase
{
    use LaminasDbFixtures;

    public function testGetValidAppointment()
    {
        $this->insertFixtures([AppointmentFixtures::class]);

        $repository = $this->getRepository();

        $result = $repository->getAppointmentFromSourceId('123', 'testEpdName');

        $expected = [
            'gap_id_appointment' => 801,
            'gap_id_organization' => 1,
        ];

        $this->assertEquals($expected, $result);
    }

    public function testGetValidAppointmentWithoutEpd()
    {
        $this->insertFixtures([AppointmentFixtures::class]);

        $repository = $this->getRepository();

        $result = $repository->getAppointmentFromSourceId('123');

        $expected = [
            'gap_id_appointment' => 801,
            'gap_id_organization' => 1,
        ];

        $this->assertEquals($expected, $result);
    }

    public function testGetUnknownAppointment()
    {
        $this->insertFixtures([AppointmentFixtures::class]);

        $repository = $this->getRepository();

        $result = $repository->getAppointmentFromSourceId('841', 'testEpdName');

        $this->assertNull($result);
    }

    public function testGetUnknownAppointmentDueToEpdName()
    {
        $this->insertFixtures([AppointmentFixtures::class]);

        $repository = $this->getRepository();

        $result = $repository->getAppointmentFromSourceId('123', 'unknownEpd');

        $this->assertNull($result);
    }

    protected function getRepository()
    {
        return new AppointmentRepository($this->db);
    }
}
