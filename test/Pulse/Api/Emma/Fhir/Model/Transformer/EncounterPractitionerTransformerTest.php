<?php

declare(strict_types=1);


namespace PulseTest\Rest\Api\Emma\Fhir\Model\Transformer;

use PHPUnit\Framework\TestCase;
use Pulse\Api\Emma\Fhir\Model\Transformer\AppointmentPractitionerTransformer;
use Pulse\Api\Emma\Fhir\Model\Transformer\EncounterPractitionerTransformer;
use Pulse\Api\Emma\Fhir\Repository\AgendaStaffRepository;
use PulseTest\Rest\Api\Emma\Fhir\Model\MockEncounterModel;

class EncounterPractitionerTransformerTest extends TestCase
{
    use MockEncounterModel;

    public function testNoParticipants()
    {
        $model = $this->getEncounterModel();
        $data = [];

        $transformer = $this->getTransformer();

        $result = $transformer->transformRowBeforeSave($model, $data);

        $this->assertEquals($data, $result);
    }

    public function testNoKnownOrganization()
    {
        $model = $this->getEncounterModel();
        $data = [
            'participant' => [
                [
                    'individual' => [
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
        $model = $this->getEncounterModel();
        $data = [
            'participant' => [
                [
                    'individual' => [
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

        $agendaStaffRepositoryProphecy->matchStaff('Jan Jansen', 1)->willReturn(12);


        return new EncounterPractitionerTransformer($agendaStaffRepositoryProphecy->reveal());
    }
}
