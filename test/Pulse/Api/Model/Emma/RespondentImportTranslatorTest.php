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
            'grs_surname_prefix' => null,
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
            'deceased' => true,
            'grs_surname_prefix' => null
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
            'gr2o_email' => null,
            'grs_surname_prefix' => null,
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
            'gr2o_email' => null,
            'grs_surname_prefix' => null,
        ];

        $result = $translator->translateRowOnce($testRow);
        $this->assertEquals($expectedResult, $result);
    }

    public function testTranslateLastName()
    {
        $translator = $this->getTranslator();

        $testRow = [
            'patient_nr' => 1,
            'last_name' => 'Janssen',
        ];
        $expectedResult = [
            'grs_iso_lang' => 'nl',
            'gr2o_readonly' => 1,
            'grs_raw_surname_prefix' => null,
            'grs_surname_prefix' => null,
            'gr2o_patient_nr' => 1,
            'grs_raw_last_name' => 'Janssen',
            'grs_last_name' => 'Janssen',
        ];

        $result = $translator->translateRowOnce($testRow);
        $this->assertEquals($expectedResult, $result);
    }

    public function testTranslateSurnamePrefix()
    {
        $translator = $this->getTranslator();

        $testRow = [
            'patient_nr' => 1,
            'last_name' => 'Janssen',
            'surname_prefix' => 'van',
        ];
        $expectedResult = [
            'grs_iso_lang' => 'nl',
            'gr2o_readonly' => 1,
            'grs_raw_surname_prefix' => 'van',
            'grs_surname_prefix' => 'van',
            'gr2o_patient_nr' => 1,
            'grs_raw_last_name' => 'Janssen',
            'grs_last_name' => 'Janssen',
        ];

        $result = $translator->translateRowOnce($testRow);
        $this->assertEquals($expectedResult, $result);
    }

    public function testTranslateNameOrderNoPartnerName()
    {
        $translator = $this->getTranslator();



        $testRow = [
            'patient_nr' => 1,
            'last_name' => 'Janssen',
            'surname_prefix' => 'van',
            'last_name_order' => 'surname',
        ];

        $lastNameOrderOptions = [
            'surname',
            'surname, partner name',
            'partner name',
            'partner name, surname',
            'test',
        ];

        $expectedResult = [
            'grs_iso_lang' => 'nl',
            'gr2o_readonly' => 1,
            'grs_raw_surname_prefix' => 'van',
            'grs_surname_prefix' => 'van',
            'gr2o_patient_nr' => 1,
            'grs_raw_last_name' => 'Janssen',
            'grs_last_name' => 'Janssen',
            'grs_last_name_order' => 'surname'
        ];

        foreach($lastNameOrderOptions as $lastNameOrderOption) {
            $testRow['last_name_order'] = $lastNameOrderOption;
            $expectedResult['grs_last_name_order'] = $lastNameOrderOption;

            $result = $translator->translateRowOnce($testRow);
            $this->assertEquals($expectedResult, $result, sprintf('last name order: %s', $lastNameOrderOption));
        }
    }

    public function testTranslateNameOrderPartnerName()
    {
        $translator = $this->getTranslator();

        $lastNameOrderOptions = [
            'surname' => [
                'grs_surname_prefix' => 'van',
                'grs_last_name' => 'Janssen',
            ],
            'surname, partner name' => [
                'grs_surname_prefix' => 'van',
                'grs_last_name' => 'Janssen - de Jong',
            ],
            'partner name' => [
                'grs_surname_prefix' => 'de',
                'grs_last_name' => 'Jong',
            ],
            'partner name, surname' => [
                'grs_surname_prefix' => 'de',
                'grs_last_name' => 'Jong - van Janssen',
            ],
            'test' => [
                'grs_surname_prefix' => 'van',
                'grs_last_name' => 'Janssen',
            ],
        ];

        $testRow = [
            'patient_nr' => 1,
            'last_name' => 'Janssen',
            'surname_prefix' => 'van',
            'last_name_order' => 'surname',
            'partner_last_name' => 'Jong',
            'partner_surname_prefix' => 'de',
        ];
        $expectedResult = [
            'grs_iso_lang' => 'nl',
            'gr2o_readonly' => 1,
            'gr2o_patient_nr' => 1,
            'grs_raw_last_name' => 'Janssen',
            'grs_raw_surname_prefix' => 'van',
            'grs_partner_last_name' => 'Jong',
            'grs_partner_surname_prefix' => 'de',
        ];

        foreach($lastNameOrderOptions as $lastNameOrderOption => $expectedSettings) {
            $testRow['last_name_order'] = $lastNameOrderOption;
            $expectedResult['grs_last_name_order'] = $lastNameOrderOption;
            $expectedResult = $expectedSettings + $expectedResult;

            $result = $translator->translateRowOnce($testRow);
            $this->assertEquals($expectedResult, $result, sprintf('last name order: %s', $lastNameOrderOption));
        }
    }

    public function testTranslateNameOrderPartnerNameWithoutPrefix()
    {
        $translator = $this->getTranslator();

        $lastNameOrderOptions = [
            'surname' => [
                'grs_surname_prefix' => 'van',
                'grs_last_name' => 'Janssen',
            ],
            'surname, partner name' => [
                'grs_surname_prefix' => 'van',
                'grs_last_name' => 'Janssen - Jong',
                'grs_partner_surname_prefix' => null,
            ],
            'partner name' => [
                'grs_surname_prefix' => null,
                'grs_last_name' => 'Jong',
                'grs_partner_surname_prefix' => null,
            ],
            'partner name, surname' => [
                'grs_surname_prefix' => null,
                'grs_last_name' => 'Jong - van Janssen',
                'grs_partner_surname_prefix' => null,
            ],
            'test' => [
                'grs_surname_prefix' => 'van',
                'grs_last_name' => 'Janssen',
            ],
        ];

        $testRow = [
            'patient_nr' => 1,
            'last_name' => 'Janssen',
            'surname_prefix' => 'van',
            'last_name_order' => 'surname',
            'partner_last_name' => 'Jong',
        ];
        $expectedBaseResult = [
            'grs_iso_lang' => 'nl',
            'gr2o_readonly' => 1,
            'gr2o_patient_nr' => 1,
            'grs_raw_last_name' => 'Janssen',
            'grs_raw_surname_prefix' => 'van',
            'grs_partner_last_name' => 'Jong',
        ];

        foreach($lastNameOrderOptions as $lastNameOrderOption => $expectedSettings) {
            $testRow['last_name_order'] = $lastNameOrderOption;
            $expectedResult = $expectedSettings + $expectedBaseResult;
            $expectedResult['grs_last_name_order'] = $lastNameOrderOption;

            $result = $translator->translateRowOnce($testRow);
            $this->assertEquals($expectedResult, $result, sprintf('last name order: %s', $lastNameOrderOption));
        }
    }

    public function testTranslateNameOrderPartnerNameWithoutSurnamePrefix()
    {
        $translator = $this->getTranslator();

        $lastNameOrderOptions = [
            'surname' => [
                'grs_surname_prefix' => null,
                'grs_last_name' => 'Janssen',
            ],
            'surname, partner name' => [
                'grs_surname_prefix' => null,
                'grs_last_name' => 'Janssen - Jong',
                'grs_partner_surname_prefix' => null,
            ],
            'partner name' => [
                'grs_surname_prefix' => null,
                'grs_last_name' => 'Jong',
                'grs_partner_surname_prefix' => null,
            ],
            'partner name, surname' => [
                'grs_surname_prefix' => null,
                'grs_last_name' => 'Jong - Janssen',
                'grs_partner_surname_prefix' => null,
            ],
            'test' => [
                'grs_surname_prefix' => null,
                'grs_last_name' => 'Janssen',
            ],
        ];

        $testRow = [
            'patient_nr' => 1,
            'last_name' => 'Janssen',
            'last_name_order' => 'surname',
            'partner_last_name' => 'Jong',
        ];
        $expectedBaseResult = [
            'grs_iso_lang' => 'nl',
            'gr2o_readonly' => 1,
            'gr2o_patient_nr' => 1,
            'grs_raw_last_name' => 'Janssen',
            'grs_raw_surname_prefix' => null,
            'grs_partner_last_name' => 'Jong',
        ];

        foreach($lastNameOrderOptions as $lastNameOrderOption => $expectedSettings) {
            $testRow['last_name_order'] = $lastNameOrderOption;
            $expectedResult = $expectedSettings + $expectedBaseResult;
            $expectedResult['grs_last_name_order'] = $lastNameOrderOption;

            $result = $translator->translateRowOnce($testRow);
            $this->assertEquals($expectedResult, $result, sprintf('last name order: %s', $lastNameOrderOption));
        }
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
            'grs_id_user' => 1,
            'gr2o_id_user' => 1,
            'new_respondent' => false,
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
            'grs_id_user' => 1,
            'gr2o_id_user' => 1,
            'new_respondent' => false,
        ];

        $result = $translator->matchRowToExistingPatient($testRow, $model);
        $this->assertEquals($expectedResult, $result);
    }

    public function testMatchRowToNewPatientWrongSsn()
    {
        $translator = $this->getTranslator();
        $model = $this->getModel();
        $testRow = [
            'gr2o_patient_nr' => 2,
            'gr2o_id_organization' => 1,
            'grs_ssn' => '1111',
        ];

        $expectedResult = [
            'gr2o_patient_nr' => 2,
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
            'gr2o_id_user' => 1,
            'grs_id_user' => 1,
            'new_respondent' => false,
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
        $respondentRepositoryProphecy->getPatientsBySsn('111222333')->willReturn([['gr2o_patient_nr' => 1, 'gr2o_id_user' => 1, 'grs_id_user' => 1, 'gr2o_id_organization' => 1, 'grs_ssn' => '111222333']]);
        $respondentRepositoryProphecy->getPatientsBySsn('011222633')->willReturn([['gr2o_patient_nr' => 1, 'gr2o_id_user' => 1, 'grs_id_user' => 1, 'gr2o_id_organization' => 1, 'grs_ssn' => '011222633']]);
        $respondentRepositoryProphecy->getPatientId(1,1)->willReturn(1);
        $respondentRepositoryProphecy->getPatient(1,1)->willReturn(['gr2o_id_user' => 1, 'grs_ssn' => '111222333']);
        $respondentRepositoryProphecy->getPatient(2,1)->willReturn(false);

        $loggerProphecy = $this->prophesize(LoggerInterface::class);
        $respondentErrorLoggerProphecy = $this->prophesize(LoggerInterface::class);

        return new RespondentImportTranslator($respondentRepositoryProphecy->reveal(), $loggerProphecy->reveal(), $respondentErrorLoggerProphecy->reveal());
    }
}
