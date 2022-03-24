<?php

namespace PulseTest\Rest\Api\Emma\Fhir\Model;

use Pulse\Api\Emma\Fhir\Model\EpisodeOfCareModel;

trait MockEpisodeOfCareModel
{
    public function getEpisodeOfCareModel()
    {
        $modelProphecy = $this->prophesize(EpisodeOfCareModel::class);

        return $modelProphecy->reveal();
    }
}
