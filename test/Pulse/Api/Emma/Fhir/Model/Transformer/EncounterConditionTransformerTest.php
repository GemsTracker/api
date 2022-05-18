<?php

declare(strict_types=1);


namespace PulseTest\Rest\Api\Emma\Fhir\Model\Transformer;


use PHPUnit\Framework\TestCase;
use Pulse\Api\Emma\Fhir\Model\Transformer\AppointmentConditionTransformer;
use Pulse\Api\Emma\Fhir\Model\Transformer\EncounterConditionTransformer;
use Pulse\Api\Emma\Fhir\Repository\ConditionRepository;
use Pulse\Api\Emma\Fhir\Repository\EpdRepository;
use Pulse\Api\Emma\Fhir\Repository\ImportEscrowLinkRepository;
use PulseTest\Rest\Api\Emma\Fhir\Model\MockEncounterModel;

class EncounterConditionTransformerTest extends TestCase
{
    use MockEncounterModel;

    public function testNoCondition()
    {
        $model = $this->getEncounterModel();

        $data = [];

        $transformer = $this->getTransformer();

        $result = $transformer->transformRowBeforeSave($model, $data);
        $this->assertEquals($data, $result);
    }

    public function testNullCondition()
    {
        $model = $this->getEncounterModel();

        $data = [
            'diagnosis' => null,
        ];

        $transformer = $this->getTransformer();

        $result = $transformer->transformRowBeforeSave($model, $data);
        $this->assertEquals($data, $result);
    }

    public function testUnknownCondition()
    {
        $model = $this->getEncounterModel();

        $data = [
            'id' => 4001,
            'diagnosis' => [
                [
                    'condition' => [
                        'reference' => 'Condition/987',
                        'display' => 'some condition',
                    ],
                ],
            ],
        ];

        $transformer = $this->getTransformer();

        $result = $transformer->transformRowBeforeSave($model, $data);

        $expected = $data;
        $expected['gap_id_episode'] = null;

        $this->assertEquals($expected, $result);
    }

    public function testKnownCondition()
    {
        $model = $this->getEncounterModel();

        $data = [
            'diagnosis' => [
                [
                    'condition' => [
                        'reference' => 'Condition/123',
                        'display' => 'some condition',
                    ],
                ],
            ],
        ];

        $transformer = $this->getTransformer();

        $result = $transformer->transformRowBeforeSave($model, $data);

        $expected = $data;
        $expected['gap_id_episode'] = 8;

        $this->assertEquals($expected, $result);
    }

    protected function getTransformer()
    {
        $conditionRepositoryProphecy = $this->prophesize(ConditionRepository::class);
        $conditionRepositoryProphecy->getEpisodeOfCareIdFromConditionBySourceId('987', 'testEpd')->willReturn(null);
        $conditionRepositoryProphecy->getEpisodeOfCareIdFromConditionBySourceId('123', 'testEpd')->willReturn(8);

        $epdRepositoryProphecy = $this->prophesize(EpdRepository::class);
        $epdRepositoryProphecy->getEpdName()->willReturn('testEpd');

        $importEscrowLinkRepositoryProphecy = $this->prophesize(ImportEscrowLinkRepository::class);

        return new EncounterConditionTransformer($conditionRepositoryProphecy->reveal(), $epdRepositoryProphecy->reveal(), $importEscrowLinkRepositoryProphecy->reveal());
    }
}
