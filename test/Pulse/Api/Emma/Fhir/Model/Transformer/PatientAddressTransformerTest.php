<?php

namespace PulseTest\Rest\Api\Emma\Fhir\Model\Transformer;

use PHPUnit\Framework\TestCase;
use Pulse\Api\Emma\Fhir\Model\Transformer\PatientAddressTransformer;
use PulseTest\Rest\Api\Emma\Fhir\Model\MockPatientModel;

class PatientAddressTransformerTest extends TestCase
{
    use MockPatientModel;

    public function testAddressNull()
    {
        $model = $this->getPatientModel();

        $data = [
            'address' => null,
        ];

        $transformer = new PatientAddressTransformer();

        $result = $transformer->transformRowBeforeSave($model, $data);

        $expected = $data;
        $expected['grs_city'] = null;
        $expected['grs_zipcode'] = null;
        $expected['grs_iso_country'] = null;
        $expected['grs_address_1'] = null;


        $this->assertEquals($expected, $result);
    }

    public function testCity()
    {
        $model = $this->getPatientModel();

        $data = [
            'address' => [
                [
                    'use' => 'home',
                    'type' => 'physical',
                    'city' => 'Teststad'
                ]
            ],
        ];

        $transformer = new PatientAddressTransformer();

        $result = $transformer->transformRowBeforeSave($model, $data);

        $expected = $data;
        $expected['grs_city'] = 'Teststad';
        $expected['grs_zipcode'] = null;
        $expected['grs_iso_country'] = null;
        $expected['grs_address_1'] = null;

        $this->assertEquals($expected, $result);
    }

    public function testCityUppercase()
    {
        $model = $this->getPatientModel();

        $data = [
            'address' => [
                [
                    'use' => 'home',
                    'type' => 'physical',
                    'city' => 'TESTSTAD'
                ]
            ],
        ];

        $transformer = new PatientAddressTransformer();

        $result = $transformer->transformRowBeforeSave($model, $data);

        $expected = $data;
        $expected['grs_city'] = 'Teststad';
        $expected['grs_zipcode'] = null;
        $expected['grs_iso_country'] = null;
        $expected['grs_address_1'] = null;

        $this->assertEquals($expected, $result);
    }

    public function testPostalCode()
    {
        $model = $this->getPatientModel();

        $data = [
            'address' => [
                [
                    'use' => 'home',
                    'type' => 'physical',
                    'postalCode' => '1234 AA',
                ]
            ],
        ];

        $transformer = new PatientAddressTransformer();

        $result = $transformer->transformRowBeforeSave($model, $data);

        $expected = $data;
        $expected['grs_zipcode'] = '1234 AA';
        $expected['grs_city'] = null;
        $expected['grs_iso_country'] = null;
        $expected['grs_address_1'] = null;

        $this->assertEquals($expected, $result);
    }

    public function testCountry()
    {
        $model = $this->getPatientModel();

        $data = [
            'address' => [
                [
                    'use' => 'home',
                    'type' => 'physical',
                    '_country' => [
                        'extension' => [
                            [
                                'url' => 'http://nictiz.nl/fhir/StructureDefinition/code-specification',
                                'valueCodeableConcept' => [
                                    'coding' => [
                                        [
                                            'system' => 'urn:iso:std:iso:3166',
                                            'code' => 'NL',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ]
            ],
        ];

        $transformer = new PatientAddressTransformer();

        $result = $transformer->transformRowBeforeSave($model, $data);

        $expected = $data;
        $expected['grs_iso_country'] = 'NL';
        $expected['grs_city'] = null;
        $expected['grs_zipcode'] = null;
        $expected['grs_address_1'] = null;

        $this->assertEquals($expected, $result);
    }

    public function testAddress()
    {
        $model = $this->getPatientModel();

        $data = [
            'address' => [
                [
                    '_line' => [
                        [
                            'extension' => [
                                [
                                    'url' => 'http://hl7.org/fhir/StructureDefinition/iso21090-ADXP-streetName',
                                    'valueString' => 'Dorpstraat',
                                ],
                                [
                                    'url' => 'http://hl7.org/fhir/StructureDefinition/iso21090-ADXP-houseNumber',
                                    'valueString' => '1',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $transformer = new PatientAddressTransformer();

        $result = $transformer->transformRowBeforeSave($model, $data);

        $expected = $data;
        $expected['grs_address_1'] = 'Dorpstraat 1';
        $expected['grs_city'] = null;
        $expected['grs_zipcode'] = null;
        $expected['grs_iso_country'] = null;

        $this->assertEquals($expected, $result);
    }
}
