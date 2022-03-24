<?php

declare(strict_types=1);


namespace PulseTest\Rest\Api\Emma\Fhir;

use PHPUnit\Framework\TestCase;
use Pulse\Api\Emma\Fhir\ExistingEpdPatientRepository;
use Pulse\Api\Emma\Fhir\Model\RespondentModel;
use Pulse\Api\Repository\RespondentRepository;

class ExistingPatientRepositoryTest extends TestCase
{
    public function testUnknownPatient()
    {
        $existingPatientRepository = $this->getRepository();

        $ssn = '666268538';
        $patientNr = 999;

        $result = $existingPatientRepository->getExistingPatients($ssn, $patientNr, 'emma');

        $this->assertNull($result);
    }

    public function testKnownSsnPatient()
    {
        $existingPatientRepository = $this->getRepository();

        $ssn = '111222333';
        $patientNr = 1;

        $result = $existingPatientRepository->getExistingPatients($ssn, $patientNr, 'emma');

        $expected = [
            [
                'gr2o_patient_nr' => 1,
                'gr2o_id_user' => 1,
                'grs_id_user' => 1,
                'gr2o_id_organization' => 1,
                'grs_ssn' => '111222333'
            ]
        ];

        $this->assertEquals($expected, $result);
    }

    public function testKnownSsnMultiplePatients()
    {
        $existingPatientRepository = $this->getRepository();

        $ssn = '454619224';
        $patientNr = 2;

        $result = $existingPatientRepository->getExistingPatients($ssn, $patientNr, 'emma');

        $expected = [
            [
                'gr2o_patient_nr' => 2,
                'gr2o_id_user' => 1,
                'grs_id_user' => 1,
                'gr2o_id_organization' => 1,
                'grs_ssn' => '454619224'
            ],
            [
                'gr2o_patient_nr' => 2,
                'gr2o_id_user' => 1,
                'grs_id_user' => 1,
                'gr2o_id_organization' => 2,
                'grs_ssn' => '454619224'
            ]
        ];

        $this->assertEquals($expected, $result);
    }

    public function testKnownSsnWithDifferentPatientNumber()
    {
        $existingPatientRepository = $this->getRepository();

        $ssn = '111222333';
        $patientNr = 3;

        $result = $existingPatientRepository->getExistingPatients($ssn, $patientNr, 'emma');

        $expected = [
            [
                'gr2o_patient_nr' => 3,
                'gr2o_id_user' => 1,
                'grs_id_user' => 1,
                'gr2o_id_organization' => 1,
                'grs_ssn' => '111222333',
                '__c_1_3_copy__gr2o_patient_nr__key_k_0_p_1__' => 1,
            ]
        ];

        $this->assertEquals($expected, $result);
    }

    public function testKnownSsnWithDifferentExistingPatientNumber()
    {
        $existingPatientRepository = $this->getRepository();

        $ssn = '111222333';
        $patientNr = 4;

        $this->expectException(\Exception::class);
        $result = $existingPatientRepository->getExistingPatients($ssn, $patientNr, 'emma');
    }



    public function testUnknownSsnKnownPatientNumber()
    {
        $existingPatientRepository = $this->getRepository();

        $ssn = '666268538';
        $patientNr = 5;

        $result = $existingPatientRepository->getExistingPatients($ssn, $patientNr, 'emma');

        $expected = [
            [
                'gr2o_patient_nr' => 5,
                'gr2o_id_user' => 1,
                'grs_id_user' => 1,
                'gr2o_id_organization' => 1,
                'grs_ssn' => '666268538'
            ]
        ];

        $this->assertEquals($expected, $result);
    }

    protected function getRepository()
    {
        $respondentRepositoryProphecy = $this->prophesize(RespondentRepository::class);
        $respondentRepositoryProphecy->getPatientsFromSsn('666268538', 'emma')->willReturn(null);
        $respondentRepositoryProphecy->getPatientsFromSsn('111222333', 'emma')->willReturn(
            [
                [
                    'gr2o_patient_nr' => 1,
                    'gr2o_id_user' => 1,
                    'grs_id_user' => 1,
                    'gr2o_id_organization' => 1,
                    'grs_ssn' => '111222333'
                ]
            ]
        );
        $respondentRepositoryProphecy->getPatientsFromSsn('454619224', 'emma')->willReturn(
            [
                [
                    'gr2o_patient_nr' => 2,
                    'gr2o_id_user' => 1,
                    'grs_id_user' => 1,
                    'gr2o_id_organization' => 1,
                    'grs_ssn' => '454619224'
                ],
                [
                    'gr2o_patient_nr' => 2,
                    'gr2o_id_user' => 1,
                    'grs_id_user' => 1,
                    'gr2o_id_organization' => 2,
                    'grs_ssn' => '454619224'
                ]
            ]
        );
        $respondentRepositoryProphecy->patientNrExistsInEpd(3, 'emma')->willReturn(false);
        $respondentRepositoryProphecy->patientNrExistsInEpd(4, 'emma')->willReturn(true);

        $respondentRepositoryProphecy->getPatientsFromPatientNr(999, 'emma')->willReturn(null);
        $respondentRepositoryProphecy->getPatientsFromPatientNr(5, 'emma')->willReturn([
            [
                'gr2o_patient_nr' => 5,
                'gr2o_id_user' => 1,
                'grs_id_user' => 1,
                'gr2o_id_organization' => 1,
            ],
        ]);

        return new ExistingEpdPatientRepository($respondentRepositoryProphecy->reveal());
    }
}
