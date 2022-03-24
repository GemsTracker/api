<?php

declare(strict_types=1);


namespace PulseTest\Rest\Api\Emma\Fhir\Model\Transformer;


use Gems\Rest\Exception\MissingDataException;
use PHPUnit\Framework\TestCase;
use Pulse\Api\Emma\Fhir\Model\Transformer\EncounterOrganizationTransformer;
use Pulse\Api\Emma\Fhir\Repository\OrganizationRepository;
use PulseTest\Rest\Api\Emma\Fhir\Model\MockEncounterModel;

class EncounterOrganizationTransformerTest extends TestCase
{
    use MockEncounterModel;

    public function testNoServiceProvider()
    {
        $model = $this->getEncounterModel();

        $transformer = $this->getTransformer();

        $data = [];

        $this->expectException(MissingDataException::class);
        $transformer->transformRowBeforeSave($model, $data);
    }

    public function testEmptyServiceProvider()
    {
        $model = $this->getEncounterModel();

        $transformer = $this->getTransformer();

        $data = [
            'serviceProvider' => null,
        ];

        $this->expectException(MissingDataException::class);
        $transformer->transformRowBeforeSave($model, $data);
    }

    public function testNoReferenceInServiceProvider()
    {
        $model = $this->getEncounterModel();

        $transformer = $this->getTransformer();

        $data = [
            'serviceProvider' => [
                'display' => 'Test organization',
            ],
        ];

        $this->expectException(MissingDataException::class);
        $transformer->transformRowBeforeSave($model, $data);
    }

    public function testNoDisplayInServiceProvider()
    {
        $model = $this->getEncounterModel();

        $transformer = $this->getTransformer();

        $data = [
            'serviceProvider' => [
                'reference' => 'Organization/123',
            ],
        ];

        $this->expectException(MissingDataException::class);
        $transformer->transformRowBeforeSave($model, $data);
    }

    public function testIncorrectReferenceInServiceProvider()
    {
        $model = $this->getEncounterModel();

        $transformer = $this->getTransformer();

        $data = [
            'serviceProvider' => [
                'reference' => 'SomethingElse/123',
                'display' => 'Test organization',
            ],
        ];

        $this->expectException(MissingDataException::class);
        $transformer->transformRowBeforeSave($model, $data);
    }

    public function testCorrectServiceProvider()
    {
        $model = $this->getEncounterModel();

        $transformer = $this->getTransformer();

        $data = [
            'serviceProvider' => [
                'reference' => 'Organization/123',
                'display' => 'Test organization Amsterdam',
            ],
        ];

        $result = $transformer->transformRowBeforeSave($model, $data);

        $expected = $data;
        $expected['gap_id_organization'] = 1;
        $expected['gap_id_location'] = 2001;

        $this->assertEquals($expected, $result);
    }

    protected function getTransformer()
    {
        $organizationRepositoryProphecy = $this->prophesize(OrganizationRepository::class);
        $organizationRepositoryProphecy->getOrganizationId('Test organization Amsterdam')->willReturn(1);
        $organizationRepositoryProphecy->getLocationFromOrganizationName('Test organization Amsterdam')->willReturn('Amsterdam');
        $organizationRepositoryProphecy->matchLocation('Amsterdam', 1, true)->willReturn(2001);

        return new EncounterOrganizationTransformer($organizationRepositoryProphecy->reveal());
    }
}
