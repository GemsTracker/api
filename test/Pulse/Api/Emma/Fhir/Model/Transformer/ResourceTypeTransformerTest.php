<?php

namespace PulseTest\Rest\Api\Emma\Fhir\Model\Transformer;


use Gems\Rest\Exception\IncorrectDataException;
use Gems\Rest\Exception\MissingDataException;
use PHPUnit\Framework\TestCase;
use Pulse\Api\Emma\Fhir\Model\Transformer\ResourceTypeTransformer;
use PulseTest\Rest\Api\Emma\Fhir\Model\MockPatientModel;

class ResourceTypeTransformerTest extends TestCase
{
    use MockPatientModel;

    public function testResourceTypeMissing()
    {
        $model = $this->getPatientModel();

        $data = [];

        $transformer = new ResourceTypeTransformer('Patient');

        $this->expectException(MissingDataException::class);
        $transformer->transformRowBeforeSave($model, $data);
    }

    public function testResourceTypeNull()
    {
        $model = $this->getPatientModel();

        $data = [
            'resourceType' => null,
        ];

        $transformer = new ResourceTypeTransformer('Patient');

        $this->expectException(MissingDataException::class);
        $transformer->transformRowBeforeSave($model, $data);
    }

    public function testResourceTypeIncorrect()
    {
        $model = $this->getPatientModel();

        $data = [
            'resourceType' => 'SomethingElse',
        ];

        $transformer = new ResourceTypeTransformer('Patient');

        $this->expectException(IncorrectDataException::class);
        $transformer->transformRowBeforeSave($model, $data);
    }

    public function testResourceTypeExcactMatch()
    {
        $model = $this->getPatientModel();

        $data = [
            'resourceType' => 'Patient',
        ];

        $transformer = new ResourceTypeTransformer('Patient');

        $result = $transformer->transformRowBeforeSave($model, $data);

        $this->assertEquals($data, $result);
    }

    public function testResourceTypeNonCaseMatch()
    {
        $model = $this->getPatientModel();

        $data = [
            'resourceType' => 'patient',
        ];

        $transformer = new ResourceTypeTransformer('Patient');

        $result = $transformer->transformRowBeforeSave($model, $data);

        $this->assertEquals(['resourceType' => 'Patient'], $result);
    }
}
