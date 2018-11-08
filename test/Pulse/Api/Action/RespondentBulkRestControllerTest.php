<?php

namespace PulseTest\Api\Action;

use GemsTest\Rest\Test\RequestTestUtils;
use GemsTest\Rest\Test\ZendDbTestCase;
use PHPUnit\DbUnit\DataSet\DefaultDataSet;
use PHPUnit\DbUnit\DataSet\YamlDataSet;
use Prophecy\Argument;
use Psr\Log\LoggerInterface;
use Pulse\Api\Action\RespondentBulkRestController;
use Pulse\Api\Model\Emma\AgendaDiagnosisRepository;
use Pulse\Api\Model\Emma\AppointmentRepository;
use Pulse\Api\Model\Emma\OrganizationRepository;
use Pulse\Api\Model\Emma\RespondentRepository;
use Zalt\Loader\ProjectOverloader;
use Zend\Db\Sql\Sql;
use Zend\Diactoros\Response\EmptyResponse;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Expressive\Helper\UrlHelper;
use Zend\ServiceManager\ServiceManager;

class RespondentBulkRestControllerTest extends ZendDbTestCase
{
    use RequestTestUtils;

    protected $loadZendDb1 = true;
    protected $loadZendDb2 = true;

    protected $routeOptions = [
        'model' => 'Model_RespondentModel',
        'methods' => ['GET', 'POST', 'PATCH'],
        'idField' => [
            'gr2o_patient_nr',
            'gr2o_id_organization',
        ],
        'idFieldRegex' => [
            '[0-9]{6}-A[0-9]{3}',
            '\d+',
        ],
    ];

    protected function getDataSet()
    {
        $file = str_replace('.php', '.yml', __FILE__);
        return new YamlDataSet($file);
    }

    public function testContentType()
    {
        $controller = $this->getController(true);

        $newData = [];
        $request = $this->getRequest('POST', [], [], $newData, $this->routeOptions, '', null);
        $delegator = $this->getDelegator();

        $response = $controller->process($request, $delegator);
        $this->checkResponse($response, EmptyResponse::class, 415);
    }

    public function testEmptyRespondent()
    {
        $controller = $this->getController(true);

        $newData = [];
        $request = $this->getRequest('POST', [], [], $newData, $this->routeOptions);
        $delegator = $this->getDelegator();

        $response = $controller->process($request, $delegator);
        $this->checkResponse($response, EmptyResponse::class, 400);
    }

    public function testMissingPatientNr()
    {
        $controller = $this->getController(true);

        $newData = [
            'test' => 'nyan',
        ];
        $request = $this->getRequest('POST', [], [], $newData, $this->routeOptions);
        $delegator = $this->getDelegator();

        $response = $controller->process($request, $delegator);
        $this->checkResponse($response, JsonResponse::class, 400);
        $responseData = $response->getPayload();
        $this->assertEquals('missing_data', $responseData['error'], 'Error code is missing or not "missing_data"');
    }

    public function testMissingOrganizations()
    {
        $controller = $this->getController(true);

        $newData = [
            'patient_nr' => 1,
        ];

        $request = $this->getRequest('POST', [], [], $newData, $this->routeOptions);
        $delegator = $this->getDelegator();

        $response = $controller->process($request, $delegator);
        $this->checkResponse($response, JsonResponse::class, 400);
        $responseData = $response->getPayload();
        $this->assertEquals('missing_data', $responseData['error'], 'Error code is missing or not "missing_data"');
    }

    public function testWrongOrganizations()
    {
        $controller = $this->getController(true);

        $newData = [
            'patient_nr' => 1,
            'organizations' => [
                'Nonexisting',
            ]
        ];

        $request = $this->getRequest('POST', [], [], $newData, $this->routeOptions);
        $delegator = $this->getDelegator();

        $response = $controller->process($request, $delegator);
        $this->checkResponse($response, JsonResponse::class, 200);
        $responseData = $response->getPayload();
        $this->assertEquals(
            'Skipping patient import because no organizations have been found in Pulse for 1',
            $responseData['message'],
            'Error message not found or not as expected'
        );
    }

    public function testAddingPatientWithoutLastName()
    {
        $controller = $this->getController(true);

        $newData = [
            'patient_nr' => 1,
            'organizations' => [
                'Test organization',
            ],
        ];

        $request = $this->getRequest('POST', [], [], $newData, $this->routeOptions);
        $delegator = $this->getDelegator();

        $response = $controller->process($request, $delegator);

        $this->checkResponse($response, JsonResponse::class, 400);

        $responseData = $response->getPayload();
        $this->assertEquals('validation_error', $responseData['error'], 'Error code is missing or not "validation_error"');
        $this->assertCount(1, $responseData['errors'], 'There are more than 1 expected validation errors');
        $this->assertArrayHasKey('grs_last_name', $responseData['errors'], 'Omission of last name should trigger an error');
    }

    public function testAddingMinimumRequiredPatient()
    {
        $controller = $this->getController(true);

        $newData = [
            'gr2o_patient_nr' => '11',
            'organizations' => [
                'Test organization',
            ],
            'grs_last_name' => 'De Jong',
            'gr2o_reception_code' => 'OK', // default in sqlite gets quoted extra
        ];

        $request = $this->getRequest('POST', [], [], $newData, $this->routeOptions);
        $delegator = $this->getDelegator();

        $response = $controller->process($request, $delegator);
        $this->checkResponse($response, EmptyResponse::class, 201);

        $request = $this->getRequest('GET', ['gr2o_patient_nr' => 11, 'gr2o_id_organization' => 1], [], [], $this->routeOptions);
        $response = $controller->process(
            $request,
            $delegator
        );

        $expectedData = $newData;
        unset($expectedData['organizations']);
        $expectedData['gr2o_id_organization'] = '1';

        $responseData = array_merge($expectedData, array_intersect_key($response->getPayload(), $expectedData));

        $this->assertEquals($expectedData, $responseData, 'parsed body not the same as expected data');
    }

    public function testUpdatingPatient()
    {
        $controller = $this->getController(true);

        $newData = [
            'gr2o_patient_nr' => '11',
            'organizations' => [
                'Test organization',
            ],
            'grs_last_name' => 'Janssen',
            'gr2o_reception_code' => 'OK', // default in sqlite gets quoted extra
        ];

        $request = $this->getRequest('POST', [], [], $newData, $this->routeOptions);
        $delegator = $this->getDelegator();

        $response = $controller->process($request, $delegator);
        $this->checkResponse($response, EmptyResponse::class, 201);

        $request = $this->getRequest('GET', ['gr2o_patient_nr' => 11, 'gr2o_id_organization' => 1], [], [], $this->routeOptions);
        $response = $controller->process(
            $request,
            $delegator
        );

        $expectedData = $newData;
        unset($expectedData['organizations']);
        $expectedData['gr2o_id_organization'] = '1';

        $responseData = array_merge($expectedData, array_intersect_key($response->getPayload(), $expectedData));

        $this->assertEquals($expectedData, $responseData, 'parsed body not the same as expected data');
    }

    public function testAddingSsnToPatient()
    {
        $controller = $this->getController(true);

        $newData = [
            'gr2o_patient_nr' => '11',
            'organizations' => [
                'Test organization',
            ],
            'grs_last_name' => 'Janssen',
            'grs_ssn' => '666268538', // Random bsn
            'gr2o_reception_code' => 'OK', // default in sqlite gets quoted extra
        ];

        $request = $this->getRequest('POST', [], [], $newData, $this->routeOptions);
        $delegator = $this->getDelegator();

        $response = $controller->process($request, $delegator);
        $this->checkResponse($response, EmptyResponse::class, 201);

        $request = $this->getRequest('GET', ['gr2o_patient_nr' => 11, 'gr2o_id_organization' => 1], [], [], $this->routeOptions);
        $response = $controller->process(
            $request,
            $delegator
        );

        $expectedData = $newData;
        unset($expectedData['organizations']);
        $expectedData['gr2o_id_organization'] = '1';

        $responseData = array_merge($expectedData, array_intersect_key($response->getPayload(), $expectedData));

        $this->assertEquals($expectedData, $responseData, 'parsed body not the same as expected data');
    }

    public function testChangingPatientNrOfExistingSsnPatient()
    {
        $controller = $this->getController(true);

        $newData = [
            'gr2o_patient_nr' => '11',
            'organizations' => [
                'Test organization',
            ],
            'grs_last_name' => 'Janssen',
            'grs_ssn' => '666268538', // Random bsn
            'gr2o_reception_code' => 'OK', // default in sqlite gets quoted extra
        ];

        $request = $this->getRequest('POST', [], [], $newData, $this->routeOptions);
        $delegator = $this->getDelegator();

        $response = $controller->process($request, $delegator);
        $this->checkResponse($response, EmptyResponse::class, 201);

        $newData['gr2o_patient_nr'] = '22';

        $request = $this->getRequest('POST', [], [], $newData, $this->routeOptions);
        $delegator = $this->getDelegator();

        $response = $controller->process($request, $delegator);
        $this->checkResponse($response, EmptyResponse::class, 201);

        $request = $this->getRequest('GET', ['gr2o_patient_nr' => 22, 'gr2o_id_organization' => 1], [], [], $this->routeOptions);
        $response = $controller->process(
            $request,
            $delegator
        );

        $expectedData = $newData;
        unset($expectedData['organizations']);
        $expectedData['gr2o_id_organization'] = '1';

        $responseData = array_merge($expectedData, array_intersect_key($response->getPayload(), $expectedData));

        $this->assertEquals($expectedData, $responseData, 'parsed body not the same as expected data');
    }

    public function testPatientWithMultipleOrganizations()
    {
        $controller = $this->getController(true);
        $delegator = $this->getDelegator();

        $newData = [
            'gr2o_patient_nr' => '22',
            'organizations' => [
                'Test organization',
                'Another test organization',
            ],
            'grs_last_name' => 'Janssen',
            'grs_ssn' => '666268538', // Random bsn
            'gr2o_reception_code' => 'OK', // default in sqlite gets quoted extra
        ];

        $request = $this->getRequest('POST', [], [], $newData, $this->routeOptions);

        $response = $controller->process($request, $delegator);
        $this->checkResponse($response, EmptyResponse::class, 201);

        $select = $this->db1->select();
        $select->from('gems__respondent2org');
        $patients = $this->db1->fetchAll($select);

        $this->assertCount(2, $patients, sprintf('There were %d patients found, while 2 were expected', count($patients)));

        $this->assertEquals($patients[0]['gr2o_id_user'], $patients[1]['gr2o_id_user'], 'The patients are not merged as one respondent');
    }

    public function testMultiplePatientsWithSameNumberButDifferentSsn()
    {
        $controller = $this->getController(true);
        $delegator = $this->getDelegator();

        $newData = [
            'gr2o_patient_nr' => '22',
            'organizations' => [
                'Test organization',
            ],
            'grs_last_name' => 'Janssen',
            'grs_ssn' => '666268538', // Random bsn
            'gr2o_reception_code' => 'OK', // default in sqlite gets quoted extra
        ];

        $request = $this->getRequest('POST', [], [], $newData, $this->routeOptions);

        $response = $controller->process($request, $delegator);
        $this->checkResponse($response, EmptyResponse::class, 201);

        $newData = [
            'gr2o_patient_nr' => '22',
            'organizations' => [
                'Test organization',
            ],
            'grs_last_name' => 'Janssen',
            'grs_ssn' => '454619224', // Random bsn
            'gr2o_reception_code' => 'OK', // default in sqlite gets quoted extra
        ];

        $request = $this->getRequest('POST', [], [], $newData, $this->routeOptions);

        $response = $controller->process($request, $delegator);
        $this->checkResponse($response, JsonResponse::class, 400);

        $responseData = $response->getPayload();
        $this->assertEquals('model_translation_error', $responseData['error'], 'Error code is missing or not "model_translation_error"');
    }

    public function testMultiplePatientsWithSameNumberButOneWithoutSsnButUpdateWithSsn()
    {
        $controller = $this->getController(true);
        $delegator = $this->getDelegator();

        $newData = [
            'gr2o_patient_nr' => '22',
            'organizations' => [
                'Test organization',
            ],
            'grs_last_name' => 'Janssen',
            'grs_ssn' => '666268538', // Random bsn
            'gr2o_reception_code' => 'OK', // default in sqlite gets quoted extra
        ];

        $request = $this->getRequest('POST', [], [], $newData, $this->routeOptions);

        $response = $controller->process($request, $delegator);
        $this->checkResponse($response, EmptyResponse::class, 201);

        $newData = [
            'gr2o_patient_nr' => '22',
            'organizations' => [
                'Another test organization',
            ],
            'grs_last_name' => 'Janssen',
            'gr2o_reception_code' => 'OK', // default in sqlite gets quoted extra
        ];

        $request = $this->getRequest('POST', [], [], $newData, $this->routeOptions);

        $response = $controller->process($request, $delegator);
        $this->checkResponse($response, EmptyResponse::class, 201);

        $newData = [
            'gr2o_patient_nr' => '22',
            'organizations' => [
                'Another test organization',
            ],
            'grs_last_name' => 'Janssen',
            'grs_ssn' => '666268538', // Random bsn
            'gr2o_reception_code' => 'OK', // default in sqlite gets quoted extra
        ];

        $request = $this->getRequest('POST', [], [], $newData, $this->routeOptions);

        $response = $controller->process($request, $delegator);
        $this->checkResponse($response, EmptyResponse::class, 201);
    }

    public function testUpdatingPatientWithoutAlreadySavedSsn()
    {
        $controller = $this->getController(true);
        $delegator = $this->getDelegator();

        $newData = [
            'gr2o_patient_nr' => '22',
            'organizations' => [
                'Test organization',
            ],
            'grs_last_name' => 'Janssen',
            'grs_ssn' => '666268538', // Random bsn
            'gr2o_reception_code' => 'OK', // default in sqlite gets quoted extra
        ];

        $request = $this->getRequest('POST', [], [], $newData, $this->routeOptions);

        $response = $controller->process($request, $delegator);
        $this->checkResponse($response, EmptyResponse::class, 201);

        $newData = [
            'gr2o_patient_nr' => '22',
            'organizations' => [
                'Another test organization',
            ],
            'grs_last_name' => 'Janssen',
            'gr2o_reception_code' => 'OK', // default in sqlite gets quoted extra
        ];

        $request = $this->getRequest('POST', [], [], $newData, $this->routeOptions);

        $response = $controller->process($request, $delegator);
        $this->checkResponse($response, EmptyResponse::class, 201);
    }

    private function getController($realLoader=false, $urlHelperRoutes=[])
    {
        $loader = new ProjectOverloader([
            //'Pulse',
            'Gems',
            'MUtil',
        ]);
        $loader->legacyClasses = true;
        $loader->legacyPrefix = 'Legacy';

        //$model = new \Pulse_Model_RespondentModel();
        $model = new \Gems_Model_RespondentModel();
        // Remove ssn hashing in tests
        $model->del('grs_ssn', \MUtil_Model_ModelAbstract::SAVE_TRANSFORMER, \MUtil_Model_ModelAbstract::LOAD_TRANSFORMER, \MUtil_Model_ModelAbstract::SAVE_WHEN_TEST);

        $currentUserProphecy = $this->prophesize(\Gems_User_User::class);
        $currentUserProphecy->getUserId()->willReturn(1);
        $currentUserProphecy->getGroup()->willReturn(null);
        $currentUserProphecy->getRespondentOrganizations()->willReturn(null);
        $currentUserProphecy->getCurrentOrganizationId()->willReturn(1);
        $currentUserProphecy->getAllowedOrganizations()->willReturn(null);
        $currentUserProphecy->hasPrivilege(Argument::type('string'))->willReturn(null);


        $organizationProphecy = $this->prophesize(\Gems_User_Organization::class);
        $legacyLoaderProphecy = $this->prophesize(\Gems_Loader::class);
        $legacyLoaderProphecy->getOrganization(Argument::cetera())->willReturn($organizationProphecy->reveal());

        $utilProphecy = $this->prophesize(\Gems_Util::class);

        //$dbLookup = $this->prophesize(\Pulse_Util_DbLookup::class);
        $dbLookup = $this->prophesize(\Gems_Util_DbLookup::class);
        $localized = $this->prophesize(\Gems_Util_Localized::class);
        //$translated = $this->prophesize(\Pulse_Util_Translated::class);
        $translated = $this->prophesize(\Gems_Util_Translated::class);
        $utilProphecy->getDbLookup()->willReturn($dbLookup->reveal());
        $utilProphecy->getLocalized()->willReturn($localized->reveal());
        $utilProphecy->getTranslated()->willReturn($translated->reveal());
        $utilProphecy->getDefaultConsent()->willReturn('Unknown');

        $projectProphecy = $this->prophesize(\Gems_Project_ProjectSettings::class);
        $projectProphecy->getLocaleDefault()->willReturn('en');

        $projectProphecy->getValueHash(Argument::any(), Argument::cetera())->willReturnArgument(1);

        $viewProphecy = $this->prophesize(\Zend_View_Abstract::class);

        $containerProphecy = $this->prophesize(ServiceManager::class);

        $containerProphecy->has('LegacyLoader')->willReturn(true);
        $containerProphecy->get('LegacyLoader')->willReturn($legacyLoaderProphecy->reveal());

        $containerProphecy->has('LegacyCurrentUser')->willReturn(true);
        $containerProphecy->get('LegacyCurrentUser')->willReturn($currentUserProphecy->reveal());

        $containerProphecy->has('LegacyUtil')->willReturn(true);
        $containerProphecy->get('LegacyUtil')->willReturn($utilProphecy->reveal());

        $containerProphecy->has('LegacyProject')->willReturn(true);
        $containerProphecy->get('LegacyProject')->willReturn($projectProphecy->reveal());

        $containerProphecy->has('LegacyView')->willReturn(true);
        $containerProphecy->get('LegacyView')->willReturn($viewProphecy->reveal());

        $containerProphecy->has('LegacyDb')->willReturn(true);
        $containerProphecy->get('LegacyDb')->willReturn($this->db1);

        $containerProphecy->has(Argument::type('string'))->willReturn(false);
        $containerProphecy->setService('loader', Argument::type('object'))->willReturn(null);

        $container = $containerProphecy->reveal();

        $loader->setServiceManager($container);

        $loader->applyToLegacyTarget($model);

        $urlHelperProphecy = $this->prophesize(UrlHelper::class);

        foreach ($urlHelperRoutes as $route=>$url) {
            $urlHelperProphecy->generate($route, Argument::cetera())->willReturn($url);
        }

        $agendaDiagnosisRepositoryProphecy = $this->prophesize(AgendaDiagnosisRepository::class);
        $appointmentRepositoryProphecy = $this->prophesize(AppointmentRepository::class);
        $organizationRepositoryProphecy = $this->prophesize(OrganizationRepository::class);

        $organizationRepositoryProphecy->getOrganizationTranslations(['Test organization'])->willReturn(['1' => 'Test organization']);
        $organizationRepositoryProphecy->getOrganizationTranslations(['Another test organization'])->willReturn(['2' => 'Another test organization']);
        $organizationRepositoryProphecy->getOrganizationTranslations(
            [
                'Test organization',
                'Another test organization'
            ]
        )->willReturn(
            [
                '1' => 'Test organization',
                '2' => 'Another test organization'
            ]);
        $organizationRepositoryProphecy->getOrganizationTranslations(Argument::type('array'))->willReturn([]);
        $organizationRepositoryProphecy->getLocationFromOrganizationName(Argument::type('string'))->willReturn(null);

        //$respondentRepositoryProphecy = $this->prophesize(RespondentRepository::class);
        $respondentRepository = new RespondentRepository($this->db);
        $gemsAgendaProphecy = $this->prophesize(\Gems_Agenda::class);
        $modelClass = \Gems_Model::class;
        if (class_exists(\Pulse_Model::class, true)) {
            $modelClass = \Pulse_Model::class;
        }

        $gemsModelProphecy = $this->prophesize($modelClass);

        $legacyLoaderProphecy = $this->prophesize(\Gems_Loader::class);
        $emmaImportLoggerProphecy = $this->prophesize(LoggerInterface::class);

        $controller =  new RespondentBulkRestController($loader, $urlHelperProphecy->reveal(), $this->db,
            $agendaDiagnosisRepositoryProphecy->reveal(),
            $appointmentRepositoryProphecy->reveal(),
            $organizationRepositoryProphecy->reveal(),
            //$respondentRepositoryProphecy->reveal(),
            $respondentRepository,
            $gemsAgendaProphecy->reveal(),
            $gemsModelProphecy->reveal(),
            $legacyLoaderProphecy->reveal(),
            $emmaImportLoggerProphecy->reveal(),
            $this->db1
        );

        $controller->setModelName($model);

        return $controller;
    }
}