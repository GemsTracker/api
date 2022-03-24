<?php

declare(strict_types=1);


namespace PulseTest\Rest\Api\Emma\Fhir\Model\Transformer;


use Gems\Rest\Exception\MissingDataException;
use PHPUnit\Framework\TestCase;
use Pulse\Api\Emma\Fhir\Model\Transformer\AppointmentPatientTransformer;
use Pulse\Api\Emma\Fhir\Model\Transformer\EncounterPatientTransformer;
use Pulse\Api\Emma\Fhir\Repository\EpdRepository;
use Pulse\Api\Repository\RespondentRepository;
use PulseTest\Rest\Api\Emma\Fhir\Model\MockEncounterModel;

class EncounterPatientTransformerTest extends TestCase
{
    use MockEncounterModel;

    public function testNoParticipant()
    {
        $model = $this->getEncounterModel();
        $data = [];

        $transformer = $this->getTransformer();

        $this->expectException(MissingDataException::class);
        $result = $transformer->transformRowBeforeSave($model, $data);
    }

    public function testNoPatientAsParticipant()
    {
        $model = $this->getEncounterModel();
        $data = [
            'subject' => [
                'reference' =>  'Practitioner/sgfdfgdfghgfh',
                'display' => 'Jan Jansen',
            ],
        ];

        $transformer = $this->getTransformer();

        $this->expectException(MissingDataException::class);
        $result = $transformer->transformRowBeforeSave($model, $data);
    }

    public function testPatientNotFound()
    {
        $model = $this->getEncounterModel();
        $data = [
            'subject' => [
                'reference' =>  'Patient/999',
                'display' => 'Janneke Jansen',
            ],
        ];

        $transformer = $this->getTransformer();

        $result = $transformer->transformRowBeforeSave($model, $data);
        $expected = $data;

        $this->assertEquals($expected, $result);
    }

    public function testPatientFound()
    {
        $model = $this->getEncounterModel();
        $data = [
            'subject' => [
                'reference' =>  'Patient/123',
                'display' => 'Janneke Jansen',
            ],
        ];

        $transformer = $this->getTransformer();

        $result = $transformer->transformRowBeforeSave($model, $data);
        $expected = $data;
        $expected['gap_id_user'] = 1;

        $this->assertEquals($expected, $result);
    }

    protected function getTransformer()
    {
        $respondentRepository = $this->prophesize(RespondentRepository::class);
        $respondentRepository->getRespondentIdFromEpdId('999', 'emma')->willReturn(null);
        $respondentRepository->getRespondentIdFromEpdId('123', 'emma')->willReturn('1');

        $epdRepositoryProphecy = $this->prophesize(EpdRepository::class);
        $epdRepositoryProphecy->getEpdName()->willReturn('emma');

        return new EncounterPatientTransformer($respondentRepository->reveal(), $epdRepositoryProphecy->reveal());
    }


}
