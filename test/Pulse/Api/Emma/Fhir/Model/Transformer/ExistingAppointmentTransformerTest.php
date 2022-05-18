<?php

declare(strict_types=1);


namespace PulseTest\Rest\Api\Emma\Fhir\Model\Transformer;


use Gems\Rest\Exception\MissingDataException;
use PHPUnit\Framework\TestCase;
use Pulse\Api\Emma\Fhir\Model\Transformer\ExistingAppointmentTransformer;
use Pulse\Api\Emma\Fhir\Repository\AppointmentRepository;
use Pulse\Api\Emma\Fhir\Repository\EpdRepository;
use PulseTest\Rest\Api\Emma\Fhir\Model\MockAppointmentModel;

class ExistingAppointmentTransformerTest extends TestCase
{
    use MockAppointmentModel;

    public function testNoId()
    {
        $model = $this->getAppointmentModel();

        $data = [];

        $transformer = $this->getTransformer();

        $this->expectException(MissingDataException::class);
        $transformer->transformRowBeforeSave($model, $data);
    }

    public function testUnknownId()
    {
        $model = $this->getAppointmentModel();

        $data = [
            'id' => 'z9876',
        ];

        $transformer = $this->getTransformer();
        $result = $transformer->transformRowBeforeSave($model, $data);
        $expected = $data;

        $this->assertEquals($expected, $result);
    }

    public function testKnownId()
    {
        $model = $this->getAppointmentModel();

        $data = [
            'id' => 'a1234',
        ];

        $transformer = $this->getTransformer();
        $result = $transformer->transformRowBeforeSave($model, $data);
        $expected = $data;
        $expected['gap_id_appointment'] = 1;
        $expected['gap_id_organization'] = 1;
        $expected['exists'] = true;

        $this->assertEquals($expected, $result);
    }

    protected function getTransformer()
    {
        $appointmentRepository = $this->prophesize(AppointmentRepository::class);
        $appointmentRepository->getAppointmentFromSourceId('z9876', 'emma')->willReturn(null);
        $appointmentRepository->getAppointmentFromSourceId('a1234', 'emma')->willReturn([
            'gap_id_appointment' => 1,
            'gap_id_organization' => 1,
        ]);

        $epdRepositoryProphecy = $this->prophesize(EpdRepository::class);
        $epdRepositoryProphecy->getEpdName()->willReturn('emma');

        return new ExistingAppointmentTransformer($appointmentRepository->reveal(), $epdRepositoryProphecy->reveal());
    }
}
