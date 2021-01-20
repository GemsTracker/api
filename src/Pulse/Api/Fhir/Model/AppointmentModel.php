<?php

namespace Pulse\Api\Fhir\Model;


use Pulse\Api\Fhir\Model\Transformer\AppointmentInfoTransformer;

class AppointmentModel extends \Gems\Rest\Fhir\Model\AppointmentModel
{
    public function __construct()
    {
        parent::__construct();
        $this->addTransformer(new AppointmentInfoTransformer());
    }
}
