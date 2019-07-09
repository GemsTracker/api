<?php

namespace PulseTest\Api\Action;

use Gems\Model\EpisodeOfCareModel;
use Gems\Rest\Model\ModelException;
use Gems\Rest\Model\ModelValidationException;
use Gems\Rest\Repository\AccesslogRepository;
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

    public function testAddingLocation()
    {
        $controller = $this->getController(true);

        $newData = [
            'gr2o_patient_nr' => '11',
            'organizations' => [
                'Test organization Home',
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

    public function testFailingModelModelException()
    {
        $controller = $this->getController(false, false);

        $modelProphecy = $this->prophesize(\Gems_Model_RespondentModel::class);
        $modelProphecy->getCol(Argument::type('string'))->willReturn([]);
        $modelProphecy->setAutoSave(Argument::type('string'))->willReturn(null);
        $modelProphecy->setOnSave(Argument::type('string'), Argument::type('array'))->willReturn(null);
        $modelProphecy->applyEditSettings(true)->willReturn(null);
        $modelProphecy->getSaveTables()->willReturn([]);
        $modelProphecy->getJoinFields()->willReturn([]);
        $modelProphecy->getKeys()->willReturn([]);
        $modelProphecy->get(Argument::type('string'), 'type')->willReturn(null);
        $modelProphecy->loadNew()->willReturn([]);
        $modelProphecy->save(Argument::type('array'))->willThrow(new ModelException('model exception'));

        $controller->setModelName($modelProphecy->reveal());

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

        $this->assertEquals('model_error', $responseData['error'], 'Error code is missing or not "model_error"');
        $this->assertEquals('model exception', $responseData['message'], 'Exception message not as expected');
    }

    public function testFailingModelOtherException()
    {
        $controller = $this->getController(false, false);

        $modelProphecy = $this->prophesize(\Gems_Model_RespondentModel::class);
        $modelProphecy->getCol(Argument::type('string'))->willReturn([]);
        $modelProphecy->setAutoSave(Argument::type('string'))->willReturn(null);
        $modelProphecy->setOnSave(Argument::type('string'), Argument::type('array'))->willReturn(null);
        $modelProphecy->applyEditSettings(true)->willReturn(null);
        $modelProphecy->getSaveTables()->willReturn([]);
        $modelProphecy->getJoinFields()->willReturn([]);
        $modelProphecy->getKeys()->willReturn([]);
        $modelProphecy->get(Argument::type('string'), 'type')->willReturn(null);
        $modelProphecy->loadNew()->willReturn([]);
        $modelProphecy->save(Argument::type('array'))->willThrow(new \Exception('unknown exception'));

        $controller->setModelName($modelProphecy->reveal());

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

        $this->assertEquals('unknown_error', $responseData['error'], 'Error code is missing or not "model_error"');
        $this->assertEquals('unknown exception', $responseData['message'], 'Exception message not as expected');
    }

    public function testAddingAppointment()
    {
        $controller = $this->getController(true);

        $newData = [
            'gr2o_patient_nr' => '11',
            'organizations' => [
                'Test organization',
            ],
            'grs_last_name' => 'De Jong',
            'gr2o_reception_code' => 'OK', // default in sqlite gets quoted extra
            'appointments' => [
                [
                    'id' => 1,
                    'organization' => 'Test organization',
                    'admission_time' => '2019-01-01T01:23:45',
                ],
            ],
        ];

        $request = $this->getRequest('POST', [], [], $newData, $this->routeOptions);
        $delegator = $this->getDelegator();

        $response = $controller->process($request, $delegator);

        $this->checkResponse($response, EmptyResponse::class, 201);

        $testResultArray = $this->getAllAppointmentsFromDb();

        $this->assertEquals($testResultArray[0]['gap_id_in_source'], '1');
        $this->assertEquals($testResultArray[0]['gap_id_organization'], '1');
        $this->assertEquals($testResultArray[0]['gap_admission_time'], '2019-01-01T01:23:45');
    }

    public function testAddingAppointmentNoId()
    {
        $controller = $this->getController(true);

        $newData = [
            'gr2o_patient_nr' => '11',
            'organizations' => [
                'Test organization',
            ],
            'grs_last_name' => 'De Jong',
            'gr2o_reception_code' => 'OK', // default in sqlite gets quoted extra
            'appointments' => [
                [
                    'organization' => 'Test organization',
                    'admission_time' => '2019-01-01T01:23:45',
                ],
            ],
        ];

        $request = $this->getRequest('POST', [], [], $newData, $this->routeOptions);
        $delegator = $this->getDelegator();

        $response = $controller->process($request, $delegator);

        $this->checkResponse($response, EmptyResponse::class, 201);

        $testResultArray = $this->getAllAppointmentsFromDb();
        $this->assertEmpty($testResultArray);
    }

    public function testAddingAppointmentNoOrganization()
    {
        $controller = $this->getController(true);

        $newData = [
            'gr2o_patient_nr' => '11',
            'organizations' => [
                'Test organization',
            ],
            'grs_last_name' => 'De Jong',
            'gr2o_reception_code' => 'OK', // default in sqlite gets quoted extra
            'appointments' => [
                [
                    'id' => 1,
                    'admission_time' => '2019-01-01T01:23:45',
                ],
            ],
        ];

        $request = $this->getRequest('POST', [], [], $newData, $this->routeOptions);
        $delegator = $this->getDelegator();

        $response = $controller->process($request, $delegator);

        $this->checkResponse($response, EmptyResponse::class, 201);

        $testResultArray = $this->getAllAppointmentsFromDb();
        $this->assertEmpty($testResultArray);
    }

    public function testAddingAppointmentWrongOrganization()
    {
        $controller = $this->getController(true);

        $newData = [
            'gr2o_patient_nr' => '11',
            'organizations' => [
                'Test organization',
            ],
            'grs_last_name' => 'De Jong',
            'gr2o_reception_code' => 'OK', // default in sqlite gets quoted extra
            'appointments' => [
                [
                    'id' => 1,
                    'organization' => 'Wrong organization',
                    'admission_time' => '2019-01-01T01:23:45',
                ],
            ],
        ];

        $request = $this->getRequest('POST', [], [], $newData, $this->routeOptions);
        $delegator = $this->getDelegator();

        $response = $controller->process($request, $delegator);

        $this->checkResponse($response, EmptyResponse::class, 201);

        $testResultArray = $this->getAllAppointmentsFromDb();
        $this->assertEmpty($testResultArray);
    }

    public function testAddingAppointmentValidationError()
    {
        $controller = $this->getController(true);

        $newData = [
            'gr2o_patient_nr' => '11',
            'organizations' => [
                'Test organization',
            ],
            'grs_last_name' => 'De Jong',
            'gr2o_reception_code' => 'OK', // default in sqlite gets quoted extra
            'appointments' => [
                [
                    'id' => 1,
                    'organization' => 'Test organization',
                ],
            ],
        ];

        $request = $this->getRequest('POST', [], [], $newData, $this->routeOptions);
        $delegator = $this->getDelegator();

        $response = $controller->process($request, $delegator);

        $this->checkResponse($response, EmptyResponse::class, 201);

        $testResultArray = $this->getAllAppointmentsFromDb();
        $this->assertEmpty($testResultArray);
    }

    public function testAddingAppointmentModelError()
    {
        $controller = $this->getController(true);
        $appointmentModel = $this->prophesize(\Gems_Model_AppointmentModel::class);
        $appointmentModel->getCol(Argument::type('string'))->willReturn([]);
        $appointmentModel->setAutoSave(Argument::type('string'))->willReturn(null);
        $appointmentModel->setOnSave(Argument::type('string'), Argument::type('array'))->willReturn(null);
        $appointmentModel->applyEditSettings(1)->willReturn(null);
        $appointmentModel->applyEditSettings(true)->willReturn(null);
        $appointmentModel->getSaveTables()->willReturn([]);
        $appointmentModel->getJoinFields()->willReturn([]);
        $appointmentModel->getKeys()->willReturn([]);
        $appointmentModel->get(Argument::type('string'), 'type')->willReturn(null);
        $appointmentModel->loadNew()->willReturn([]);
        $appointmentModel->save(Argument::type('array'))->willThrow(new ModelException('model exception'));

        $controller->setAppointmentModel($appointmentModel->reveal());

        $newData = [
            'gr2o_patient_nr' => '11',
            'organizations' => [
                'Test organization',
            ],
            'grs_last_name' => 'De Jong',
            'gr2o_reception_code' => 'OK', // default in sqlite gets quoted extra
            'appointments' => [
                [
                    'id' => 1,
                    'organization' => 'Test organization',
                    'admission_time' => '2019-01-01T01:23:45',
                ],
            ],
        ];

        $request = $this->getRequest('POST', [], [], $newData, $this->routeOptions);
        $delegator = $this->getDelegator();

        $response = $controller->process($request, $delegator);

        $this->checkResponse($response, EmptyResponse::class, 201);

        $testResultArray = $this->getAllAppointmentsFromDb();
        $this->assertEmpty($testResultArray);
    }

    public function testAddingAppointmentUnkownError()
    {
        $controller = $this->getController(true);
        $appointmentModel = $this->prophesize(\Gems_Model_AppointmentModel::class);
        $appointmentModel->getCol(Argument::type('string'))->willReturn([]);
        $appointmentModel->setAutoSave(Argument::type('string'))->willReturn(null);
        $appointmentModel->setOnSave(Argument::type('string'), Argument::type('array'))->willReturn(null);
        $appointmentModel->applyEditSettings(true)->willReturn(null);
        $appointmentModel->applyEditSettings(Argument::any())->willReturn(null);
        $appointmentModel->getSaveTables()->willReturn([]);
        $appointmentModel->getJoinFields()->willReturn([]);
        $appointmentModel->getKeys()->willReturn([]);
        $appointmentModel->get(Argument::type('string'), 'type')->willReturn(null);
        $appointmentModel->loadNew()->willReturn([]);
        $appointmentModel->save(Argument::type('array'))->willThrow(new \Exception('unknown exception'));

        $controller->setAppointmentModel($appointmentModel->reveal());

        $newData = [
            'gr2o_patient_nr' => '11',
            'organizations' => [
                'Test organization',
            ],
            'grs_last_name' => 'De Jong',
            'gr2o_reception_code' => 'OK', // default in sqlite gets quoted extra
            'appointments' => [
                [
                    'id' => 1,
                    'organization' => 'Test organization',
                    'admission_time' => '2019-01-01T01:23:45',
                ],
            ],
        ];

        $request = $this->getRequest('POST', [], [], $newData, $this->routeOptions);
        $delegator = $this->getDelegator();

        $response = $controller->process($request, $delegator);

        $this->checkResponse($response, EmptyResponse::class, 201);

        $testResultArray = $this->getAllAppointmentsFromDb();
        $this->assertEmpty($testResultArray);
    }

    public function testAddingEpisode()
    {
        $controller = $this->getController(true);

        $newData = [
            'gr2o_patient_nr' => '11',
            'organizations' => [
                'Test organization',
            ],
            'grs_last_name' => 'De Jong',
            'gr2o_reception_code' => 'OK', // default in sqlite gets quoted extra
            'episodes' => [
                [
                    'episode_id' => 1,
                    'start_date' => '2019-01-01T01:23:45',
                    'organization' => 'Test organization',

                ],
            ],
        ];

        $request = $this->getRequest('POST', [], [], $newData, $this->routeOptions);
        $delegator = $this->getDelegator();

        $response = $controller->process($request, $delegator);

        $this->checkResponse($response, EmptyResponse::class, 201);

        $testResultArray = $this->getAllEpisodesFromDb();

        $this->assertEquals($testResultArray[0]['gec_id_in_source'], '1');
        $this->assertEquals($testResultArray[0]['gec_id_organization'], '1');
    }

    /*public function testUpdatingEpisode()
    {
        $controller = $this->getController(true);

        $newData = [
            'gr2o_patient_nr' => '11',
            'organizations' => [
                'Test organization',
            ],
            'grs_last_name' => 'De Jong',
            'gr2o_reception_code' => 'OK', // default in sqlite gets quoted extra
            'episodes' => [
                [
                    'episode_id' => 1,
                    'start_date' => '2019-01-01T01:23:45',
                    'organization' => 'Test organization',

                ],
            ],
        ];

        $request = $this->getRequest('POST', [], [], $newData, $this->routeOptions);
        $delegator = $this->getDelegator();

        $response1 = $controller->process($request, $delegator);

        $response2 = $controller->process($request, $delegator);

        $this->checkResponse($response2, EmptyResponse::class, 201);

        $testResultArray = $this->getAllEpisodesFromDb();

        $this->assertEquals($testResultArray[0]['gec_id_in_source'], '1');
        $this->assertEquals($testResultArray[0]['gec_id_organization'], '1');
    }*/

    public function testAddingEpisodeModelError()
    {
        $controller = $this->getController(true);
        $episodeModel = $this->prophesize(EpisodeOfCareModel::class);
        $episodeModel->getCol(Argument::type('string'))->willReturn([]);
        $episodeModel->setAutoSave(Argument::type('string'))->willReturn(null);
        $episodeModel->setOnSave(Argument::type('string'), Argument::type('array'))->willReturn(null);
        $episodeModel->applyEditSettings(true)->willReturn(null);
        $episodeModel->getSaveTables()->willReturn([]);
        $episodeModel->getJoinFields()->willReturn([]);
        $episodeModel->getKeys()->willReturn([]);
        $episodeModel->get(Argument::type('string'), 'type')->willReturn(null);
        $episodeModel->loadNew()->willReturn([]);
        $episodeModel->save(Argument::type('array'))->willThrow(new ModelException('model exception'));

        $controller->setEpisodeModel($episodeModel->reveal());

        $newData = [
            'gr2o_patient_nr' => '11',
            'organizations' => [
                'Test organization',
            ],
            'grs_last_name' => 'De Jong',
            'gr2o_reception_code' => 'OK', // default in sqlite gets quoted extra
            'episodes' => [
                [
                    'episode_id' => 1,
                    'start_date' => '2019-01-01T01:23:45',
                    'organization' => 'Test organization',

                ],
            ],
        ];

        $request = $this->getRequest('POST', [], [], $newData, $this->routeOptions);
        $delegator = $this->getDelegator();

        $response = $controller->process($request, $delegator);

        $this->checkResponse($response, EmptyResponse::class, 201);

        $testResultArray = $this->getAllEpisodesFromDb();
        $this->assertEmpty($testResultArray);
    }

    public function testAddingEpisodeModelValidationError()
    {
        $controller = $this->getController(true);
        $episodeModel = $this->prophesize(EpisodeOfCareModel::class);
        $episodeModel->getCol(Argument::type('string'))->willReturn([]);
        $episodeModel->setAutoSave(Argument::type('string'))->willReturn(null);
        $episodeModel->setOnSave(Argument::type('string'), Argument::type('array'))->willReturn(null);
        $episodeModel->applyEditSettings(true)->willReturn(null);
        $episodeModel->getSaveTables()->willReturn([]);
        $episodeModel->getJoinFields()->willReturn([]);
        $episodeModel->getKeys()->willReturn([]);
        $episodeModel->get(Argument::type('string'), 'type')->willReturn(null);
        $episodeModel->loadNew()->willReturn([]);
        $episodeModel->save(Argument::type('array'))->willThrow(new ModelValidationException('model validation exception', ['test1' => 'some error']));

        $controller->setEpisodeModel($episodeModel->reveal());

        $newData = [
            'gr2o_patient_nr' => '11',
            'organizations' => [
                'Test organization',
            ],
            'grs_last_name' => 'De Jong',
            'gr2o_reception_code' => 'OK', // default in sqlite gets quoted extra
            'episodes' => [
                [
                    'episode_id' => 1,
                    'start_date' => '2019-01-01T01:23:45',
                    'organization' => 'Test organization',

                ],
            ],
        ];

        $request = $this->getRequest('POST', [], [], $newData, $this->routeOptions);
        $delegator = $this->getDelegator();

        $response = $controller->process($request, $delegator);

        $this->checkResponse($response, EmptyResponse::class, 201);

        $testResultArray = $this->getAllEpisodesFromDb();
        $this->assertEmpty($testResultArray);
    }

    public function testAddingEpisodeUnknownError()
    {
        $controller = $this->getController(true);
        $episodeModel = $this->prophesize(EpisodeOfCareModel::class);
        $episodeModel->getCol(Argument::type('string'))->willReturn([]);
        $episodeModel->setAutoSave(Argument::type('string'))->willReturn(null);
        $episodeModel->setOnSave(Argument::type('string'), Argument::type('array'))->willReturn(null);
        $episodeModel->applyEditSettings(true)->willReturn(null);
        $episodeModel->getSaveTables()->willReturn([]);
        $episodeModel->getJoinFields()->willReturn([]);
        $episodeModel->getKeys()->willReturn([]);
        $episodeModel->get(Argument::type('string'), 'type')->willReturn(null);
        $episodeModel->loadNew()->willReturn([]);
        $episodeModel->save(Argument::type('array'))->willThrow(new \Exception('unknown exception'));

        $controller->setEpisodeModel($episodeModel->reveal());

        $newData = [
            'gr2o_patient_nr' => '11',
            'organizations' => [
                'Test organization',
            ],
            'grs_last_name' => 'De Jong',
            'gr2o_reception_code' => 'OK', // default in sqlite gets quoted extra
            'episodes' => [
                [
                    'episode_id' => 1,
                    'start_date' => '2019-01-01T01:23:45',
                    'organization' => 'Test organization',

                ],
            ],
        ];

        $request = $this->getRequest('POST', [], [], $newData, $this->routeOptions);
        $delegator = $this->getDelegator();

        $response = $controller->process($request, $delegator);

        $this->checkResponse($response, EmptyResponse::class, 201);

        $testResultArray = $this->getAllEpisodesFromDb();
        $this->assertEmpty($testResultArray);
    }





    private function getAllAppointmentsFromDb()
    {
        $sql = new Sql($this->db);
        $select = $sql->select();
        $select->from('gems__appointments');

        $statement = $sql->prepareStatementForSqlObject($select);
        $testResult = $statement->execute();

        return iterator_to_array($testResult);
    }

    private function getAllEpisodesFromDb()
    {
        $sql = new Sql($this->db);
        $select = $sql->select();
        $select->from('gems__episodes_of_care');

        $statement = $sql->prepareStatementForSqlObject($select);
        $testResult = $statement->execute();

        return iterator_to_array($testResult);
    }

    private function getController($realLoader=false, $realModel=true, $urlHelperRoutes=[])
    {
        \Gems_Model::setCurrentUserId(1);

        //$model = new \Pulse_Model_RespondentModel()
        if ($realModel) {
            $model = new \Gems_Model_RespondentModel();
            // Remove ssn hashing in tests
            $model->del('grs_ssn', \MUtil_Model_ModelAbstract::SAVE_TRANSFORMER, \MUtil_Model_ModelAbstract::LOAD_TRANSFORMER, \MUtil_Model_ModelAbstract::SAVE_WHEN_TEST);
        } else {
            $modelProphecy = $this->prophesize(\Gems_Model_RespondentModel::class);
            $modelProphecy->getCol(Argument::type('string'))->willReturn([]);
            $modelProphecy->setAutoSave(Argument::type('string'))->willReturn(null);
            $modelProphecy->setOnSave(Argument::type('string'), Argument::type('array'))->willReturn(null);
            $modelProphecy->applyEditSettings(true)->willReturn(null);
            $modelProphecy->getSaveTables()->willReturn([]);
            $modelProphecy->getJoinFields()->willReturn([]);
            $modelProphecy->getKeys()->willReturn([]);
            $modelProphecy->get(Argument::type('string'), 'type')->willReturn(null);
            $modelProphecy->loadNew()->willReturn([]);
            $modelProphecy->save(Argument::type('array'))->willReturn(['gr2o_patient_nr' => '1']);
            $model = $modelProphecy->reveal();
        }

        if ($realLoader) {
            $loader = new ProjectOverloader([
                //'Pulse',
                'Gems',
                'MUtil',
            ]);
        } else {
            $loaderProphecy = $this->prophesize(ProjectOverloader::class);

            $loader = $loaderProphecy->reveal();
        }
        $loader->legacyClasses = true;
        $loader->legacyPrefix = 'Legacy';



        $currentUserProphecy = $this->prophesize(\Gems_User_User::class);
        $currentUserProphecy->getUserId()->willReturn(1);
        $currentUserProphecy->getGroup()->willReturn(null);
        $currentUserProphecy->getRespondentOrganizations()->willReturn(null);
        $currentUserProphecy->getCurrentOrganizationId()->willReturn(1);
        $currentUserProphecy->getAllowedOrganizations()->willReturn(null);
        $currentUserProphecy->hasPrivilege(Argument::type('string'))->willReturn(null);


        $gemsAgendaProphecy = $this->prophesize(\Gems_Agenda::class);
        $gemsAgendaProphecy->matchLocation('Home', '1', false)->willReturn(['glo_id_location' => 99]);
        $gemsAgendaProphecy->getStatusCodesInactive()->willReturn(['AB' => 'Aborted appointment', 'CA' => 'Cancelled appointment']);
        $gemsAgendaProphecy->getStatusKeysInactive()->willReturn(['AB', 'CA']);
        $gemsAgendaProphecy->getTypeCodes()->willReturn([
            'A' => 'Ambulatory',
            'E' => 'Emergency',
            'F' => 'Field',
            'H' => 'Home',
            'I' => 'Inpatient',
            'S' => 'Short stay',
            'V' => 'Virtual',
        ]);
        $gemsAgendaProphecy->getStatusCodes()->willReturn(
            [
                'AB' => 'Aborted appointment',
                'AC' => 'Active appointment',
                'CA' => 'Cancelled appointment',
                'CO' => 'Completed appointment',
            ]
        );
        $gemsAgendaProphecy->getHealthcareStaff()->willReturn([]);
        $gemsAgendaProphecy->getActivities(1)->willReturn([]);
        $gemsAgendaProphecy->getProcedures(1)->willReturn([]);
        $gemsAgendaProphecy->getLocations(1)->willReturn([]);

        $appointment = $this->prophesize(\Gems_Agenda_Appointment::class);


        $gemsAgendaProphecy->getAppointment(Argument::type('array'))->willReturn($appointment->reveal());



        $organizationProphecy = $this->prophesize(\Gems_User_Organization::class);
        $legacyLoaderProphecy = $this->prophesize(\Gems_Loader::class);
        $legacyLoaderProphecy->getOrganization(Argument::cetera())->willReturn($organizationProphecy->reveal());
        $legacyLoaderProphecy->getAgenda()->willReturn($gemsAgendaProphecy->reveal());

        $utilProphecy = $this->prophesize(\Gems_Util::class);

        //$dbLookup = $this->prophesize(\Pulse_Util_DbLookup::class);
        $dbLookup = $this->prophesize(\Gems_Util_DbLookup::class);
        $dbLookup->getOrganizations()->willReturn([]);
        $dbLookup->getUserConsents()->willReturn(null);
        $dbLookup->getStaff()->willReturn(null);

        $localized = $this->prophesize(\Gems_Util_Localized::class);
        //$translated = $this->prophesize(\Pulse_Util_Translated::class);
        $translated = $this->prophesize(\Gems_Util_Translated::class);
        $translated->getEmptyDropdownArray()->willReturn(['' => '-']);
        $translated->getGenderHello()->willReturn(
            ['M' => 'Mr.', 'F' => 'Mrs.', 'U' => 'Mr./Mrs.']
        );
        $translated->getYesNo()->willReturn([1 => 'Yes', 0 => 'No']);
        $translated->getGenders()->willReturn([
            'M' => 'Male',
            'F' => 'Female',
            'U' => 'Unknown',
        ]);


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

        if ($realModel) {
            $loader->applyToLegacyTarget($model);
        }

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
        $organizationRepositoryProphecy->getOrganizationTranslations(['Test organization Home'])->willReturn(['1' => 'Test organization Home']);
        $organizationRepositoryProphecy->getLocationFromOrganizationName('Test organization Home')->willReturn('Home');
        $organizationRepositoryProphecy->getLocationFromOrganizationName(Argument::type('string'))->willReturn(null);
        $organizationRepositoryProphecy->getOrganizationId('Test organization')->willReturn(1);
        $organizationRepositoryProphecy->getOrganizationId(Argument::type('string'))->willReturn(null);

        //$respondentRepositoryProphecy = $this->prophesize(RespondentRepository::class);
        $respondentRepository = new RespondentRepository($this->db);



        $modelClass = \Gems_Model::class;
        if (class_exists(\Pulse_Model::class, true)) {
            $modelClass = \Pulse_Model::class;
        }

        $gemsModelProphecy = $this->prophesize($modelClass);
        $gemsModelProphecy->createGemsUserId(Argument::cetera())->willReturnArgument(0);
        $gemsModelProphecy->checkAnaesthesiaLink(Argument::cetera())->willReturn(null);

        $legacyLoaderProphecy = $this->prophesize(\Gems_Loader::class);
        $emmaImportLoggerProphecy = $this->prophesize(LoggerInterface::class);

        $emmaRespondentErrorLoggerProphecy = $this->prophesize(LoggerInterface::class);

        $accesslogRepository = $this->prophesize(AccesslogRepository::class);

        $controller =  new RespondentBulkRestController($accesslogRepository->reveal(), $loader, $urlHelperProphecy->reveal(), $this->db,
            $agendaDiagnosisRepositoryProphecy->reveal(),
            $appointmentRepositoryProphecy->reveal(),
            $organizationRepositoryProphecy->reveal(),
            $respondentRepository,
            $gemsAgendaProphecy->reveal(),
            $gemsModelProphecy->reveal(),
            $legacyLoaderProphecy->reveal(),
            $emmaImportLoggerProphecy->reveal(),
            $emmaRespondentErrorLoggerProphecy->reveal(),
            $this->db1
        );

        $controller->setModelName($model);

        return $controller;
    }
}