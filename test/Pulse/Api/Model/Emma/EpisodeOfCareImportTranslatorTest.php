<?php


namespace PulseTest\Rest\Api\Model\Emma;


use GemsTest\Rest\Test\ZendDbTestCase;
use PHPUnit\DbUnit\DataSet\YamlDataSet;
use Prophecy\Argument;
use Psr\Log\LoggerInterface;
use Pulse\Api\Model\Emma\AgendaDiagnosisRepository;
use Pulse\Api\Model\Emma\EpisodeOfCareImportTranslator;
use Pulse\Api\Model\Emma\OrganizationRepository;

class EpisodeOfCareImportTranslatorTest extends ZendDbTestCase
{
    protected $usersPerOrganization = [
        70 => 2,
    ];

    /**
     * @var bool should Zend 2 adapter be loaded?
     */
    protected $loadZendDb2 = true;

    protected function getDataSet()
    {
        $file = str_replace('.php', '.yml', __FILE__);
        return new YamlDataSet($file);
    }

    public function testTranslateEpisodesEmpty()
    {
        $translator = $this->getTranslator();
        $row = [];

        $result = $translator->translateEpisodes($row, $this->usersPerOrganization);
        $this->assertEmpty($result);
    }

    public function testTranslateEpisodesNoEpisodeId()
    {
        $translator = $this->getTranslator();

        $row = [[]];

        $result = $translator->translateEpisodes($row, $this->usersPerOrganization);
        $this->assertEmpty($result);
    }

    public function testTranslateEpisodesNoStartDate()
    {
        $translator = $this->getTranslator();

        $row = [
            [
                'episode_id' => 1,
            ]
        ];

        $result = $translator->translateEpisodes($row, $this->usersPerOrganization);
        $this->assertEmpty($result);
    }

    /*public function testTranslateEpisodesNoDiagnosisCode()
    {
        $translator = $this->getTranslator();
        $startDate = '2019-01-01T01:23:45';

        $row = [
            [
                'episode_id' => 1,
                'start_date' => $startDate,
            ]
        ];

        $result = $translator->translateEpisodes($row, $this->usersPerOrganization);
        $this->assertEmpty($result);
    }

    public function testTranslateEpisodesNoDiagnosisDescription()
    {
        $translator = $this->getTranslator();
        $startDate = '2019-01-01T01:23:45';

        $row = [
            [
                'episode_id' => 1,
                'start_date' => $startDate,
                'diagnosis_code' => 10,
            ]
        ];

        $result = $translator->translateEpisodes($row, $this->usersPerOrganization);
        $this->assertEmpty($result);
    }*/

    public function testTranslateEpisodesNoOrganization()
    {
        $translator = $this->getTranslator();
        $startDate = '2019-01-01T01:23:45';

        $row = [
            [
                'episode_id' => 1,
                'start_date' => $startDate,
                'diagnosis_code' => 10,
                'diagnosis_description' => 'Test diagnosis',
            ]
        ];

        $result = $translator->translateEpisodes($row, $this->usersPerOrganization);
        $this->assertEmpty($result);
    }

    public function testTranslateEpisodesNoValidOrganization()
    {
        $translator = $this->getTranslator();
        $startDate = '2019-01-01T01:23:45';

        $row = [
            [
                'episode_id' => 1,
                'start_date' => $startDate,
                'diagnosis_code' => 10,
                'diagnosis_description' => 'Test diagnosis',
                'organization' => 'Unknown organization',
            ]
        ];

        $result = $translator->translateEpisodes($row, $this->usersPerOrganization);
        $this->assertEmpty($result);
    }

    public function testTranslateEpisodesNoValidUser()
    {
        $translator = $this->getTranslator();
        $startDate = '2019-01-01T01:23:45';

        $row = [
            [
                'episode_id' => 1,
                'start_date' => $startDate,
                'diagnosis_code' => 10,
                'diagnosis_description' => 'Test diagnosis',
                'organization' => 'Other organization',
            ]
        ];

        $result = $translator->translateEpisodes($row, $this->usersPerOrganization);
        $this->assertEmpty($result);
    }


    public function testTranslateEpisodesBasic()
    {
        $translator = $this->getTranslator();
        $startDate = '2019-01-01T01:23:45';

        $row = [
            [
                'episode_id' => 11,
                'organization' => 'Test organization',
                'start_date' => $startDate,
                'diagnosis_code' => 10,
                'diagnosis_description' => 'Test diagnosis',
            ]
        ];

        $result = $translator->translateEpisodes($row, $this->usersPerOrganization);
        $expectedResult = [
            11 => [
                'gec_id_in_source'      => 11,
                'gec_id_user'           => 2,
                'gec_id_organization'   => 70,

                'gec_source'            => 'emma',
                'gec_status'            => 'A',
                'gec_startdate'         => new \MUtil_Date($startDate, \MUtil_Date::ISO_8601),
            ],
        ];
        $this->assertEquals($expectedResult, $result);
    }

    public function testTranslateEpisodesAllInfo()
    {
        $translator = $this->getTranslator();
        $startDate = '2019-01-01T01:23:45';
        $endDate = '2021-01-01T01:23:45';

        $row = [
            [
                'episode_id' => 11,
                'organization' => 'Test organization',
                'start_date' => $startDate,
                'end_date' => $endDate,
                'diagnosis_code' => 10,
                'diagnosis_description' => 'Test diagnosis',
                'dbc_id' => 3,
                'info' => [
                    'some-form' => [
                        'info1' => 1,
                        'info2' => 2,
                    ]
                ],
                'main_practitioner' => 'someone',
            ]
        ];

        $result = $translator->translateEpisodes($row, $this->usersPerOrganization);
        $expectedResult = [
            11 => [
                'gec_id_in_source'      => 11,
                'gec_id_user'           => 2,
                'gec_id_organization'   => 70,

                'gec_source'            => 'emma',
                'gec_status'            => 'A',
                'gec_startdate'         => new \MUtil_Date($startDate, \MUtil_Date::ISO_8601),
                'gec_id_attended_by'    => 2,
                'gec_extra_data'        => json_encode([
                    'some-form' => [
                        'info1' => 1,
                        'info2' => 2,
                    ]
                ]),
                'gec_diagnosis_data'    => json_encode([
                    3 => [
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                        'diagnosis_code' => '10',
                        'diagnosis_description' => 'Test diagnosis',
                    ],
                ]),
                'gec_diagnosis'         => 'Test diagnosis',

            ],
        ];
        $this->assertEquals($expectedResult, $result);
    }

    public function testTranslateEpisodesMultipleEarlierDates()
    {
        $translator = $this->getTranslator();
        $startDate = '2019-01-01T01:23:45';
        $earlierStartDate = '2018-12-31T01:23:45';

        $row = [
            [
                'episode_id' => 11,
                'organization' => 'Test organization',
                'start_date' => $startDate,
                'diagnosis_code' => 10,
                'diagnosis_description' => 'Test diagnosis',
            ],
            [
                'episode_id' => 11,
                'organization' => 'Test organization',
                'start_date' => $earlierStartDate,
                'diagnosis_code' => 10,
                'diagnosis_description' => 'Test diagnosis',
            ],

        ];

        $result = $translator->translateEpisodes($row, $this->usersPerOrganization);
        $expectedResult = [
            11 => [
                'gec_id_in_source'      => 11,
                'gec_id_user'           => 2,
                'gec_id_organization'   => 70,

                'gec_source'            => 'emma',
                'gec_status'            => 'A',
                'gec_startdate'         => new \MUtil_Date($earlierStartDate, \MUtil_Date::ISO_8601),
            ],
        ];
        $this->assertEquals($expectedResult, $result);
    }

    public function testTranslateEpisodesMultipleLastWithInfo()
    {
        $translator = $this->getTranslator();
        $startDate = '2019-01-01T01:23:45';
        $earlierStartDate = '2018-12-31T01:23:45';

        $row = [
            [
                'episode_id' => 11,
                'organization' => 'Test organization',
                'start_date' => $startDate,
                'diagnosis_code' => 10,
                'diagnosis_description' => 'Test diagnosis',
            ],
            [
                'episode_id' => 11,
                'organization' => 'Test organization',
                'start_date' => $earlierStartDate,
                'diagnosis_code' => 10,
                'diagnosis_description' => 'Test diagnosis',
                'info' => [
                    'some-form' => [
                        'info1' => 1,
                        'info2' => 2,
                    ]
                ],
            ],

        ];

        $result = $translator->translateEpisodes($row, $this->usersPerOrganization);
        $expectedResult = [
            11 => [
                'gec_id_in_source'      => 11,
                'gec_id_user'           => 2,
                'gec_id_organization'   => 70,

                'gec_source'            => 'emma',
                'gec_status'            => 'A',
                'gec_startdate'         => new \MUtil_Date($earlierStartDate, \MUtil_Date::ISO_8601),
                'gec_extra_data'        => json_encode([
                    'some-form' => [
                        'info1' => 1,
                        'info2' => 2,
                    ]
                ]),
            ],
        ];
        $this->assertEquals($expectedResult, $result);
    }

    public function testTranslateEpisodesMultipleBothWithInfo()
    {
        $translator = $this->getTranslator();
        $startDate = '2019-01-01T01:23:45';
        $earlierStartDate = '2018-12-31T01:23:45';

        $row = [
            [
                'episode_id' => 11,
                'organization' => 'Test organization',
                'start_date' => $startDate,
                'diagnosis_code' => 10,
                'diagnosis_description' => 'Test diagnosis',
                'info' => [
                    'some-form' => [
                        [
                            'code' => 1,
                            'info1' => 1,
                        ],
                        [
                            'code' => 2,
                            'info2' => 2,
                        ],
                    ],
                ],
            ],
            [
                'episode_id' => 11,
                'organization' => 'Test organization',
                'start_date' => $earlierStartDate,
                'diagnosis_code' => 10,
                'diagnosis_description' => 'Test diagnosis',
                'info' => [
                    'some-form' => [
                        [
                            'code' => 1,
                            'info1' => 1,
                        ],
                        [
                            'code' => 3,
                            'info3' => 3,
                        ],
                    ],
                ],
            ],

        ];

        $result = $translator->translateEpisodes($row, $this->usersPerOrganization);
        $expectedResult = [
            11 => [
                'gec_id_in_source'      => 11,
                'gec_id_user'           => 2,
                'gec_id_organization'   => 70,

                'gec_source'            => 'emma',
                'gec_status'            => 'A',
                'gec_startdate'         => new \MUtil_Date($earlierStartDate, \MUtil_Date::ISO_8601),
                'gec_extra_data'        => json_encode([
                    'some-form' => [
                        [
                            'code' => 1,
                            'info1' => 1,
                        ],
                        [
                            'code' => 2,
                            'info2' => 2,
                        ],
                        [
                            'code' => 3,
                            'info3' => 3,
                        ],
                    ]
                ]),
            ],
        ];
        $this->assertEquals($expectedResult, $result);
    }

    public function testTranslateEpisodesExisting()
    {
        $translator = $this->getTranslator();
        $startDate = '2019-01-01T01:23:45';

        $row = [
            [
                'episode_id' => 1,
                'organization' => 'Test organization',
                'start_date' => $startDate,
                'diagnosis_code' => 10,
                'diagnosis_description' => 'Test diagnosis',
            ]
        ];

        $result = $translator->translateEpisodes($row, $this->usersPerOrganization);
        $expectedResult = [
            1 => [
                'gec_id_in_source'      => 1,
                'gec_id_user'           => 2,
                'gec_id_organization'   => 70,

                'gec_source'            => 'emma',
                'gec_status'            => 'A',
                'gec_startdate'         => new \MUtil_Date($startDate, \MUtil_Date::ISO_8601),
                'gec_episode_of_care_id' => '11'
            ],
        ];
        $this->assertEquals($expectedResult, $result);
    }

    public function testTranslateEpisodesStartDateWithTimezones()
    {
        $translator = $this->getTranslator();
        $startDate = '2019-01-01T01:23:45';

        $row = [
            [
                'episode_id' => 11,
                'organization' => 'Test organization',
                'start_date' => $startDate . '+00:00',
                'diagnosis_code' => 10,
                'diagnosis_description' => 'Test diagnosis',
            ]
        ];

        $result = $translator->translateEpisodes($row, $this->usersPerOrganization);
        $expectedResult = [
            11 => [
                'gec_id_in_source'      => 11,
                'gec_id_user'           => 2,
                'gec_id_organization'   => 70,

                'gec_source'            => 'emma',
                'gec_status'            => 'A',
                'gec_startdate'         => new \MUtil_Date($startDate, \MUtil_Date::ISO_8601),
            ],
        ];
        $this->assertEquals($expectedResult, $result);
    }

    protected function getTranslator()
    {
        $agendaDiagnosisRepository = $this->prophesize(AgendaDiagnosisRepository::class);
        //$agendaDiagnosisRepository->matchDiagnosis()

        $logger = $this->prophesize(LoggerInterface::class);

        $organizationRepository = $this->prophesize(OrganizationRepository::class);
        $organizationRepository->getOrganizationId('Test organization')->willReturn(70);
        $organizationRepository->getOrganizationId('Other organization')->willReturn(75);
        $organizationRepository->getOrganizationId(Argument::type('string'))->willReturn(null);

        $agenda = $this->prophesize(\Gems_Agenda::class);
        $agenda->matchHealthcareStaff('someone', 70)->willReturn(2);

        return new EpisodeOfCareImportTranslator($this->db,
            $agendaDiagnosisRepository->reveal(),
            $logger->reveal(),
            $organizationRepository->reveal(),
            $agenda->reveal()
        );
    }
}