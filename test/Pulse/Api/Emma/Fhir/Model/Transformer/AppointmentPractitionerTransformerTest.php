<?php

declare(strict_types=1);


namespace PulseTest\Rest\Api\Emma\Fhir\Model\Transformer;


use Gems\Rest\Exception\MissingDataException;
use PHPUnit\Framework\TestCase;
use Pulse\Api\Emma\Fhir\Model\Transformer\AppointmentPractitionerTransformer;
use Pulse\Api\Emma\Fhir\Repository\AgendaStaffRepository;
use Pulse\Api\Emma\Fhir\Repository\EpdRepository;
use PulseTest\Rest\Api\Emma\Fhir\Model\MockAppointmentModel;

class AppointmentPractitionerTransformerTest extends TestCase
{
    use MockAppointmentModel;

    public function testNoParticipants()
    {
        $model = $this->getAppointmentModel();
        $data = [];

        $transformer = $this->getTransformer();

        $result = $transformer->transformRowBeforeSave($model, $data);

        $this->assertEquals($data, $result);
    }

    public function testNoKnownOrganization()
    {
        $model = $this->getAppointmentModel();
        $data = [
            'participant' => [
                [
                    'actor' => [
                        'reference' => 'Practitioner/123',
                        'display' => 'Jan Jansen',
                    ],
                ],
            ],
        ];

        $transformer = $this->getTransformer();

        $result = $transformer->transformRowBeforeSave($model, $data);

        $this->assertEquals($data, $result);
    }

    public function testCorrectPractitioner()
    {
        $model = $this->getAppointmentModel();
        $data = [
            'participant' => [
                [
                    'actor' => [
                        'reference' => 'Practitioner/123',
                        'display' => 'Jan Jansen',
                    ],
                ],
            ],
            'gap_id_organization' => 1,
        ];

        $transformer = $this->getTransformer();

        $result = $transformer->transformRowBeforeSave($model, $data);

        $expected = $data;
        $expected['gap_id_attended_by'] = 12;

        $this->assertEquals($expected, $result);
    }

    public function testMultipleParticipants()
    {
        $model = $this->getAppointmentModel();
        $data = [
            'participant' => [
                [
                    'actor' => [
                        'reference' =>  'Patient/123',
                        'display' => 'Janneke Jansen',
                    ],
                ],
                [
                    'actor' => [
                        'reference' => 'Practitioner/123',
                        'display' => 'Jan Jansen',
                    ],
                ],
            ],
            'gap_id_organization' => 1,
        ];

        $transformer = $this->getTransformer();

        $result = $transformer->transformRowBeforeSave($model, $data);

        $expected = $data;
        $expected['gap_id_attended_by'] = 12;

        $this->assertEquals($expected, $result);
    }

    public function getTransformer()
    {
        $agendaStaffRepositoryProphecy = $this->prophesize(AgendaStaffRepository::class);

        $agendaStaffRepositoryProphecy->matchStaffByNameOrSourceId('Jan Jansen', 'testEpd', '123', 1)->willReturn(12);

        $epdRepositoryProphecy = $this->prophesize(EpdRepository::class);
        $epdRepositoryProphecy->getEpdName()->willReturn('testEpd');


        return new AppointmentPractitionerTransformer($agendaStaffRepositoryProphecy->reveal(), $epdRepositoryProphecy->reveal());
    }
}
