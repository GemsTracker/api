<?php

declare(strict_types=1);


namespace PulseTest\Rest\Api\Emma\Fhir\Model\Transformer;


use Gems\Rest\Exception\MissingDataException;
use PHPUnit\Framework\TestCase;
use Pulse\Api\Emma\Fhir\Model\Transformer\EncounterPeriodTransformer;
use PulseTest\Rest\Api\Emma\Fhir\Model\MockEncounterModel;

class EncounterPeriodTransformerTest extends TestCase
{
    use MockEncounterModel;

    public function testNoPeriod()
    {
        $model = $this->getEncounterModel();

        $transformer = new EncounterPeriodTransformer();

        $data = [];

        $this->expectException(MissingDataException::class);
        $transformer->transformRowBeforeSave($model, $data);
    }

    public function testNullPeriod()
    {
        $model = $this->getEncounterModel();

        $transformer = new EncounterPeriodTransformer();

        $data = [
            'period' => null,
        ];

        $this->expectException(MissingDataException::class);
        $transformer->transformRowBeforeSave($model, $data);
    }

    public function testNoStartPeriod()
    {
        $model = $this->getEncounterModel();

        $transformer = new EncounterPeriodTransformer();

        $data = [
            'period' => [
                'end' => '2022-02-22T08:00:00+02:00',
            ],
        ];

        $this->expectException(MissingDataException::class);
        $transformer->transformRowBeforeSave($model, $data);
    }

    public function testStartPeriod()
    {
        $model = $this->getEncounterModel();

        $transformer = new EncounterPeriodTransformer();

        $data = [
            'period' => [
                'start' => '2022-02-22T08:00:00+02:00',
            ],
        ];

        $result = $transformer->transformRowBeforeSave($model, $data);

        $expected = $data;
        $expected['gap_admission_time'] = '2022-02-22T08:00:00+02:00';
        $expected['gap_discharge_time'] = null;

        $this->assertEquals($expected, $result);
    }

    public function testEndPeriod()
    {
        $model = $this->getEncounterModel();

        $transformer = new EncounterPeriodTransformer();

        $data = [
            'period' => [
                'start' => '2022-02-22T08:00:00+02:00',
                'end' => '2022-02-22T09:00:00+02:00',
            ],
        ];

        $result = $transformer->transformRowBeforeSave($model, $data);

        $expected = $data;
        $expected['gap_admission_time'] = '2022-02-22T08:00:00+02:00';
        $expected['gap_discharge_time'] = '2022-02-22T09:00:00+02:00';

        $this->assertEquals($expected, $result);
    }
}
