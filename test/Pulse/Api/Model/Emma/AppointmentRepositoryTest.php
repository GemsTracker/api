<?php


namespace Pulse\Api\Model\Emma;


use GemsTest\Rest\Test\ZendDbTestCase;
use PHPUnit\DbUnit\DataSet\YamlDataSet;

class AppointmentRepositoryTest extends ZendDbTestCase
{
    protected $loadZendDb2 = true;

    protected function getDataSet()
    {
        $file = str_replace('.php', '.yml', __FILE__);
        return new YamlDataSet($file);
    }

    public function testGetAppointmentExistsBySourceIdExists()
    {
        $repository = $this->getRepositry();
        $result = $repository->getAppointmentExistsBySourceId('appointment1', 'test');

        $this->assertTrue($result, 'Appointment could not be found');
    }

    public function testGetAppointmentExistsBySourceIdMissing()
    {
        $repository = $this->getRepositry();
        $result = $repository->getAppointmentExistsBySourceId('missingAppointment', 'test');

        $this->assertFalse($result);
    }

    protected function getRepositry()
    {

        return new AppointmentRepository($this->db);
    }
}