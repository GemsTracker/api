<?php

declare(strict_types=1);


namespace PulseTest\Rest\Api\Emma\Fhir\Model\Transformer;


use PHPUnit\Framework\TestCase;
use Pulse\Api\Emma\Fhir\Model\Transformer\AppointmentStatusTransformer;
use PulseTest\Rest\Api\Emma\Fhir\Model\MockAppointmentModel;

class AppointmentStatusTransformerTest extends TestCase
{
    use MockAppointmentModel;

    public function testNoStatus()
    {
        $model = $this->getAppointmentModel();
        $data = [];

        $transformer = new AppointmentStatusTransformer();

        $result = $transformer->transformRowBeforeSave($model, $data);
        $this->assertEquals($data, $result);
    }

    public function testNonExistingStatus()
    {
        $model = $this->getAppointmentModel();
        $data = ['status' => 'test123'];

        $transformer = new AppointmentStatusTransformer();

        $result = $transformer->transformRowBeforeSave($model, $data);
        $this->assertEquals($data, $result);
    }

    public function testBookedStatus()
    {
        $model = $this->getAppointmentModel();
        $data = ['status' => 'booked'];

        $transformer = new AppointmentStatusTransformer();

        $result = $transformer->transformRowBeforeSave($model, $data);

        $expected = $data;
        $expected['gap_status'] = 'AC';
        $this->assertEquals($expected, $result);
    }

    public function testCancelledStatus()
    {
        $model = $this->getAppointmentModel();
        $data = ['status' => 'cancelled'];

        $transformer = new AppointmentStatusTransformer();

        $result = $transformer->transformRowBeforeSave($model, $data);

        $expected = $data;
        $expected['gap_status'] = 'CA';

        $this->assertEquals($expected, $result);
    }


}
