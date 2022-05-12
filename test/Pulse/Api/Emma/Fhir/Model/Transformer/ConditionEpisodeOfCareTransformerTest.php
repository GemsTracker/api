<?php

declare(strict_types=1);


namespace PulseTest\Rest\Api\Emma\Fhir\Model\Transformer;


use PHPUnit\Framework\TestCase;
use Pulse\Api\Emma\Fhir\Model\Transformer\ConditionEpisodeOfCareTransformer;
use Pulse\Api\Emma\Fhir\Repository\EpisodeOfCareRepository;
use Pulse\Api\Emma\Fhir\Repository\ImportEscrowLinkRepository;
use PulseTest\Rest\Api\Emma\Fhir\Model\MockConditionModel;

class ConditionEpisodeOfCareTransformerTest extends TestCase
{
    use MockConditionModel;

    public function testNoEpisode()
    {
        $model = $this->getConditionModel();

        $transformer = $this->getTransformer();
        $data = [];
        $result = $transformer->transformRowBeforeSave($model, $data);

        $this->assertEquals($data, $result);
    }

    public function testEmptyEpisode()
    {
        $model = $this->getConditionModel();

        $transformer = $this->getTransformer();
        $data = [
            'context' => null,
        ];
        $result = $transformer->transformRowBeforeSave($model, $data);

        $this->assertEquals($data, $result);
    }

    public function testNoReferenceEpisode()
    {
        $model = $this->getConditionModel();

        $transformer = $this->getTransformer();
        $data = [
            'context' => [
                'display' => 'Only display!',
            ],
        ];
        $result = $transformer->transformRowBeforeSave($model, $data);

        $this->assertEquals($data, $result);
    }

    public function testIncorrectReferenceEpisode()
    {
        $model = $this->getConditionModel();

        $transformer = $this->getTransformer();
        $data = [
            'id' => 3001,
            'context' => [
                'reference' => 'SomeOtherReference/123',
            ],
        ];
        $result = $transformer->transformRowBeforeSave($model, $data);

        $this->assertEquals($data, $result);
    }

    public function testUnknownEpisode()
    {
        $model = $this->getConditionModel();

        $transformer = $this->getTransformer();
        $data = [
            'id' => 3001,
            'context' => [
                'reference' => 'EpisodeOfCare/987',
            ],
        ];
        $result = $transformer->transformRowBeforeSave($model, $data);

        $expected = $data;
        $expected['gmco_id_episode_of_care'] = null;
        $expected['episodeOfCareSourceId'] = 987;

        $this->assertEquals($expected, $result);
    }

    public function testKnownEpisode()
    {
        $model = $this->getConditionModel();

        $transformer = $this->getTransformer();
        $data = [
            'context' => [
                'reference' => 'EpisodeOfCare/123',
            ],
        ];
        $result = $transformer->transformRowBeforeSave($model, $data);

        $expected = $data;
        $expected['gmco_id_episode_of_care'] = 3001;
        $expected['episodeOfCareSourceId'] = 123;

        $this->assertEquals($expected, $result);
    }

    protected function getTransformer()
    {
        $episodeOfCareRepositoryProphecy = $this->prophesize(EpisodeOfCareRepository::class);
        $episodeOfCareRepositoryProphecy->getEpisodeOfCareBySourceId('987', 'emma')->willReturn(null);
        $episodeOfCareRepositoryProphecy->getEpisodeOfCareBySourceId('123', 'emma')->willReturn(3001);

        $importEscrowLinkRepositoryProphecy = $this->prophesize(ImportEscrowLinkRepository::class);

        return new ConditionEpisodeOfCareTransformer($episodeOfCareRepositoryProphecy->reveal(), $importEscrowLinkRepositoryProphecy->reveal());
    }
}
