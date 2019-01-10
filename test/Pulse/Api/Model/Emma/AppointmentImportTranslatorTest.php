<?php


namespace PulseTest\Rest\Api\Model\Emma;


use GemsTest\Rest\Test\ZendDbTestCase;
use PHPUnit\DbUnit\DataSet\YamlDataSet;
use Pulse\Api\Model\Emma\AppointmentImportTranslator;

class AppointmentImportTranslatorTest extends ZendDbTestCase
{
    /**
     * @var bool should Zend 2 adapter be loaded?
     */
    protected $loadZendDb2 = true;

    protected function getDataSet()
    {
        $file = str_replace('.php', '.yml', __FILE__);
        return new YamlDataSet($file);
    }

    public function testTranslateRowEmpty()
    {
        $translator = $this->getTranslator();
        $row = [];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Organization ID is needed for translations');
        $translator->translateRow($row);
    }

    public function testTranslateRowBareMinimum()
    {
        $translator = $this->getTranslator();
        $row = [
            'gap_id_organization' => 1
        ];
        $result = $translator->translateRow($row);
        $expectedResult = [
            'gap_id_organization' => 1,
            'gap_source' => 'emma',
            'gap_code' => 'A',
        ];
        $this->assertEquals($expectedResult, $result, 'Default settings not as expected');
    }

    public function testTranslateRowAllMatches()
    {
        $translator = $this->getTranslator();
        $row = [
            'gap_id_organization' => 1,
            'location' => 'somewhere',
            'attended_by' => 'someone',
            'activity' => 'some action',
        ];
        $result = $translator->translateRow($row);
        $expectedResult = [
            'gap_id_organization' => 1,
            'gap_source' => 'emma',
            'gap_code' => 'A',
            'gap_id_location' => 1,
            'gap_id_attended_by' => 2,
            'gap_id_activity' => 3,
            'location' => 'somewhere',
            'attended_by' => 'someone',
            'activity' => 'some action',
        ];
        $this->assertEquals($expectedResult, $result, 'Matched ids not as expected');
    }

    public function testFindEpisodeOfCareExists()
    {
        $translator = $this->getTranslator();
        $row = [
            'gap_id_organization' => 1,
            'episode_id' => 1
        ];
        $result = $translator->translateRow($row);
        $expectedResult = [
            'gap_id_organization' => 1,
            'gap_source' => 'emma',
            'gap_code' => 'A',
            'gap_id_episode' => '11',
            'episode_id' => 1
        ];
        $this->assertEquals($expectedResult, $result, 'episode of care id not as expected');
    }

    public function testFindEpisodeOfCareMissing()
    {
        $translator = $this->getTranslator();
        $row = [
            'gap_id_organization' => 1,
            'episode_id' => 999
        ];
        $result = $translator->translateRow($row);
        $expectedResult = [
            'gap_id_organization' => 1,
            'gap_source' => 'emma',
            'gap_code' => 'A',
            'gap_id_episode' => null,
            'episode_id' => 999
        ];
        $this->assertEquals($expectedResult, $result, 'episode of care id not as expected');
    }

    protected function getTranslator()
    {
        $agenda = $this->prophesize(\Gems_Agenda::class);
        $agenda->matchLocation('somewhere', 1)->willReturn(['glo_id_location' => 1]);
        $agenda->matchHealthcareStaff('someone', 1)->willReturn(2);
        $agenda->matchActivity('some action', 1)->willReturn(3);

        return new AppointmentImportTranslator($this->db, $agenda->reveal());
    }
}