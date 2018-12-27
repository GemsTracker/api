<?php


namespace Pulse\Api\Model\Emma;


use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class RespondentImportTranslatorTest extends TestCase
{

    public function testTranslateRowOnceEmptyRow()
    {
        $translator = $this->getTranslator();

        $testRow = [];
        $expectedResult = [
            'grs_iso_lang' => 'nl',
            'gr2o_readonly' => 1,
        ];

        $result = $translator->translateRowOnce($testRow);
        $this->assertEquals($expectedResult, $result);
    }

    public function testTranslateRowOnceDeceased()
    {
        $translator = $this->getTranslator();

        $expectedResult = [
            'grs_iso_lang' => 'nl',
            'gr2o_readonly' => 1,
            'gr2o_reception_code' => 'deceased',
            'deceased' => true
        ];

        $testRow = [
            'deceased' => true,
        ];

        $result = $translator->translateRowOnce($testRow);
        $this->assertEquals($expectedResult, $result);
    }

    public function testTranslateRowOnceEmptyEmail()
    {
        $translator = $this->getTranslator();

        $testRow = [
            'patient_nr' => 1,
            'email' => '',
        ];

        $expectedResult = [
            'gr2o_patient_nr' => 1,
            'grs_iso_lang' => 'nl',
            'gr2o_readonly' => 1,
            'gr2o_email' => null
        ];

        $result = $translator->translateRowOnce($testRow);
        $this->assertEquals($expectedResult, $result);
    }

    public function testTranslateRowOnceWrongEmail()
    {
        $translator = $this->getTranslator();

        $testRow = [
            'patient_nr' => 1,
            'email' => 'somethingthatisclearlynotanemailaddress',
        ];

        $expectedResult = [
            'gr2o_patient_nr' => 1,
            'grs_iso_lang' => 'nl',
            'gr2o_readonly' => 1,
            'gr2o_email' => null
        ];

        $result = $translator->translateRowOnce($testRow);
        $this->assertEquals($expectedResult, $result);
    }

    public function testMatchRowToExistingPatientSmallSsn()
    {
        $translator = $this->getTranslator();
        $model = $this->getModel();
        $testRow = [
            'gr2o_patient_nr' => 1,
            'gr2o_id_organization' => 1,
            'grs_ssn' => '11222633',
        ];

        $expectedResult = [
            'gr2o_patient_nr' => 1,
            'gr2o_id_organization' => 1,
            'grs_ssn' => '011222633',
        ];

        $result = $translator->matchRowToExistingPatient($testRow, $model);
        $this->assertEquals($expectedResult, $result);
    }

    public function testMatchRowToExistingPatientWrongSsn()
    {
        $translator = $this->getTranslator();
        $model = $this->getModel();
        $testRow = [
            'gr2o_patient_nr' => 1,
            'gr2o_id_organization' => 1,
            'grs_ssn' => '1111',
        ];

        $expectedResult = [
            'gr2o_patient_nr' => 1,
            'gr2o_id_organization' => 1,
            'grs_ssn' => null,
        ];

        $result = $translator->matchRowToExistingPatient($testRow, $model);
        $this->assertEquals($expectedResult, $result);
    }

    public function testMatchRowToExistingPatientMatch()
    {
        $translator = $this->getTranslator();
        $model = $this->getModel();
        $testRow = [
            'gr2o_patient_nr' => 1,
            'gr2o_id_organization' => 1,
            'grs_ssn' => '111222333',
        ];

        $expectedResult = [
            'gr2o_patient_nr' => 1,
            'gr2o_id_organization' => 1,
            'grs_ssn' => '111222333',
        ];

        $result = $translator->matchRowToExistingPatient($testRow, $model);
        $this->assertEquals($expectedResult, $result);
    }

    protected function getModel()
    {
        $modelProphesize = $this->prophesize(\Gems_Model_RespondentModel::class);
        return $modelProphesize->reveal();
    }

    protected function getTranslator()
    {

        $respondentRepositoryProphecy = $this->prophesize(RespondentRepository::class);
        $respondentRepositoryProphecy->getPatientsBySsn('111222333')->willReturn();

        $loggerProphecy = $this->prophesize(LoggerInterface::class);

        return new RespondentImportTranslator($respondentRepositoryProphecy->reveal(), $loggerProphecy->reveal());
    }
}