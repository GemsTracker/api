<?php

declare(strict_types=1);


namespace PulseTest\Rest\Api\Emma\Fhir\Model\Transformer;


use Gems\Rest\Exception\MissingDataException;
use PHPUnit\Framework\TestCase;
use Pulse\Api\Emma\Fhir\Model\Transformer\EpisodeOfCareOrganizationTransformer;
use Pulse\Api\Emma\Fhir\Repository\OrganizationRepository;
use PulseTest\Rest\Api\Emma\Fhir\Model\MockEpisodeOfCareModel;

class EpisodeOfCareOrganizationTransformerTest extends TestCase
{
    use MockEpisodeOfCareModel;

    public function testNoManagingOrganization()
    {
        $model = $this->getEpisodeOfCareModel();

        $transformer = $this->getTransformer();

        $data = [];

        $this->expectException(MissingDataException::class);
        $transformer->transformRowBeforeSave($model, $data);
    }

    public function testEmptyManagingOrganization()
    {
        $model = $this->getEpisodeOfCareModel();

        $transformer = $this->getTransformer();

        $data = [
            'managingOrganization' => null,
        ];

        $this->expectException(MissingDataException::class);
        $transformer->transformRowBeforeSave($model, $data);
    }

    public function testNoReferenceInManagingOrganization()
    {
        $model = $this->getEpisodeOfCareModel();

        $transformer = $this->getTransformer();

        $data = [
            'managingOrganization' => [
                'display' => 'Test organization',
            ],
        ];

        $this->expectException(MissingDataException::class);
        $transformer->transformRowBeforeSave($model, $data);
    }

    public function testNoDisplayInManagingOrganization()
    {
        $model = $this->getEpisodeOfCareModel();

        $transformer = $this->getTransformer();

        $data = [
            'managingOrganization' => [
                'reference' => 'Organization/123',
            ],
        ];

        $this->expectException(MissingDataException::class);
        $transformer->transformRowBeforeSave($model, $data);
    }

    public function testIncorrectReferenceInManagingOrganization()
    {
        $model = $this->getEpisodeOfCareModel();

        $transformer = $this->getTransformer();

        $data = [
            'managingOrganization' => [
                'reference' => 'SomethingElse/123',
                'display' => 'Test organization',
            ],
        ];

        $this->expectException(MissingDataException::class);
        $transformer->transformRowBeforeSave($model, $data);
    }

    public function testCorrectManagingOrganization()
    {
        $model = $this->getEpisodeOfCareModel();

        $transformer = $this->getTransformer();

        $data = [
            'managingOrganization' => [
                'reference' => 'Organization/123',
                'display' => 'Test organization Amsterdam',
            ],
        ];

        $result = $transformer->transformRowBeforeSave($model, $data);

        $expected = $data;
        $expected['gec_id_organization'] = 1;
        $expected['locationId'] = 201;

        $this->assertEquals($expected, $result);
    }

    protected function getTransformer()
    {
        $organizationRepositoryProphecy = $this->prophesize(OrganizationRepository::class);
        $organizationRepositoryProphecy->getOrganizationId('Test organization Amsterdam')->willReturn(1);
        $organizationRepositoryProphecy->getLocationFromOrganizationName('Test organization Amsterdam')->willReturn('Amsterdam');
        $organizationRepositoryProphecy->matchLocation('Amsterdam', 1, true)->willReturn(201);

        return new EpisodeOfCareOrganizationTransformer($organizationRepositoryProphecy->reveal());
    }
}
