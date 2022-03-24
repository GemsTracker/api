<?php

declare(strict_types=1);


namespace PulseTest\Rest\Api\Emma\Fhir\Action;


use Gems\Event\EventDispatcher;
use Gems\Rest\Model\ModelException;
use Gems\Rest\Model\ModelValidationException;
use Gems\Rest\Repository\AccesslogRepository;
use GemsTest\Rest\Test\PhinxMigrateDatabase;
use Laminas\Diactoros\Response\EmptyResponse;
use Laminas\Diactoros\Response\JsonResponse;
use Mezzio\Helper\UrlHelper;
use Phinx\Console\PhinxApplication;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Pulse\Api\Emma\Fhir\Action\PatientResourceAction;
use Pulse\Api\Emma\Fhir\ExistingEpdPatientRepository;
use Pulse\Api\Emma\Fhir\Model\RespondentModel;
use Pulse\Api\Emma\Fhir\Repository\CurrentUserRepository;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\NullOutput;
use Zalt\Loader\ProjectOverloader;

class EmmaPatientResourceActionTest extends TestCase
{
    use PhinxMigrateDatabase;

    public function testIncorrectContentType()
    {
        $data = [];

        $request = $this->prophesize(ServerRequestInterface::class);

        $currentUserRepositoryProphecy = $this->prophesize(CurrentUserRepository::class);
        $eventDispatcherProphecy = $this->prophesize(EventDispatcher::class);
        $existingEpdPatientRepositoryProphecy = $this->prophesize(ExistingEpdPatientRepository::class);
        $accessLogRepositoryProphecy = $this->prophesize(AccesslogRepository::class);
        $projectOverloaderProphecy = $this->prophesize(ProjectOverloader::class);
        $urlHelperProphecy  = $this->prophesize(UrlHelper::class);
        $legacyDb = $this->prophesize(\Zend_Db_Adapter_Abstract::class);
        $action = new PatientResourceAction(
            $currentUserRepositoryProphecy->reveal(),
            $eventDispatcherProphecy->reveal(),
            $existingEpdPatientRepositoryProphecy->reveal(),
            $accessLogRepositoryProphecy->reveal(),
            $projectOverloaderProphecy->reveal(),
            $urlHelperProphecy->reveal(),
            $legacyDb->reveal()
        );

        $result = $action->put($request->reveal());

        $this->assertInstanceOf(EmptyResponse::class, $result);
        $this->assertEquals(415, $result->getStatusCode());
    }

    public function testEmptyData()
    {
        $data = [];

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getHeaderLine('content-type')->willReturn('application/json');

        $streamBodyProphecy = $this->prophesize(StreamInterface::class);
        $streamBodyProphecy->getContents()->willReturn(json_encode($data));
        $request->getBody()->willReturn($streamBodyProphecy->reveal());

        $currentUserRepositoryProphecy = $this->prophesize(CurrentUserRepository::class);
        $eventDispatcherProphecy = $this->prophesize(EventDispatcher::class);
        $existingEpdPatientRepositoryProphecy = $this->prophesize(ExistingEpdPatientRepository::class);
        $accessLogRepositoryProphecy = $this->prophesize(AccesslogRepository::class);
        $projectOverloaderProphecy = $this->prophesize(ProjectOverloader::class);
        $urlHelperProphecy  = $this->prophesize(UrlHelper::class);
        $legacyDb = $this->prophesize(\Zend_Db_Adapter_Abstract::class);
        $action = new PatientResourceAction(
            $currentUserRepositoryProphecy->reveal(),
            $eventDispatcherProphecy->reveal(),
            $existingEpdPatientRepositoryProphecy->reveal(),
            $accessLogRepositoryProphecy->reveal(),
            $projectOverloaderProphecy->reveal(),
            $urlHelperProphecy->reveal(),
            $legacyDb->reveal()
        );

        $result = $action->put($request->reveal());

        $this->assertInstanceOf(EmptyResponse::class, $result);
        $this->assertEquals(400, $result->getStatusCode());
    }

    public function testNewPatient()
    {
        $data = [
            'identifier' => [
                [
                    'use' => 'official',
                    'system' => 'http://fhir.nl/fhir/NamingSystem/bsn',
                    'value' => '999911120',
                ],[
                    'system' => 'http://fhir.timeff.com/identifier/patientnummer',
                    'value' => '123',
                ],
            ],
        ];

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getHeaderLine('content-type')->willReturn('application/json');
        $request->getAttribute('user_id')->willReturn(1);

        $streamBodyProphecy = $this->prophesize(StreamInterface::class);
        $streamBodyProphecy->getContents()->willReturn(json_encode($data));
        $request->getBody()->willReturn($streamBodyProphecy->reveal());

        $currentUserRepositoryProphecy = $this->prophesize(CurrentUserRepository::class);
        $eventDispatcherProphecy = $this->prophesize(EventDispatcher::class);
        $existingEpdPatientRepositoryProphecy = $this->prophesize(ExistingEpdPatientRepository::class);
        $accessLogRepositoryProphecy = $this->prophesize(AccesslogRepository::class);
        $projectOverloaderProphecy = $this->prophesize(ProjectOverloader::class);
        $urlHelperProphecy  = $this->prophesize(UrlHelper::class);
        $legacyDb = $this->prophesize(\Zend_Db_Adapter_Abstract::class);
        $action = new PatientResourceAction(
            $currentUserRepositoryProphecy->reveal(),
            $eventDispatcherProphecy->reveal(),
            $existingEpdPatientRepositoryProphecy->reveal(),
            $accessLogRepositoryProphecy->reveal(),
            $projectOverloaderProphecy->reveal(),
            $urlHelperProphecy->reveal(),
            $legacyDb->reveal()
        );

        $model = $this->prophesize(RespondentModel::class);
        $model->getCol('apiName')->willReturn([]);
        $model->getCol('model')->willReturn([]);
        $model->getColNames('allow_api_load')->willReturn([]);
        $model->getColNames('allow_api_save')->willReturn([]);
        $model->save(Argument::any())->willReturn([]);
        $model->getOldValues()->willReturn([]);
        $model->addTransformer(Argument::any())->willReturn(null);
        $model->getName()->willReturn('respondentModel');

        $action->setModelName($model->reveal());

        $result = $action->put($request->reveal());

        $this->assertInstanceOf(EmptyResponse::class, $result);
        $this->assertEquals(201, $result->getStatusCode());
    }

    public function testExistingPatient()
    {
        $data = [
            'identifier' => [
                [
                    'use' => 'official',
                    'system' => 'http://fhir.nl/fhir/NamingSystem/bsn',
                    'value' => '999911120',
                ],[
                    'system' => 'http://fhir.timeff.com/identifier/patientnummer',
                    'value' => '123',
                ],
            ],
        ];

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getHeaderLine('content-type')->willReturn('application/json');
        $request->getAttribute('user_id')->willReturn(1);

        $streamBodyProphecy = $this->prophesize(StreamInterface::class);
        $streamBodyProphecy->getContents()->willReturn(json_encode($data));
        $request->getBody()->willReturn($streamBodyProphecy->reveal());

        $existingEpdPatientRepositoryProphecy = $this->prophesize(ExistingEpdPatientRepository::class);
        $existingEpdPatientRepositoryProphecy->getExistingPatients('999911120', '123')->willReturn([
            [
                'gr2o_patient_nr' => '123',
                'gr2o_id_user' => 1,
                'grs_id_user' => 1,
                'gr2o_id_organization' => 1,
                'grs_ssn' => '999911120'
            ]
        ]);

        $currentUserRepositoryProphecy = $this->prophesize(CurrentUserRepository::class);
        $eventDispatcherProphecy = $this->prophesize(EventDispatcher::class);
        $accessLogRepositoryProphecy = $this->prophesize(AccesslogRepository::class);
        $projectOverloaderProphecy = $this->prophesize(ProjectOverloader::class);
        $urlHelperProphecy  = $this->prophesize(UrlHelper::class);
        $legacyDb = $this->prophesize(\Zend_Db_Adapter_Abstract::class);
        $action = new PatientResourceAction(
            $currentUserRepositoryProphecy->reveal(),
            $eventDispatcherProphecy->reveal(),
            $existingEpdPatientRepositoryProphecy->reveal(),
            $accessLogRepositoryProphecy->reveal(),
            $projectOverloaderProphecy->reveal(),
            $urlHelperProphecy->reveal(),
            $legacyDb->reveal()
        );

        $model = $this->prophesize(RespondentModel::class);
        $model->getCol('apiName')->willReturn([]);
        $model->getCol('model')->willReturn([]);
        $model->getColNames('allow_api_load')->willReturn([]);
        $model->getColNames('allow_api_save')->willReturn([]);
        $model->save(Argument::any())->willReturn([]);
        $model->getOldValues()->willReturn([]);
        $model->addTransformer(Argument::any())->willReturn(null);
        $model->getName()->willReturn('respondentModel');

        $action->setModelName($model->reveal());

        $result = $action->put($request->reveal());

        $this->assertInstanceOf(EmptyResponse::class, $result);
        $this->assertEquals(200, $result->getStatusCode());
    }

    public function testValidationError()
    {
        $data = [
            'identifier' => [
                [
                    'use' => 'official',
                    'system' => 'http://fhir.nl/fhir/NamingSystem/bsn',
                    'value' => '999911120',
                ],[
                    'system' => 'http://fhir.timeff.com/fhir/NamingSystem/emma',
                    'value' => '123',
                ],
            ],
        ];

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getHeaderLine('content-type')->willReturn('application/json');

        $streamBodyProphecy = $this->prophesize(StreamInterface::class);
        $streamBodyProphecy->getContents()->willReturn(json_encode($data));
        $request->getBody()->willReturn($streamBodyProphecy->reveal());

        $existingEpdPatientRepositoryProphecy = $this->prophesize(ExistingEpdPatientRepository::class);
        $existingEpdPatientRepositoryProphecy->getExistingPatients('999911120', '123')->willReturn([
            [
                'gr2o_patient_nr' => '123',
                'gr2o_id_user' => 1,
                'grs_id_user' => 1,
                'gr2o_id_organization' => 1,
                'grs_ssn' => '999911120'
            ]
        ]);

        $currentUserRepositoryProphecy = $this->prophesize(CurrentUserRepository::class);
        $eventDispatcherProphecy = $this->prophesize(EventDispatcher::class);
        $accessLogRepositoryProphecy = $this->prophesize(AccesslogRepository::class);
        $projectOverloaderProphecy = $this->prophesize(ProjectOverloader::class);
        $urlHelperProphecy  = $this->prophesize(UrlHelper::class);
        $legacyDb = $this->prophesize(\Zend_Db_Adapter_Abstract::class);
        $action = new PatientResourceAction(
            $currentUserRepositoryProphecy->reveal(),
            $eventDispatcherProphecy->reveal(),
            $existingEpdPatientRepositoryProphecy->reveal(),
            $accessLogRepositoryProphecy->reveal(),
            $projectOverloaderProphecy->reveal(),
            $urlHelperProphecy->reveal(),
            $legacyDb->reveal()
        );

        $model = $this->prophesize(RespondentModel::class);
        $model->getCol('apiName')->willReturn([]);
        $model->getCol('model')->willReturn([]);
        $model->getColNames('allow_api_load')->willReturn([]);
        $model->getColNames('allow_api_save')->willReturn([]);
        $model->save(Argument::any())->willThrow(new ModelValidationException('validation errros!', ['somefield' => 'is missing!']));

        $action->setModelName($model->reveal());

        $result = $action->put($request->reveal());

        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(400, $result->getStatusCode());
    }

    public function testModelError()
    {
        $data = [
            'identifier' => [
                [
                    'use' => 'official',
                    'system' => 'http://fhir.nl/fhir/NamingSystem/bsn',
                    'value' => '999911120',
                ],[
                    'system' => 'http://fhir.timeff.com/fhir/NamingSystem/emma',
                    'value' => '123',
                ],
            ],
        ];

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getHeaderLine('content-type')->willReturn('application/json');

        $streamBodyProphecy = $this->prophesize(StreamInterface::class);
        $streamBodyProphecy->getContents()->willReturn(json_encode($data));
        $request->getBody()->willReturn($streamBodyProphecy->reveal());

        $existingEpdPatientRepositoryProphecy = $this->prophesize(ExistingEpdPatientRepository::class);
        $existingEpdPatientRepositoryProphecy->getExistingPatients('999911120', '123')->willReturn([
            [
                'gr2o_patient_nr' => '123',
                'gr2o_id_user' => 1,
                'grs_id_user' => 1,
                'gr2o_id_organization' => 1,
                'grs_ssn' => '999911120'
            ]
        ]);

        $currentUserRepositoryProphecy = $this->prophesize(CurrentUserRepository::class);
        $eventDispatcherProphecy = $this->prophesize(EventDispatcher::class);
        $accessLogRepositoryProphecy = $this->prophesize(AccesslogRepository::class);
        $projectOverloaderProphecy = $this->prophesize(ProjectOverloader::class);
        $urlHelperProphecy  = $this->prophesize(UrlHelper::class);
        $legacyDb = $this->prophesize(\Zend_Db_Adapter_Abstract::class);
        $action = new PatientResourceAction(
            $currentUserRepositoryProphecy->reveal(),
            $eventDispatcherProphecy->reveal(),
            $existingEpdPatientRepositoryProphecy->reveal(),
            $accessLogRepositoryProphecy->reveal(),
            $projectOverloaderProphecy->reveal(),
            $urlHelperProphecy->reveal(),
            $legacyDb->reveal()
        );

        $model = $this->prophesize(RespondentModel::class);
        $model->getCol('apiName')->willReturn([]);
        $model->getCol('model')->willReturn([]);
        $model->getColNames('allow_api_load')->willReturn([]);
        $model->getColNames('allow_api_save')->willReturn([]);
        $model->save(Argument::any())->willThrow(new ModelException('Some model error!!'));

        $action->setModelName($model->reveal());

        $result = $action->put($request->reveal());

        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(400, $result->getStatusCode());
    }

    public function testOtherError()
    {
        $data = [
            'identifier' => [
                [
                    'use' => 'official',
                    'system' => 'http://fhir.nl/fhir/NamingSystem/bsn',
                    'value' => '999911120',
                ],[
                    'system' => 'http://fhir.timeff.com/fhir/NamingSystem/emma',
                    'value' => '123',
                ],
            ],
        ];

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getHeaderLine('content-type')->willReturn('application/json');

        $streamBodyProphecy = $this->prophesize(StreamInterface::class);
        $streamBodyProphecy->getContents()->willReturn(json_encode($data));
        $request->getBody()->willReturn($streamBodyProphecy->reveal());

        $existingEpdPatientRepositoryProphecy = $this->prophesize(ExistingEpdPatientRepository::class);
        $existingEpdPatientRepositoryProphecy->getExistingPatients('999911120', '123')->willReturn([
            [
                'gr2o_patient_nr' => '123',
                'gr2o_id_user' => 1,
                'grs_id_user' => 1,
                'gr2o_id_organization' => 1,
                'grs_ssn' => '999911120'
            ]
        ]);

        $currentUserRepositoryProphecy = $this->prophesize(CurrentUserRepository::class);
        $eventDispatcherProphecy = $this->prophesize(EventDispatcher::class);
        $accessLogRepositoryProphecy = $this->prophesize(AccesslogRepository::class);
        $projectOverloaderProphecy = $this->prophesize(ProjectOverloader::class);
        $urlHelperProphecy  = $this->prophesize(UrlHelper::class);
        $legacyDb = $this->prophesize(\Zend_Db_Adapter_Abstract::class);
        $action = new PatientResourceAction(
            $currentUserRepositoryProphecy->reveal(),
            $eventDispatcherProphecy->reveal(),
            $existingEpdPatientRepositoryProphecy->reveal(),
            $accessLogRepositoryProphecy->reveal(),
            $projectOverloaderProphecy->reveal(),
            $urlHelperProphecy->reveal(),
            $legacyDb->reveal()
        );

        $model = $this->prophesize(RespondentModel::class);
        $model->getCol('apiName')->willReturn([]);
        $model->getCol('model')->willReturn([]);
        $model->getColNames('allow_api_load')->willReturn([]);
        $model->getColNames('allow_api_save')->willReturn([]);
        $model->save(Argument::any())->willThrow(new \Exception('Some random error!!'));

        $action->setModelName($model->reveal());

        $result = $action->put($request->reveal());

        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(400, $result->getStatusCode());
    }
}
