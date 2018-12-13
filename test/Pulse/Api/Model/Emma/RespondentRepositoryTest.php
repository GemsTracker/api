<?php


namespace Pulse\Api\Model\Emma;


use GemsTest\Rest\Test\ZendDbTestCase;
use PHPUnit\DbUnit\DataSet\YamlDataSet;

class RespondentRepositoryTest extends ZendDbTestCase
{
    protected $loadZendDb2 = true;

    protected function getDataSet()
    {
        $file = str_replace('.php', '.yml', __FILE__);
        return new YamlDataSet($file);
    }

    public function testGetPatientIdExists()
    {
        $repository = $this->getRepository();
        $result = $repository->getPatientId('T001', 1);
        $this->assertEquals(1, $result);
    }

    public function testGetPatientIdMissing()
    {
        $repository = $this->getRepository();
        $result = $repository->getPatientId('T010', 1);
        $this->assertFalse($result);
    }

    public function testGetPatientBySsn()
    {
        $repository = $this->getRepository();
        $result = $repository->getPatientBySsn('1234567890');
        $this->assertEquals(1, $result);

        $result2 = $repository->getPatientBySsn('4567');
        $this->assertFalse($result2);
    }

    public function testGetPatientsBySsn()
    {
        $repository = $this->getRepository();
        $result = $repository->getPatientsbySsn('1234567890');
        $this->assertCount(2, $result);
        $this->assertEquals(1, $result[0]['grs_id_user']);
        $this->assertEquals(1, $result[1]['grs_id_user']);

        $result2 = $repository->getPatientsBySsn('4567');
        $this->assertNull($result2);
    }

    protected function getRepository()
    {
        return new RespondentRepository($this->db);
    }
}