<?php

namespace PulseTest\Rest\Api\Emma\Fhir\Model\Transformer;

use PHPUnit\Framework\TestCase;
use Pulse\Api\Emma\Fhir\Model\Transformer\PatientTelecomTransformer;
use PulseTest\Rest\Api\Emma\Fhir\Model\MockPatientModel;

class PatientTelecomTransformerTest extends TestCase
{
    use MockPatientModel;

    public function testTelecomNull()
    {
        $model = $this->getPatientModel();

        $data = [
            'telecom' => null,
        ];

        $transformer = new PatientTelecomTransformer();
        $result = $transformer->transformRowBeforeSave($model, $data);

        $expected = $data;
        $expected['gr2o_email'] = null;
        $expected['grs_phone_1'] = null;
        $expected['grs_phone_2'] = null;
        $expected['grs_phone_3'] = null;

        $this->assertEquals($expected, $result);
    }

    public function testEmailCorrect()
    {
        $model = $this->getPatientModel();

        $data = [
            'telecom' => [
                [
                    'system' => 'email',
                    'value' => 'janneke@test.nl',
                    'use' => 'home',
                ]
            ],
        ];

        $transformer = new PatientTelecomTransformer();
        $result = $transformer->transformRowBeforeSave($model, $data);

        $expected = $data;
        $expected['gr2o_email'] = 'janneke@test.nl';
        $expected['grs_phone_1'] = null;
        $expected['grs_phone_2'] = null;
        $expected['grs_phone_3'] = null;

        $this->assertEquals($expected, $result);
    }

    public function testEmailMultipleEntries()
    {
        $model = $this->getPatientModel();

        $data = [
            'telecom' => [
                [
                    'system' => 'email',
                    'value' => 'janneke@test.nl',
                    'use' => 'home',
                ],
                [
                    'system' => 'email',
                    'value' => 'janneke@work.nl',
                    'use' => 'office',
                ],
            ],
        ];

        $transformer = new PatientTelecomTransformer();
        $result = $transformer->transformRowBeforeSave($model, $data);

        $expected = $data;
        $expected['gr2o_email'] = 'janneke@test.nl';
        $expected['grs_phone_1'] = null;
        $expected['grs_phone_2'] = null;
        $expected['grs_phone_3'] = null;

        $this->assertEquals($expected, $result);
    }

    public function testInvalidEmail()
    {
        $model = $this->getPatientModel();

        $data = [
            'telecom' => [
                [
                    'system' => 'email',
                    'value' => 'janneke.test.nl',
                    'use' => 'home',
                ],
            ],
        ];

        $transformer = new PatientTelecomTransformer();
        $result = $transformer->transformRowBeforeSave($model, $data);

        $expected = $data;
        $expected['gr2o_email'] = null;
        $expected['grs_phone_1'] = null;
        $expected['grs_phone_2'] = null;
        $expected['grs_phone_3'] = null;

        $this->assertEquals($expected, $result);
    }

    public function testMobilePhoneNumber()
    {
        $model = $this->getPatientModel();

        $data = [
            'telecom' => [
                [
                    'system' => 'phone',
                    'value' => '0612345678',
                    'use' => 'mobile',
                ],
            ],
        ];

        $transformer = new PatientTelecomTransformer();
        $result = $transformer->transformRowBeforeSave($model, $data);

        $expected = $data;
        $expected['grs_phone_3'] = '0612345678';
        $expected['gr2o_email'] = null;
        $expected['grs_phone_1'] = null;
        $expected['grs_phone_2'] = null;

        $this->assertEquals($expected, $result);
    }

    public function testHomePhoneNumber()
    {
        $model = $this->getPatientModel();

        $data = [
            'telecom' => [
                [
                    'system' => 'phone',
                    'value' => '0401234567',
                    'use' => 'home',
                ],
            ],
        ];

        $transformer = new PatientTelecomTransformer();
        $result = $transformer->transformRowBeforeSave($model, $data);

        $expected = $data;
        $expected['grs_phone_1'] = '0401234567';
        $expected['gr2o_email'] = null;
        $expected['grs_phone_2'] = null;
        $expected['grs_phone_3'] = null;

        $this->assertEquals($expected, $result);
    }

    public function testWorkPhoneNumber()
    {
        $model = $this->getPatientModel();

        $data = [
            'telecom' => [
                [
                    'system' => 'phone',
                    'value' => '0800123456',
                    'use' => 'work',
                ],
            ],
        ];

        $transformer = new PatientTelecomTransformer();
        $result = $transformer->transformRowBeforeSave($model, $data);

        $expected = $data;
        $expected['grs_phone_2'] = '0800123456';
        $expected['gr2o_email'] = null;
        $expected['grs_phone_1'] = null;
        $expected['grs_phone_3'] = null;

        $this->assertEquals($expected, $result);
    }

    public function testInvalidPhoneNumber()
    {
        $model = $this->getPatientModel();

        $data = [
            'telecom' => [
                [
                    'system' => 'phone',
                    'value' => '123a45678',
                    'use' => 'mobile',
                ],
            ],
        ];

        $transformer = new PatientTelecomTransformer();
        $result = $transformer->transformRowBeforeSave($model, $data);

        $expected = $data;
        $expected['gr2o_email'] = null;
        $expected['grs_phone_1'] = null;
        $expected['grs_phone_2'] = null;
        $expected['grs_phone_3'] = null;

        $this->assertEquals($expected, $result);
    }
}
