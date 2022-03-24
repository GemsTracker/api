<?php

declare(strict_types=1);


namespace PulseTest\Rest\Api\Emma\Fhir\Repository;


use GemsTest\Rest\Test\DbHelpers;

class EpisodeOfCareFixtures
{
    use DbHelpers;

    public function getData()
    {
        $data = [
            'gems__episodes_of_care' => [
                [
                    'gec_episode_of_care_id' => 18,
                    'gec_id_user' => 1001,
                    'gec_id_organization' => 1,
                    'gec_source' => 'test',
                    'gec_id_in_source' => '123',
                    'gec_startdate' => $this->getDbNow(),
                ]
            ],
        ];

        return $data;
    }
}
