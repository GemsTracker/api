<?php


namespace PulseTest\Rest\Api\Emma\Fhir\Model\Transformer;


use PHPUnit\Framework\TestCase;
use Pulse\Api\Emma\Fhir\Model\Transformer\PatientIdentifierTransformer;
use PulseTest\Rest\Api\Emma\Fhir\Model\MockPatientModel;

class PatientIdentifierTransformerTest extends TestCase
{
    use MockPatientModel;

    public function testFetchBsnFromIdentifiers()
    {
        $model = $this->getPatientModel();

        $data = [
            'identifier' => [
                [
                    'use' => 'official',
                    'system' => 'http://fhir.nl/fhir/NamingSystem/bsn',
                    'value' => '999911120',
                ]
            ]
        ];

        $transformer = new PatientIdentifierTransformer();

        $result = $transformer->transformRowBeforeSave($model, $data);

        $expected = $data;

        $expected['grs_ssn'] = '999911120';

        $this->assertEquals($expected, $result);
    }

    public function testZeroPrefixBsnWhenShort()
    {
        $model = $this->getPatientModel();

        $data = [
            'identifier' => [
                [
                    'use' => 'official',
                    'system' => 'http://fhir.nl/fhir/NamingSystem/bsn',
                    'value' => '11222633',
                ]
            ]
        ];

        $transformer = new PatientIdentifierTransformer();

        $result = $transformer->transformRowBeforeSave($model, $data);

        $expected = $data;

        $expected['grs_ssn'] = '011222633';

        $this->assertEquals($expected, $result);
        $this->assertStringStartsWith('0', $result['grs_ssn']);

    }

    public function testInvalidBsn()
    {
        $model = $this->getPatientModel();

        $data = [
            'identifier' => [
                [
                    'use' => 'official',
                    'system' => 'http://fhir.nl/fhir/NamingSystem/bsn',
                    'value' => '123456789',
                ]
            ]
        ];

        $transformer = new PatientIdentifierTransformer();

        $result = $transformer->transformRowBeforeSave($model, $data);

        $expected = $data;

        $this->assertEquals($expected, $result);
    }

    public function testFetchPatientNrFromIdentifiers()
    {
        $model = $this->getPatientModel();

        $data = [
            'identifier' => [
                [
                    'system' => 'http://fhir.timeff.com/identifier/patientnummer',
                    'value' => '123456',
                ]
            ]
        ];

        $transformer = new PatientIdentifierTransformer();

        $result = $transformer->transformRowBeforeSave($model, $data);

        $expected = $data;

        $expected['gr2o_patient_nr'] = '123456';

        $this->assertEquals($expected, $result);
    }
}
