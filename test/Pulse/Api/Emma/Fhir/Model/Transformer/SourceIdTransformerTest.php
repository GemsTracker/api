<?php

namespace PulseTest\Rest\Api\Emma\Fhir\Model\Transformer;


use Gems\Rest\Exception\IncorrectDataException;
use Gems\Rest\Exception\MissingDataException;
use PHPUnit\Framework\TestCase;
use Pulse\Api\Emma\Fhir\Model\Transformer\ResourceTypeTransformer;
use Pulse\Api\Emma\Fhir\Model\Transformer\SourceIdTransformer;
use PulseTest\Rest\Api\Emma\Fhir\Model\MockPatientModel;

class SourceIdTransformerTest extends TestCase
{
    use MockPatientModel;

    public function testIdMissing()
    {
        $model = $this->getPatientModel();

        $data = [];

        $transformer = new SourceIdTransformer('gr2o_epd_id');

        $this->expectException(MissingDataException::class);
        $transformer->transformRowBeforeSave($model, $data);
    }

    public function testIdNull()
    {
        $model = $this->getPatientModel();

        $data = [
            'id' => null,
        ];

        $transformer = new SourceIdTransformer('gr2o_epd_id');

        $this->expectException(MissingDataException::class);
        $transformer->transformRowBeforeSave($model, $data);
    }

    public function testIdCorrect()
    {
        $model = $this->getPatientModel();

        $data = [
            'id' => '123abcd',
        ];

        $transformer = new SourceIdTransformer('gr2o_epd_id');

        $result = $transformer->transformRowBeforeSave($model, $data);

        $expected = [
            'id' => '123abcd',
            'gr2o_epd_id' => '123abcd',
        ];

        $this->assertEquals($expected, $result);
    }
}
