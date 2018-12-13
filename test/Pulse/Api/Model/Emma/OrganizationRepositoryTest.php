<?php


namespace Pulse\Api\Model\Emma;


use GemsTest\Rest\Test\ZendDbTestCase;
use PHPUnit\DbUnit\DataSet\YamlDataSet;

class OrganizationRepositoryTest extends ZendDbTestCase
{
    protected $loadZendDb2 = true;

    protected function getDataSet()
    {
        $file = str_replace('.php', '.yml', __FILE__);
        return new YamlDataSet($file);
    }

    public function testGetOrganizationIdExists()
    {
        $repository = $this->getRepository();

        $result = $repository->getOrganizationId('  TEst Organization fkjhdfgkhdkgjh');
        $this->assertEquals(1, $result);
    }

    public function testGetOrganizationIdMissing()
    {
        $repository = $this->getRepository();

        $result = $repository->getOrganizationId('Missing organization');
        $this->assertNull($result);
    }

    public function testGetLocationFromOrganizationName()
    {
        $repository = $this->getRepository();

        $result = $repository->getLocationFromOrganizationName(' Test Organization on the server');
        $this->assertEquals('on the server', $result);

        $result2 = $repository->getLocationFromOrganizationName('Test Organization');
        $this->assertNull($result2);
    }

    public function testGetOrganizationTranslations()
    {
        $repository = $this->getRepository();

        $translations = [
            'Test Organization',
            'Missing Organization'
        ];
        $result = $repository->getOrganizationTranslations($translations);

        $this->assertEquals([1 => 'Test Organization'], $result);
    }

    protected function getRepository()
    {
        return new OrganizationRepository($this->db);
    }
}