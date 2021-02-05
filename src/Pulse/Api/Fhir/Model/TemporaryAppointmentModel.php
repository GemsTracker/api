<?php

namespace Pulse\Api\Fhir\Model;

use Pulse\Api\Fhir\Model\Transformer\TemporaryAppointmentInfoTransformer;

class TemporaryAppointmentModel extends \Gems\Rest\Fhir\Model\AppointmentModel
{
    public function __construct()
    {
        parent::__construct();
        $this->addTransformer(new TemporaryAppointmentInfoTransformer());

    }
}
