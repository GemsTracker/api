<?php

namespace PulseTest\Rest\Api\Emma\Fhir\Model;

use Pulse\Api\Emma\Fhir\Model\AppointmentModel;

trait MockAppointmentModel
{
    public function getAppointmentModel()
    {
        $modelProphecy = $this->prophesize(AppointmentModel::class);

        return $modelProphecy->reveal();
    }
}
