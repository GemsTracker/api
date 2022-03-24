<?php

declare(strict_types=1);


namespace PulseTest\Rest\Api\Emma\Fhir\Model\Transformer;


use PHPUnit\Framework\TestCase;
use Pulse\Api\Emma\Fhir\Model\Transformer\AppointmentEscrowOrganizationTransformer;
use PulseTest\Rest\Api\Emma\Fhir\Model\MockAppointmentModel;

class AppointmentEscrowOrganizationTransformerTest extends TestCase
{
    use MockAppointmentModel;

    public function testKnownOrganization()
    {
        $model = $this->getAppointmentModel();

        $transformer = new AppointmentEscrowOrganizationTransformer();

        $data = [
            'gap_id_organization' => 1
        ];

        $result = $transformer->transformRowBeforeSave($model, $data);

        $this->assertEquals($data, $result);
    }

    public function testUnknownOrganization()
    {
        $model = $this->getAppointmentModel();

        $transformer = new AppointmentEscrowOrganizationTransformer();

        $data = [];

        $result = $transformer->transformRowBeforeSave($model, $data);

        $expected = [
            'gap_id_organization' => 81
        ];

        $this->assertEquals($expected, $result);
    }
}
