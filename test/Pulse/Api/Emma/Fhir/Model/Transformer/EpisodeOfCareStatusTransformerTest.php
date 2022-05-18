<?php

declare(strict_types=1);


namespace PulseTest\Rest\Api\Emma\Fhir\Model\Transformer;


use PHPUnit\Framework\TestCase;
use Pulse\Api\Emma\Fhir\Model\Transformer\EpisodeOfCareStatusTransformer;
use PulseTest\Rest\Api\Emma\Fhir\Model\MockEpisodeOfCareModel;

class EpisodeOfCareStatusTransformerTest extends TestCase
{
    use MockEpisodeOfCareModel;

    public function testNoStatus()
    {
        $model = $this->getEpisodeOfCareModel();
        $data = [];

        $transformer = new EpisodeOfCareStatusTransformer();

        $result = $transformer->transformRowBeforeSave($model, $data);
        $this->assertEquals($data, $result);
    }

    public function testNonExistingStatus()
    {
        $model = $this->getEpisodeOfCareModel();
        $data = ['status' => 'test123'];

        $transformer = new EpisodeOfCareStatusTransformer();

        $result = $transformer->transformRowBeforeSave($model, $data);
        $this->assertEquals($data, $result);
    }

    public function testAllStatus()
    {
        $model = $this->getEpisodeOfCareModel();

        $testStatus = [
            'active' => 'A',
            'cancelled' => 'C',
            'entered-in-error' => 'E',
            'finished' => 'F',
            'onhold' => 'O',
            'planned' => 'P',
            'waitlist' => 'W',
        ];

        $transformer = new EpisodeOfCareStatusTransformer();

        foreach($testStatus as $inputStatus => $outputStatus) {
            $data = ['status' => $inputStatus];

            $result = $transformer->transformRowBeforeSave($model, $data);

            $expected = $data;
            $expected['gec_status'] = $outputStatus;
            $this->assertEquals($expected, $result);
        }
    }
}
