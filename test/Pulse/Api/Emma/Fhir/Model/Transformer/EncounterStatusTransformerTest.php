<?php

declare(strict_types=1);


namespace PulseTest\Rest\Api\Emma\Fhir\Model\Transformer;


use PHPUnit\Framework\TestCase;
use Pulse\Api\Emma\Fhir\Model\Transformer\EncounterStatusTransformer;
use PulseTest\Rest\Api\Emma\Fhir\Model\MockEncounterModel;

class EncounterStatusTransformerTest extends TestCase
{
    use MockEncounterModel;

    public function testNoStatus()
    {
        $model = $this->getEncounterModel();
        $data = [];

        $transformer = new EncounterStatusTransformer();

        $result = $transformer->transformRowBeforeSave($model, $data);
        $this->assertEquals($data, $result);
    }

    public function testNonExistingStatus()
    {
        $model = $this->getEncounterModel();
        $data = ['status' => 'test123'];

        $transformer = new EncounterStatusTransformer();

        $result = $transformer->transformRowBeforeSave($model, $data);
        $this->assertEquals($data, $result);
    }

    public function testBookedStatus()
    {
        $model = $this->getEncounterModel();
        $data = ['status' => 'planned'];

        $transformer = new EncounterStatusTransformer();

        $result = $transformer->transformRowBeforeSave($model, $data);

        $expected = $data;
        $expected['gap_status'] = 'AC';
        $this->assertEquals($expected, $result);
    }

    public function testFinishedStatus()
    {
        $model = $this->getEncounterModel();
        $data = ['status' => 'finished'];

        $transformer = new EncounterStatusTransformer();

        $result = $transformer->transformRowBeforeSave($model, $data);

        $expected = $data;
        $expected['gap_status'] = 'CO';
        $this->assertEquals($expected, $result);
    }

    public function testCancelledStatus()
    {
        $model = $this->getEncounterModel();
        $data = ['status' => 'cancelled'];

        $transformer = new EncounterStatusTransformer();

        $result = $transformer->transformRowBeforeSave($model, $data);

        $expected = $data;
        $expected['gap_status'] = 'CA';

        $this->assertEquals($expected, $result);
    }


}
