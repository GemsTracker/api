<?php

namespace Pulse\Api\Fhir\Model;


use Pulse\Api\Fhir\Model\Transformer\FilterXpertClinicOldAppointments;
use Pulse\Api\Fhir\Model\Transformer\TemporaryAppointmentInfoTransformer;

/**
 * The Soulve AppointmentModel has 3 additions:
 *
 * 1. Only Xpert Clinics Hand pols & Handtherapie appointments with an appointment > 14 Oktober 2021 00:00:00 are returned
 * (FilterXpertClinicOldAppointments
 *
 * 2. The time of a surgery appointments datetime is only added 3 days before the actual appointment
 * 3. The time you have to be present for an appointment is shown instead of the actual appointment start datetime
 * (TemporaryAppointmentInfoTransformer)
 */
class SoulveAppointmentModel extends AppointmentModel
{
    public function __construct()
    {
        parent::__construct();
        $this->addTransformer(new FilterXpertClinicOldAppointments());
        $this->addTransformer(new TemporaryAppointmentInfoTransformer());

    }
}
