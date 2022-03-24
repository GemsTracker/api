<?php

declare(strict_types=1);

namespace PulseTest\Rest\Api\Emma\Fhir\Repository;

use GemsTest\Rest\Test\DbTestCase;
use GemsTest\Rest\Test\LaminasDbFixtures;
use Laminas\Db\TableGateway\TableGateway;
use Pulse\Api\Emma\Fhir\Repository\EpisodeOfCareRepository;


class EpisodeOfCareRepositoryTest extends DbTestCase
{
    use LaminasDbFixtures;

    public function testDatabaseSomething()
    {
        $this->insertFixtures([EpisodeOfCareFixtures::class]);

        $this->assertTableRowCount(1, 'gems__episodes_of_care');
    }

    public function testGetEpisodeOfCareBySourceIdValid()
    {
        $this->insertFixtures([EpisodeOfCareFixtures::class]);

        $repository = $this->getRepository();
        $result = $repository->getEpisodeOfCareBySourceId('123', 'test');

        $this->assertEquals($result, 18);
    }

    public function testGetEpisodeOfCareBySourceIdUnkown()
    {
        $this->insertFixtures([EpisodeOfCareFixtures::class]);

        $repository = $this->getRepository();
        $result = $repository->getEpisodeOfCareBySourceId('987', 'test');

        $this->assertNull($result);
    }

    protected function getRepository()
    {
        return new EpisodeOfCareRepository($this->db);
    }
}
