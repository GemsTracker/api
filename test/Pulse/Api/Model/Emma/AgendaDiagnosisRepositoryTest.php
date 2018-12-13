<?php


namespace Pulse\Api\Model\Emma;


use GemsTest\Rest\Test\ZendDbTestCase;
use PHPUnit\DbUnit\DataSet\YamlDataSet;
use Pulse\Api\Model\DiagnosisModel;
use Zalt\Loader\ProjectOverloader;
use Zend\Db\Sql\Sql;

class AgendaDiagnosisRepositoryTest extends ZendDbTestCase
{
    protected $loadZendDb1 = true;
    protected $loadZendDb2 = true;

    protected function getDataSet()
    {
        $file = str_replace('.php', '.yml', __FILE__);
        return new YamlDataSet($file);
    }

    public function testMatchDiagnosisCode()
    {
        $agendaDiagnosisRepository = $this->getAgendaDiagnosisRepository();
        $result = $agendaDiagnosisRepository->matchDiagnosis(1, 'test');

        $this->assertEquals(1, $result, 'Wrong diagnosis code found');

        // A second time to check if it is stored in the class successfully
        $result = $agendaDiagnosisRepository->matchDiagnosis(1, 'test');
        $this->assertEquals(1, $result, 'The diagnosis code saved in the class does not match expected result');
    }

    public function testMatchUnknownDiagnosisCode()
    {
        $agendaDiagnosisRepository = $this->getAgendaDiagnosisRepository();
        $result = $agendaDiagnosisRepository->matchDiagnosis(2, 'test');

        $this->assertEquals(2, $result, 'Wrong diagnosis code found');

        $sql = new Sql($this->db);
        $select = $sql->select();
        $select->from('gems__agenda_diagnoses')
            ->columns(['gad_diagnosis_code','gad_description','gad_coding_method','gad_code','gad_source','gad_id_in_source','gad_active','gad_filter'])
            ->where(['gad_diagnosis_code' => 2]);

        $statement = $sql->prepareStatementForSqlObject($select);
        $resultset = $statement->execute();
        $result = $resultset->current();

        $expectedResult = [
            'gad_diagnosis_code' => '2',
            'gad_description' => null,
            'gad_coding_method' => 'DBC',
            'gad_code' => null,
            'gad_source' => 'test',
            'gad_id_in_source' => null,
            'gad_active' => '1',
            'gad_filter' => '0',
        ];

        $this->assertEquals($expectedResult, $result);
    }

    protected function getAgendaDiagnosisRepository()
    {
        \Gems_Model::setCurrentUserId(1);
        $diagnosisModel = new DiagnosisModel();


        $loaderProphecy  = $this->prophesize(ProjectOverloader::class);
        $loaderProphecy->create(DiagnosisModel::class)->willReturn($diagnosisModel);

        return new AgendaDiagnosisRepository($this->db, $loaderProphecy->reveal());
    }
}