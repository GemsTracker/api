<?php

declare(strict_types=1);


namespace PulseTest\Rest\Api\Emma\Fhir\Repository;


use GemsTest\Rest\Test\DbHelpers;

class AppointmentFixtures
{
    use DbHelpers;

    public function getData()
    {
        $data = [
            'gems__appointments' => [
                [
                    'gap_id_appointment' => 801,
                    'gap_id_user' => 1001,
                    'gap_id_organization' => 1,
                    'gap_source' => 'testEpdName',
                    'gap_id_in_source' => '123',
                    'gap_admission_time' => $this->getDbNow(),
                ]
            ],
        ];

        return $data;
    }
}
