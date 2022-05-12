<?php

namespace PulseTest\Rest\Api\Emma\Fhir\Model;

use Pulse\Api\Emma\Fhir\Model\ConditionModel;

trait MockConditionModel
{
    public function getConditionModel()
    {
        $modelProphecy = $this->prophesize(ConditionModel::class);

        return $modelProphecy->reveal();
    }
}
