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
        $result = $repository->getAppointmentDataBySourceId('appointment1', 'test');

        $exprectedAppointmentResult = $this->db->query('SELECT * FROM gems__appointments WHERE gap_id_appointment = ?', [1]);
        $expected = (array)$exprectedAppointmentResult->current();

        $this->assertEquals($expected, $result, 'Appointment could not be found');
    }

    public function testGetAppointmentExistsBySourceIdMissing()
    {
        $repository = $this->getRepositry();
        $result = $repository->getAppointmentDataBySourceId('missingAppointment', 'test');

        $this->assertFalse($result);
    }

    public function testGetLatestAppointmentVersionNone()
    {
        $repository = $this->getRepositry();
        $result = $repository->getLatestAppointmentVersion('appointment1', 'test');

        $this->assertEquals(0, $result, 'Appointment version not correct');

    }

    public function testGetLatestAppointmentVersionExists()
    {
        $this->db->query("UPDATE gems__appointments SET gap_id_in_source = ? WHERE gap_id_appointment = ?", ['appointment1_v2', 1]);

        $repository = $this->getRepositry();
        $result = $repository->getLatestAppointmentVersion('appointment1', 'test');
        $this->assertEquals(2, $result, 'Appointment version not correct');
    }

    protected function getRepositry()
    {

        return new AppointmentRepository($this->db);
    }
}