<?php

namespace PulseTest\Rest\Api\Emma\Fhir\Model\Transformer;


use Gems\Rest\Exception\IncorrectDataException;
use Gems\Rest\Exception\MissingDataException;
use PHPUnit\Framework\TestCase;
use Pulse\Api\Emma\Fhir\Model\Transformer\PatientNameTransformer;
use PulseTest\Rest\Api\Emma\Fhir\Model\MockPatientModel;

class PatientNameTransformerTest extends TestCase
{
    use MockPatientModel;

    public function testNameMissing()
    {
        $model = $this->getPatientModel();

        $data = [];

        $transformer = new PatientNameTransformer();

        $this->expectException(MissingDataException::class);
        $transformer->transformRowBeforeSave($model, $data);
    }

    public function testNameNull()
    {
        $model = $this->getPatientModel();

        $data = [
            'name' => null,
        ];

        $transformer = new PatientNameTransformer();

        $this->expectException(MissingDataException::class);
        $transformer->transformRowBeforeSave($model, $data);
    }

    public function testFamilyMissing()
    {
        $model = $this->getPatientModel();

        $data = [
            'name' => [
                [
                    'use' => 'official',
                ],
            ],
        ];

        $transformer = new PatientNameTransformer();

        $this->expectException(MissingDataException::class);
        $transformer->transformRowBeforeSave($model, $data);
    }

    public function testFamilyNull()
    {
        $model = $this->getPatientModel();

        $data = [
            'name' => [
                [
                    'use' => 'official',
                    'family' => null,
                ],
            ],
        ];

        $transformer = new PatientNameTransformer();

        $this->expectException(MissingDataException::class);
        $transformer->transformRowBeforeSave($model, $data);
    }

    public function testFamilyCorrect()
    {
        $model = $this->getPatientModel();

        $data = [
            'name' => [
                [
                    'use' => 'official',
                    'family' => 'Jansen',
                ],
            ],
        ];

        $transformer = new PatientNameTransformer();

        $result = $transformer->transformRowBeforeSave($model, $data);

        $expected = $data;

        $expected['grs_last_name'] = 'Jansen';
        $expected['grs_last_name_order'] = 'surname';
        $expected['grs_surname_prefix'] = null;

        $this->assertEquals($expected, $result);
    }

    public function testFamilyExtendedOwnPrefixOnly()
    {
        $model = $this->getPatientModel();

        $data = [
            'name' => [
                [
                    'use' => 'official',
                    'family' => 'Jansen',
                    '_family' => [
                        'extension' => [
                            [
                                'url' => 'http://hl7.org/fhir/StructureDefinition/humanname-own-prefix',
                                'valueString' => 'van',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $transformer = new PatientNameTransformer();

        $result = $transformer->transformRowBeforeSave($model, $data);

        $expected = $data;

        $expected['grs_last_name'] = 'Jansen';
        $expected['grs_last_name_order'] = 'surname';
        $expected['grs_surname_prefix'] = null;
        $expected['grs_raw_surname_prefix'] = 'van';

        $this->assertEquals($expected, $result);
    }

    public function testFamilyExtendedDifferenceWithFamily()
    {
        $model = $this->getPatientModel();

        $data = [
            'name' => [
                [
                    'extension' => [
                        [
                            'url' => 'http://hl7.org/fhir/StructureDefinition/humanname-assembly-order',
                            'valueCode' => 'NL1',
                        ],
                    ],
                    'use' => 'official',
                    'family' => 'Jansen',
                    '_family' => [
                        'extension' => [
                            [
                                'url' => 'http://hl7.org/fhir/StructureDefinition/humanname-own-name',
                                'valueString' => 'Janssen',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $transformer = new PatientNameTransformer();

        $result = $transformer->transformRowBeforeSave($model, $data);

        $expected = $data;

        $expected['grs_last_name'] = 'Janssen';
        $expected['grs_raw_last_name'] = 'Janssen';
        $expected['grs_last_name_order'] = 'surname';
        $expected['grs_surname_prefix'] = null;

        $this->assertEquals($expected, $result);
    }

    public function testFamilyExtendedPartnerNameNoOrder()
    {
        $model = $this->getPatientModel();

        $data = [
            'name' => [
                [
                    'use' => 'official',
                    'family' => 'Jansen',
                    '_family' => [
                        'extension' => [
                            [
                                'url' => 'http://hl7.org/fhir/StructureDefinition/humanname-own-name',
                                'valueString' => 'Jansen',
                            ],
                            [
                                'url' => 'http://hl7.org/fhir/StructureDefinition/humanname-partner-name',
                                'valueString' => 'Jong',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $transformer = new PatientNameTransformer();

        $result = $transformer->transformRowBeforeSave($model, $data);

        $expected = $data;

        $expected['grs_last_name'] = 'Jansen';
        $expected['grs_raw_last_name'] = 'Jansen';
        $expected['grs_partner_last_name'] = 'Jong';
        $expected['grs_last_name_order'] = 'surname';
        $expected['grs_surname_prefix'] = null;

        $this->assertEquals($expected, $result);
    }

    public function testFamilyExtendedOwnNameOnly()
    {
        $model = $this->getPatientModel();

        $data = [
            'name' => [
                [
                    'extension' => [
                        [
                            'url' => 'http://hl7.org/fhir/StructureDefinition/humanname-assembly-order',
                            'valueCode' => 'NL1',
                        ],
                    ],
                    'use' => 'official',
                    'family' => 'Jansen',
                    '_family' => [
                        'extension' => [
                            [
                                'url' => 'http://hl7.org/fhir/StructureDefinition/humanname-own-name',
                                'valueString' => 'Jansen',
                            ],
                            [
                                'url' => 'http://hl7.org/fhir/StructureDefinition/humanname-partner-prefix',
                                'valueString' => 'de',
                            ],
                            [
                                'url' => 'http://hl7.org/fhir/StructureDefinition/humanname-partner-name',
                                'valueString' => 'Jong',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $transformer = new PatientNameTransformer();

        $result = $transformer->transformRowBeforeSave($model, $data);

        $expected = $data;

        $expected['grs_last_name'] = 'Jansen';
        $expected['grs_raw_last_name'] = 'Jansen';
        $expected['grs_partner_last_name'] = 'Jong';
        $expected['grs_partner_surname_prefix'] = 'de';
        $expected['grs_last_name_order'] = 'surname';
        $expected['grs_surname_prefix'] = null;

        $this->assertEquals($expected, $result);
    }

    public function testFamilyExtendedPartnerNameOnly()
    {
        $model = $this->getPatientModel();

        $data = [
            'name' => [
                [
                    'extension' => [
                        [
                            'url' => 'http://hl7.org/fhir/StructureDefinition/humanname-assembly-order',
                            'valueCode' => 'NL2',
                        ],
                    ],
                    'use' => 'official',
                    'family' => 'Jansen',
                    '_family' => [
                        'extension' => [
                            [
                                'url' => 'http://hl7.org/fhir/StructureDefinition/humanname-own-name',
                                'valueString' => 'Jansen',
                            ],
                            [
                                'url' => 'http://hl7.org/fhir/StructureDefinition/humanname-partner-prefix',
                                'valueString' => 'de',
                            ],
                            [
                                'url' => 'http://hl7.org/fhir/StructureDefinition/humanname-partner-name',
                                'valueString' => 'Jong',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $transformer = new PatientNameTransformer();

        $result = $transformer->transformRowBeforeSave($model, $data);

        $expected = $data;

        $expected['grs_last_name'] = 'Jong';
        $expected['grs_surname_prefix'] = 'de';
        $expected['grs_raw_last_name'] = 'Jansen';
        $expected['grs_partner_last_name'] = 'Jong';
        $expected['grs_partner_surname_prefix'] = 'de';
        $expected['grs_last_name_order'] = 'partner name';

        $this->assertEquals($expected, $result);
    }

    public function testFamilyExtendedPartnerNameFirst()
    {
        $model = $this->getPatientModel();

        $data = [
            'name' => [
                [
                    'extension' => [
                        [
                            'url' => 'http://hl7.org/fhir/StructureDefinition/humanname-assembly-order',
                            'valueCode' => 'NL3',
                        ],
                    ],
                    'use' => 'official',
                    'family' => 'Jansen',
                    '_family' => [
                        'extension' => [
                            [
                                'url' => 'http://hl7.org/fhir/StructureDefinition/humanname-own-name',
                                'valueString' => 'Jansen',
                            ],
                            [
                                'url' => 'http://hl7.org/fhir/StructureDefinition/humanname-partner-prefix',
                                'valueString' => 'de',
                            ],
                            [
                                'url' => 'http://hl7.org/fhir/StructureDefinition/humanname-partner-name',
                                'valueString' => 'Jong',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $transformer = new PatientNameTransformer();

        $result = $transformer->transformRowBeforeSave($model, $data);

        $expected = $data;

        $expected['grs_last_name'] = 'Jong - Jansen';
        $expected['grs_surname_prefix'] = 'de';
        $expected['grs_raw_last_name'] = 'Jansen';
        $expected['grs_partner_last_name'] = 'Jong';
        $expected['grs_partner_surname_prefix'] = 'de';
        $expected['grs_last_name_order'] = 'partner name, surname';

        $this->assertEquals($expected, $result);
    }

    public function testFamilyExtendedOwnNameFirst()
    {
        $model = $this->getPatientModel();

        $data = [
            'name' => [
                [
                    'extension' => [
                        [
                            'url' => 'http://hl7.org/fhir/StructureDefinition/humanname-assembly-order',
                            'valueCode' => 'NL4',
                        ],
                    ],
                    'use' => 'official',
                    'family' => 'Jansen',
                    '_family' => [
                        'extension' => [
                            [
                                'url' => 'http://hl7.org/fhir/StructureDefinition/humanname-own-name',
                                'valueString' => 'Jansen',
                            ],
                            [
                                'url' => 'http://hl7.org/fhir/StructureDefinition/humanname-partner-prefix',
                                'valueString' => 'de',
                            ],
                            [
                                'url' => 'http://hl7.org/fhir/StructureDefinition/humanname-partner-name',
                                'valueString' => 'Jong',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $transformer = new PatientNameTransformer();

        $result = $transformer->transformRowBeforeSave($model, $data);

        $expected = $data;

        $expected['grs_last_name'] = 'Jansen - de Jong';
        $expected['grs_raw_last_name'] = 'Jansen';
        $expected['grs_partner_last_name'] = 'Jong';
        $expected['grs_partner_surname_prefix'] = 'de';
        $expected['grs_last_name_order'] = 'surname, partner name';
        $expected['grs_surname_prefix'] = null;

        $this->assertEquals($expected, $result);
    }

    public function testGivenNull()
    {
        $model = $this->getPatientModel();

        $data = [
            'name' => [
                [
                    'use' => 'official',
                    'family' => 'Jansen',
                    'given' => null,
                ],
            ],
        ];

        $transformer = new PatientNameTransformer();

        $result = $transformer->transformRowBeforeSave($model, $data);

        $expected = $data;

        $expected['grs_last_name'] = 'Jansen';
        $expected['grs_last_name_order'] = 'surname';
        $expected['grs_surname_prefix'] = null;

        $this->assertEquals($expected, $result);
    }

    public function testGivenString()
    {
        $model = $this->getPatientModel();

        $data = [
            'name' => [
                [
                    'use' => 'official',
                    'family' => 'Jansen',
                    'given' => 'Janneke',
                ],
            ],
        ];

        $transformer = new PatientNameTransformer();

        $result = $transformer->transformRowBeforeSave($model, $data);

        $expected = $data;

        $expected['grs_last_name'] = 'Jansen';
        $expected['grs_last_name_order'] = 'surname';
        $expected['grs_first_name'] = 'Janneke';
        $expected['grs_surname_prefix'] = null;

        $this->assertEquals($expected, $result);
    }

    public function testGivenArrayMissingHelper()
    {
        $model = $this->getPatientModel();

        $data = [
            'name' => [
                [
                    'use' => 'official',
                    'family' => 'Jansen',
                    'given' => ['Janneke'],
                ],
            ],
        ];

        $transformer = new PatientNameTransformer();

        $this->expectException(IncorrectDataException::class);
        $transformer->transformRowBeforeSave($model, $data);
    }

    public function testGivenArrayWrongHelper()
    {
        $model = $this->getPatientModel();

        $data = [
            'name' => [
                [
                    'use' => 'official',
                    'family' => 'Jansen',
                    'given' => ['Janneke'],
                    '_given' => [
                        'extension' => [
                            'url' => 'https://someunknowndefinition.nl',
                            'AA',
                        ],
                    ],
                ],
            ],
        ];

        $transformer = new PatientNameTransformer();

        $result = $transformer->transformRowBeforeSave($model, $data);

        $expected = $data;
        $expected['grs_last_name'] = 'Jansen';
        $expected['grs_last_name_order'] = 'surname';
        $expected['grs_surname_prefix'] = null;

        $this->assertEquals($expected, $result);
    }

    public function testGivenArrayWithBirthName()
    {
        $model = $this->getPatientModel();

        $data = [
            'name' => [
                [
                    'use' => 'official',
                    'family' => 'Jansen',
                    'given' => ['Janneke'],
                    '_given' => [
                        [
                            'extension' => [
                                [
                                    'url' => 'http://hl7.org/fhir/StructureDefinition/iso21090-EN-qualifier',
                                    'valueCode' => 'BR',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $transformer = new PatientNameTransformer();

        $result = $transformer->transformRowBeforeSave($model, $data);

        $expected = $data;

        $expected['grs_last_name'] = 'Jansen';
        $expected['grs_last_name_order'] = 'surname';
        $expected['grs_first_name'] = 'Janneke';
        $expected['grs_surname_prefix'] = null;

        $this->assertEquals($expected, $result);
    }

    public function testGivenArrayWithInitials()
    {
        $model = $this->getPatientModel();

        $data = [
            'name' => [
                [
                    'use' => 'official',
                    'family' => 'Jansen',
                    'given' => ['J.'],
                    '_given' => [
                        [
                            'extension' => [
                                [
                                    'url' => 'http://hl7.org/fhir/StructureDefinition/iso21090-EN-qualifier',
                                    'valueCode' => 'IN',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $transformer = new PatientNameTransformer();

        $result = $transformer->transformRowBeforeSave($model, $data);

        $expected = $data;

        $expected['grs_last_name'] = 'Jansen';
        $expected['grs_last_name_order'] = 'surname';
        $expected['grs_initials_name'] = 'J.';
        $expected['grs_surname_prefix'] = null;

        $this->assertEquals($expected, $result);
    }

    public function testGivenArrayWithInitialsAndBirthName()
    {
        $model = $this->getPatientModel();

        $data = [
            'name' => [
                [
                    'use' => 'official',
                    'family' => 'Jansen',
                    'given' => ['Janneke', 'J.'],
                    '_given' => [
                        [
                            'extension' => [
                                [
                                    'url' => 'http://hl7.org/fhir/StructureDefinition/iso21090-EN-qualifier',
                                    'valueCode' => 'BR',
                                ],
                            ],
                        ],
                        [
                            'extension' => [
                                [
                                    'url' => 'http://hl7.org/fhir/StructureDefinition/iso21090-EN-qualifier',
                                    'valueCode' => 'IN',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $transformer = new PatientNameTransformer();

        $result = $transformer->transformRowBeforeSave($model, $data);

        $expected = $data;

        $expected['grs_last_name'] = 'Jansen';
        $expected['grs_last_name_order'] = 'surname';
        $expected['grs_first_name'] = 'Janneke';
        $expected['grs_initials_name'] = 'J.';
        $expected['grs_surname_prefix'] = null;

        $this->assertEquals($expected, $result);
    }
}
