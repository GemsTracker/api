<?php

declare(strict_types=1);


namespace PulseTest\Rest\Api\Emma\Fhir\Model\Transformer;


use PHPUnit\Framework\TestCase;
use Pulse\Api\Emma\Fhir\Model\Transformer\AppointmentActivityTransformer;
use Pulse\Api\Emma\Fhir\Repository\AgendaActivityRepository;
use PulseTest\Rest\Api\Emma\Fhir\Model\MockAppointmentModel;

class AppointmentActivityTransformerTest extends TestCase
{
    use MockAppointmentModel;

    public function testNoAppointmentDescription()
    {
        $model = $this->getAppointmentModel();

        $transformer = $this->getTransformer();
        $data = [];

        $result = $transformer->transformRowBeforeSave($model, $data);
        $this->assertEquals($data, $result);
    }

    public function testCorrectAppointmentDescriptionWithoutOrganizationId()
    {
        $model = $this->getAppointmentModel();

        $transformer = $this->getTransformer();
        $data = [
            'description' => 'consult',
        ];

        $result = $transformer->transformRowBeforeSave($model, $data);

        $expected = $data;
        $expected['gap_id_activity'] = 24;
        $this->assertEquals($expected, $result);
    }

    public function testCorrectAppointmentDescriptionWithOrganizationId()
    {
        $model = $this->getAppointmentModel();

        $transformer = $this->getTransformer();
        $data = [
            'description' => 'consult',
            'gap_id_organization' => 1,
        ];

        $result = $transformer->transformRowBeforeSave($model, $data);

        $expected = $data;
        $expected['gap_id_activity'] = 32;
        $this->assertEquals($expected, $result);
    }

    protected function getTransformer()
    {
        $agendaActivityRepositoryProphecy = $this->prophesize(AgendaActivityRepository::class);
        $agendaActivityRepositoryProphecy->matchActivity('consult', null)->willReturn(24);
        $agendaActivityRepositoryProphecy->matchActivity('consult', 1)->willReturn(32);

        return new AppointmentActivityTransformer($agendaActivityRepositoryProphecy->reveal());
    }
}
