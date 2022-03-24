<?php

declare(strict_types=1);


namespace PulseTest\Rest\Api\Emma\Fhir\Repository;


class ConditionFixtures
{
    public function getData()
    {
        $data = [
            'gems__medical_conditions' => [
                [
                    'gmco_id_condition' => 501,
                    'gmco_source' => 'testSource',
                    'gmco_id_source' => '123',
                    'gmco_id_user' => 1001,
                    'gmco_id_episode_of_care' => 601,
                    'gmco_status' => 'test',
                ],
                [
                    'gmco_id_condition' => 502,
                    'gmco_source' => 'testSource',
                    'gmco_id_source' => '456',
                    'gmco_id_user' => 1001,
                    'gmco_id_episode_of_care' => null,
                    'gmco_status' => 'test',
                ],
            ],
        ];

        return $data;
    }
}
