<?php

namespace PulseTest\Rest\Api\Emma\Fhir\Model;

use Pulse\Api\Emma\Fhir\Model\EncounterModel;

trait MockEncounterModel
{
    public function getEncounterModel()
    {
        $modelProphecy = $this->prophesize(EncounterModel::class);

        return $modelProphecy->reveal();
    }
}
