<?php

declare(strict_types=1);


namespace PulseTest\Rest\Api\Emma\Fhir\Repository;


use GemsTest\Rest\Test\DbTestCase;
use GemsTest\Rest\Test\LaminasDbFixtures;
use Pulse\Api\Emma\Fhir\Repository\ConditionRepository;

class ConditionRepositoryTest extends DbTestCase
{
    use LaminasDbFixtures;

    public function testGetConditionBySourceIdValid()
    {
        $this->insertFixtures([ConditionFixtures::class]);

        $repository = $this->getRepository();
        $result = $repository->getConditionBySourceId('123', 'testSource');

        $expected = [
            'gmco_id_condition' => 501,
            'gmco_id_source' => '123',
            'gmco_id_episode_of_care' => 601,
        ];

        $this->assertEquals($expected, $result);
    }

    public function testGetConditionBySourceIdUnknown()
    {
        $this->insertFixtures([ConditionFixtures::class]);

        $repository = $this->getRepository();
        $result = $repository->getConditionBySourceId('987', 'testSource');

        $this->assertNull($result);
    }

    public function testGetEpisodeIdFromExistingCondition()
    {
        $this->insertFixtures([ConditionFixtures::class]);

        $repository = $this->getRepository();
        $result = $repository->getEpisodeOfCareIdFromConditionBySourceId('123', 'testSource');

        $this->assertEquals(601, $result);
    }

    public function testGetUnknownEpisodeFromExistingCondition()
    {
        $this->insertFixtures([ConditionFixtures::class]);

        $repository = $this->getRepository();
        $result = $repository->getEpisodeOfCareIdFromConditionBySourceId('456', 'testSource');

        $this->assertNull($result);
    }

    public function testGetUnknownEpisodeFromUnknownCondition()
    {
        $this->insertFixtures([ConditionFixtures::class]);

        $repository = $this->getRepository();
        $result = $repository->getEpisodeOfCareIdFromConditionBySourceId('987', 'testSource');

        $this->assertNull($result);
    }

    protected function getRepository()
    {
        return new ConditionRepository($this->db);
    }
}
