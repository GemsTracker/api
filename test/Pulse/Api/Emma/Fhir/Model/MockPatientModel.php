<?php

namespace PulseTest\Rest\Api\Emma\Fhir\Model;

use Pulse\Api\Emma\Fhir\Model\RespondentModel;

trait MockPatientModel
{
    public function getPatientModel()
    {
        $modelProphecy = $this->prophesize(RespondentModel::class);

        return $modelProphecy->reveal();
    }
}
