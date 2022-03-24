<?php

namespace PulseTest\Rest\Api\Emma\Fhir\Model\Transformer;


use Gems\Rest\Exception\MissingDataException;
use PHPUnit\Framework\TestCase;
use Pulse\Api\Emma\Fhir\Model\Transformer\PatientOtherFieldsTransformer;
use PulseTest\Rest\Api\Emma\Fhir\Model\MockPatientModel;

class PatientOtherFieldsTransformerTest extends TestCase
{
    use MockPatientModel;

    public function testGenderNull()
    {
        $model = $this->getPatientModel();

        $data = [
            'gender' => null,
        ];

        $transformer = new PatientOtherFieldsTransformer();

        $result = $transformer->transformRowBeforeSave($model, $data);

        $expected = $data;
        $expected['grs_gender'] = 'U';

        $this->assertEquals($expected, $result);
    }

    public function testGenderMale()
    {
        $model = $this->getPatientModel();

        $data = [
            'gender' => 'male',
        ];

        $transformer = new PatientOtherFieldsTransformer();

        $result = $transformer->transformRowBeforeSave($model, $data);

        $expected = $data;
        $expected['grs_gender'] = 'M';

        $this->assertEquals($expected, $result);
    }

    public function testGenderFemale()
    {
        $model = $this->getPatientModel();

        $data = [
            'gender' => 'female',
        ];

        $transformer = new PatientOtherFieldsTransformer();

        $result = $transformer->transformRowBeforeSave($model, $data);

        $expected = $data;
        $expected['grs_gender'] = 'F';

        $this->assertEquals($expected, $result);
    }

    public function testGenderUnknown()
    {
        $model = $this->getPatientModel();

        $data = [
            'gender' => 'unknown',
        ];

        $transformer = new PatientOtherFieldsTransformer();

        $result = $transformer->transformRowBeforeSave($model, $data);

        $expected = $data;
        $expected['grs_gender'] = 'U';

        $this->assertEquals($expected, $result);
    }

    public function testGenderOther()
    {
        $model = $this->getPatientModel();

        $data = [
            'gender' => 'other',
        ];

        $transformer = new PatientOtherFieldsTransformer();

        $result = $transformer->transformRowBeforeSave($model, $data);

        $expected = $data;
        $expected['grs_gender'] = 'U';

        $this->assertEquals($expected, $result);
    }

    public function testBirthdateNull()
    {
        $model = $this->getPatientModel();

        $data = [
            'birthDate' => null,
        ];

        $transformer = new PatientOtherFieldsTransformer();

        $result = $transformer->transformRowBeforeSave($model, $data);

        $expected = $data;
        $expected['grs_gender'] = 'U';

        $this->assertEquals($expected, $result);
    }

    public function testBirthdateInvalid()
    {
        $model = $this->getPatientModel();

        $data = [
            'birthDate' => '1425',
        ];

        $transformer = new PatientOtherFieldsTransformer();

        $result = $transformer->transformRowBeforeSave($model, $data);

        $expected = $data;
        $expected['grs_gender'] = 'U';

        $this->assertEquals($expected, $result);
    }

    public function testBirthdateValid()
    {
        $model = $this->getPatientModel();

        $data = [
            'birthDate' => '2002-08-25',
        ];

        $transformer = new PatientOtherFieldsTransformer();

        $result = $transformer->transformRowBeforeSave($model, $data);

        $this->assertArrayHasKey('grs_birthday', $result);
        $this->assertInstanceOf(\MUtil_Date::class, $result['grs_birthday']);
        $this->assertEquals('2002-08-25', $result['grs_birthday']->toString('yyyy-MM-dd'));
    }

}
