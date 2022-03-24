<?php

declare(strict_types=1);


namespace PulseTest\Rest\Api\Emma\Fhir\Model\Transformer;


use Gems\Rest\Exception\MissingDataException;
use PHPUnit\Framework\TestCase;
use Pulse\Api\Emma\Fhir\Model\Transformer\EpisodeOfCarePeriodTransformer;
use PulseTest\Rest\Api\Emma\Fhir\Model\MockEpisodeOfCareModel;

class EpisodeOfCarePeriodTransformerTest extends TestCase
{
    use MockEpisodeOfCareModel;

    public function testNoPeriod()
    {
        $model = $this->getEpisodeOfCareModel();

        $transformer = new EpisodeOfCarePeriodTransformer();

        $data = [];

        $this->expectException(MissingDataException::class);
        $transformer->transformRowBeforeSave($model, $data);
    }

    public function testNullPeriod()
    {
        $model = $this->getEpisodeOfCareModel();

        $transformer = new EpisodeOfCarePeriodTransformer();

        $data = [
            'period' => null,
        ];

        $this->expectException(MissingDataException::class);
        $transformer->transformRowBeforeSave($model, $data);
    }

    public function testNoStartPeriod()
    {
        $model = $this->getEpisodeOfCareModel();

        $transformer = new EpisodeOfCarePeriodTransformer();

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
        $model = $this->getEpisodeOfCareModel();

        $transformer = new EpisodeOfCarePeriodTransformer();

        $data = [
            'period' => [
                'start' => '2022-02-22T08:00:00+02:00',
            ],
        ];

        $result = $transformer->transformRowBeforeSave($model, $data);

        $expected = $data;
        $expected['gec_startdate'] = '2022-02-22T08:00:00+02:00';

        $this->assertEquals($expected, $result);
    }

    public function testEndPeriod()
    {
        $model = $this->getEpisodeOfCareModel();

        $transformer = new EpisodeOfCarePeriodTransformer();

        $data = [
            'period' => [
                'start' => '2022-02-22T08:00:00+02:00',
                'end' => '2022-02-22T09:00:00+02:00',
            ],
        ];

        $result = $transformer->transformRowBeforeSave($model, $data);

        $expected = $data;
        $expected['gec_startdate'] = '2022-02-22T08:00:00+02:00';
        $expected['gec_enddate'] = '2022-02-22T09:00:00+02:00';

        $this->assertEquals($expected, $result);
    }
}
