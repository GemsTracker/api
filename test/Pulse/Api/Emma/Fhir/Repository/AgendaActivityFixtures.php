<?php

declare(strict_types=1);


namespace PulseTest\Rest\Api\Emma\Fhir\Repository;


use GemsTest\Rest\Test\DbHelpers;

class AgendaActivityFixtures
{
    use DbHelpers;

    public function getData()
    {
        $data = [
            'gems__agenda_activities' => [
                [
                    'gaa_id_activity' => 4001,
                    'gaa_name' => 'testActivity1',
                    'gaa_id_organization' => 1,
                    'gaa_match_to' => 'testActivity1',
                ],
                [
                    'gaa_id_activity' => 4002,
                    'gaa_name' => 'testActivity2',
                    'gaa_id_organization' => 1,
                    'gaa_match_to' => 'testActivity2',
                    'gaa_active' => 0,
                ],
                [
                    'gaa_id_activity' => 4003,
                    'gaa_name' => 'otherName',
                    'gaa_id_organization' => 1,
                    'gaa_match_to' => 'testActivity3',
                ],
                [
                    'gaa_id_activity' => 4004,
                    'gaa_name' => 'otherName2',
                    'gaa_id_organization' => 1,
                    'gaa_match_to' => 'testActivity4|otherActivity2',
                ],
                [
                    'gaa_id_activity' => 4005,
                    'gaa_name' => 'otherName3',
                    'gaa_id_organization' => 1,
                    'gaa_match_to' => 'otherActivity3|testActivity5',
                ],
                [
                    'gaa_id_activity' => 4006,
                    'gaa_name' => 'otherName4',
                    'gaa_id_organization' => 1,
                    'gaa_match_to' => 'otherActivity4|testActivity6|otherActivity4a',
                ],
            ],
        ];

        return $data;
    }
}
