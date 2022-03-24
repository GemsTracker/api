<?php

declare(strict_types=1);


namespace PulseTest\Rest\Api\Emma\Fhir\Repository;


use GemsTest\Rest\Test\DbHelpers;

class AgendaStaffFixtures
{
    use DbHelpers;

    public function getData()
    {
        $data = [
            'gems__agenda_staff' => [
                [
                    'gas_id_staff' => 2001,
                    'gas_name' => 'testStaff1',
                    'gas_id_organization' => 1,
                    'gas_match_to' => 'testStaff1',
                    'gas_source' => 'testEpd',
                ],
                [
                    'gas_id_staff' => 2002,
                    'gas_name' => 'testStaff2',
                    'gas_id_organization' => 1,
                    'gas_match_to' => 'testStaff2',
                    'gas_source' => 'testEpd',
                    'gas_active' => 0,
                ],
                [
                    'gas_id_staff' => 2003,
                    'gas_name' => 'otherName',
                    'gas_id_organization' => 1,
                    'gas_match_to' => 'testStaff3',
                    'gas_source' => 'testEpd',
                ],
                [
                    'gas_id_staff' => 2004,
                    'gas_name' => 'otherName2',
                    'gas_id_organization' => 1,
                    'gas_match_to' => 'testStaff4|othername2',
                    'gas_source' => 'testEpd',
                ],
                [
                    'gas_id_staff' => 2005,
                    'gas_name' => 'otherName3',
                    'gas_id_organization' => 1,
                    'gas_match_to' => 'othername3|testStaff5',
                    'gas_source' => 'testEpd',
                ],
                [
                    'gas_id_staff' => 2006,
                    'gas_name' => 'otherName4',
                    'gas_id_organization' => 1,
                    'gas_match_to' => 'othername4|testStaff6|othername4a',
                    'gas_source' => 'testEpd',
                ],
            ],
        ];

        return $data;
    }
}
