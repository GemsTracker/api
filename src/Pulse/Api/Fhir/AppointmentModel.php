<?php

namespace Pulse\Api\Fhir;


use Pulse\Api\Fhir\Transformer\AppointmentInfoTransformer;

class AppointmentModel extends \Gems\Rest\Fhir\Model\AppointmentModel
{
    public function __construct()
    {
        parent::__construct();
        $this->addTransformer(new AppointmentInfoTransformer());
    }
}
