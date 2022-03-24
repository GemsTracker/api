<?php

declare(strict_types=1);


namespace PulseTest\Rest\Api\Emma\Fhir\Model\Transformer;


use Gems\Rest\Exception\MissingDataException;
use PHPUnit\Framework\TestCase;
use Pulse\Api\Emma\Fhir\Model\Transformer\AppointmentPatientTransformer;
use Pulse\Api\Emma\Fhir\Model\Transformer\EncounterPatientTransformer;
use Pulse\Api\Emma\Fhir\Model\Transformer\PatientReferenceTransformer;
use Pulse\Api\Emma\Fhir\Repository\EpdRepository;
use Pulse\Api\Repository\RespondentRepository;
use PulseTest\Rest\Api\Emma\Fhir\Model\MockEncounterModel;

class PatientReferenceTransformerTest extends TestCase
{
    protected $externalField = 'patient';

    protected $internalField = 'gr2o_id_user';

    protected function getModel()
    {
        $model = $this->prophesize(\MUtil_Model_DatabaseModelAbstract::class);
        return $model->reveal();
    }

    public function testNoParticipant()
    {
        $model = $this->getModel();
        $data = [];

        $transformer = $this->getTransformer();

        $this->expectException(MissingDataException::class);
        $result = $transformer->transformRowBeforeSave($model, $data);
    }

    public function testNoPatientAsParticipant()
    {
        $model = $this->getModel();
        $data = [
            $this->externalField => [
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
        $model = $this->getModel();
        $data = [
            $this->externalField => [
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
        $model = $this->getModel();
        $data = [
            $this->externalField => [
                'reference' =>  'Patient/123',
                'display' => 'Janneke Jansen',
            ],
        ];

        $transformer = $this->getTransformer();

        $result = $transformer->transformRowBeforeSave($model, $data);
        $expected = $data;
        $expected[$this->internalField] = 1;

        $this->assertEquals($expected, $result);
    }

    protected function getTransformer()
    {
        $respondentRepository = $this->prophesize(RespondentRepository::class);
        $respondentRepository->getRespondentIdFromEpdId('999', 'emma')->willReturn(null);
        $respondentRepository->getRespondentIdFromEpdId('123', 'emma')->willReturn('1');

        $epdRepositoryProphecy = $this->prophesize(EpdRepository::class);
        $epdRepositoryProphecy->getEpdName()->willReturn('emma');

        return new PatientReferenceTransformer($respondentRepository->reveal(), $epdRepositoryProphecy->reveal(), $this->externalField, $this->internalField);
    }


}
