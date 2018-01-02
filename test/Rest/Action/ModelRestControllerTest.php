<?php


namespace GemsTest\Rest\Action;


use Interop\Container\ContainerInterface;
use Interop\Http\ServerMiddleware\DelegateInterface;
use PHPUnit\DbUnit\DataSet\YamlDataSet;
use Prophecy\Argument;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use GemsTest\Rest\Test\ZendDbTestCase;
use Symfony\Component\Yaml\Yaml;
use Zalt\Loader\ProjectOverloader;
use Zend\Diactoros\Response\EmptyResponse;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\Uri;
use Zend\Expressive\Helper\UrlHelper;
use Zend\Expressive\Router\RouteResult;
use Zend\ServiceManager\Config;

class ModelRestControllerTest extends ZendDbTestCase
{
    protected $loadZendDb1 = true;

    /**
     * Returns the test dataset.
     *
     * @return IDataSet
     */
    protected function getDataSet()
    {
        $file = str_replace('.php', '.yml', __FILE__);
        return new YamlDataSet($file);
    }

    protected function getDataArray($tableName=false)
    {
        $file = str_replace('.php', '.yml', __FILE__);

        $result = Yaml::parse(file_get_contents($file));

        if ($tableName) {
            return $result[$tableName];
        }

        return $result;
    }

    public function testGetListEmptyModel()
    {
        $controller = $this->getArrayModelRestController();

        $request = $this->getRequest('GET', ['id' => null]);

        $response = $controller->process(
            $request,
            $this->prophesize(DelegateInterface::class)->reveal()
        );

        $this->checkResponse($response,EmptyResponse::class, 204);
    }

    public function testGet()
    {
        $controller = $this->getTestMessageModelRestController();
        $request = $this->getRequest('GET', ['id' => 1]);

        $response = $controller->process(
            $request,
            $this->prophesize(DelegateInterface::class)->reveal()
        );

        $this->checkResponse($response,JsonResponse::class, 200);

        $databaseData = $this->getDataArray('test_messages');
        $expectedData = reset($databaseData);

        $this->assertEquals($expectedData, $response->getPayload(), 'parsed body not the same as expected data');

        $request = $this->getRequest('GET', ['id' => 10]);

        $response = $controller->process(
            $request,
            $this->prophesize(DelegateInterface::class)->reveal()
        );

        $this->checkResponse($response,EmptyResponse::class, 404);
    }

    public function testGetNoPrimaryKey()
    {
        $controller = $this->getArrayModelRestController();
        $controller->setData([
            [
                'id' => 1,
                'message' => 'hello',
                'created_on' => '2017-09-12 00:00:00'
            ]
        ]);
        $request = $this->getRequest('GET', ['id' => 1]);

        $response = $controller->process(
            $request,
            $this->prophesize(DelegateInterface::class)->reveal()
        );

        $this->checkResponse($response,EmptyResponse::class, 404);
    }


    public function testGetList()
    {
        $controller = $this->getTestMessageModelRestController();
        $request = $this->getRequest('GET', ['id' => null]);

        $response = $controller->process(
            $request,
            $this->prophesize(DelegateInterface::class)->reveal()
        );

        $this->checkResponse($response,JsonResponse::class, 200);

        $expectedResult = $this->getDataArray('test_messages');

        $this->assertEquals($expectedResult, $response->getPayload(), 'parsed body not the same as expected data');
    }

    public function testGetListCountWhenOne()
    {
        $controller = $this->getArrayModelRestController();
        $controller->setData([
            [
                'id' => 1,
                'message' => 'hello',
                'created_on' => '2017-09-12 00:00:00'
            ]
        ]);
        $request = $this->getRequest('GET', ['id' => null]);

        $response = $controller->process(
            $request,
            $this->prophesize(DelegateInterface::class)->reveal()
        );

        $totalCount = $response->getHeader('X-total-count');

        $this->assertTrue(is_array($totalCount));

        $count = reset($totalCount);

        $this->assertEquals(1, $count);
    }

    public function testGetListCountWhenTwo()
    {
        $controller = $this->getArrayModelRestController();
        $controller->setData([
            [
                'id' => 1,
                'message' => 'hello',
                'created_on' => '2017-09-12 00:00:00'
            ],
            [
                'id' => 2,
                'message' => 'hello2',
                'created_on' => '2017-09-12 00:00:00'
            ]
        ]);
        $request = $this->getRequest('GET', ['id' => null]);

        $response = $controller->process(
            $request,
            $this->prophesize(DelegateInterface::class)->reveal()
        );

        $totalCount = $response->getHeader('X-total-count');

        $this->assertTrue(is_array($totalCount));

        $count = reset($totalCount);

        $this->assertEquals(2, $count);
    }

    public function testGetListWithParams()
    {
        $controller = $this->getTestMessageModelRestController();

        $request = $this->getRequest('GET',
            [
                'id' => null,
            ],
            [
                'page' => 1,
                'by' => 1,
            ]
        );

        $response = $controller->process(
            $request,
            $this->prophesize(DelegateInterface::class)->reveal()
        );

        $this->checkResponse($response, JsonResponse::class, 200);
    }

    public function testGetListAlias()
    {
        $controller = $this->getTestMessageModelRestController();
        $controller->alias = true;

        $request = $this->getRequest('GET',
            [
                'id' => null,
            ],
            [
                'page' => 1,
                'alias_by' => 1,
            ]
        );

        $response = $controller->process(
            $request,
            $this->prophesize(DelegateInterface::class)->reveal()
        );

        $this->checkResponse($response,JsonResponse::class, 200);
    }

    public function testGetListOrder()
    {
        $controller = $this->getTestMessageModelRestController();
        $controller->alias = true;

        $request = $this->getRequest('GET',
            [
                'id' => null,
            ],
            [
                'page' => 1,
                'alias_by' => 1,
                'order' => 'created DESC, message ASC, alias_by, -id',
            ]
        );

        $response = $controller->process(
            $request,
            $this->prophesize(DelegateInterface::class)->reveal()
        );

        $this->checkResponse($response,JsonResponse::class, 200);

        // TODO: Add actual check for the sorting results
    }

    public function testGetListPagination()
    {
        $controller = $this->getTestMessageModelRestController();

        $request = $this->getRequest('GET',
            [
                'id' => null,
            ],
            [
                'page' => 1,
                'per_page' => 1,
            ]
        );

        $response = $controller->process(
            $request,
            $this->prophesize(DelegateInterface::class)->reveal()
        );

        $this->checkResponse($response,JsonResponse::class, 200);

        $request = $this->getRequest('GET',
            [
                'id' => null,
            ],
            [
                'page' => 2,
                'per_page' => 1,
            ]
        );

        $response = $controller->process(
            $request,
            $this->prophesize(DelegateInterface::class)->reveal()
        );

        $this->checkResponse($response,JsonResponse::class, 200);
    }

    public function testPost()
    {
        $controller = $this->getTestMessageModelRestController(true);

        // Test empty Item
        $newData = [];
        $request = $this->getRequest('POST', [], [], $newData);
        $response = $controller->process(
            $request,
            $this->prophesize(DelegateInterface::class)->reveal()
        );
        $this->checkResponse($response, EmptyResponse::class, 400);

        // Test correct item
        $newData = [
            'message' => 'a new entry!',
            'by' => 3,
            'changed' => '2017-09-12 00:00:00',
            'changed_by' => 3,
            'created' => '2017-09-11 00:00:00',
            'created_by' => 3,
        ];
        $request = $this->getRequest('POST', [], [], $newData);
        $response = $controller->process(
            $request,
            $this->prophesize(DelegateInterface::class)->reveal()
        );

        $this->checkResponse($response, EmptyResponse::class, 201);

        $expectedData = ['id' => 3] + $newData + ['optional' => null];

        $request = $this->getRequest('GET', ['id' => 3]);

        $response = $controller->process(
            $request,
            $this->prophesize(DelegateInterface::class)->reveal()
        );
        $this->assertEquals($expectedData, $response->getPayload(), 'parsed body not the same as expected data');


        // Test the exception thrown in a model delete. Currently no models throw an exception
        $mockedModelController = $this->getArrayModelRestController();
        $mockedModelProphecy = $this->prophesize(\MUtil_Model_TableModel::class);
        $mockedModelProphecy->getKeys()->willReturn(['id' => 'id']);
        $mockedModelProphecy->getCol(Argument::type('string'))->willReturn([]);
        $exception = new \Exception('Saving of the item has failed');
        $mockedModelProphecy->save(Argument::type('array'))->willThrow($exception);

        $mockedModel = $mockedModelProphecy->reveal();
        $mockedModelController->setModel($mockedModel);

        $request = $this->getRequest('POST', [], [], $newData);
        $response = $mockedModelController->process(
            $request,
            $this->prophesize(DelegateInterface::class)->reveal()
        );

        $this->checkResponse($response, EmptyResponse::class, 400);

        // Test incorrect item, missing field
        $newData = [
            'by' => 4,
            'changed' => '2017-09-12 00:00:00',
            'changed_by' => 4,
            'created' => '2017-09-11 00:00:00',
            'created_by' => 4,
        ];
        $request = $this->getRequest('POST', [], [], $newData);
        $response = $controller->process(
            $request,
            $this->prophesize(DelegateInterface::class)->reveal()
        );
        $this->checkResponse($response, JsonResponse::class, 400);

        // Test correct item with an existing route
        $controller = $this->getTestMessageModelRestController(true, ['.get' => 'test123']);
        $newData = [
            'by' => 4,
            'message' => 'a new entry!',
            'changed' => '2017-09-12 00:00:00',
            'changed_by' => 4,
            'created' => '2017-09-11 00:00:00',
            'created_by' => 4,
        ];
        $request = $this->getRequest('POST', [], [], $newData);
        $response = $controller->process(
            $request,
            $this->prophesize(DelegateInterface::class)->reveal()
        );
        $this->checkResponse($response, EmptyResponse::class, 201);

        // Test if location is actually set in header
        $this->assertNotNull($response->getHeaderLine('Location'), 'Header Location not set');
        $this->assertSame('test123', $response->getHeaderLine('Location'), 'Header location not as expected');
    }

    public function testPatch()
    {
        $controller = $this->getTestMessageModelRestController(true);
        $databaseData = $this->getDataArray('test_messages');
        $firstEntry = reset($databaseData);
        $firstEntry['message'] = 'The test has been updated';

        // Test if not supplying an ID results in an error
        $request = $this->getRequest('PATCH', ['id' => null], [], $firstEntry);
        $response = $controller->process(
            $request,
            $this->prophesize(DelegateInterface::class)->reveal()
        );
        $this->checkResponse($response, EmptyResponse::class, 404);

        $request = $this->getRequest('PATCH', ['id' => $firstEntry['id']], [], $firstEntry);
        $response = $controller->process(
            $request,
            $this->prophesize(DelegateInterface::class)->reveal()
        );
        $this->checkResponse($response, EmptyResponse::class, 201);


        $request = $this->getRequest('GET', ['id' => 1]);
        $response = $controller->process(
            $request,
            $this->prophesize(DelegateInterface::class)->reveal()
        );

        $this->assertEquals($firstEntry, $response->getPayload(), 'parsed body not the same as expected data');
    }

    public function testStructure()
    {
        $controller = $this->getTestMessageModelRestController();

        $request = $this->getRequest(
            'GET',
            ['id' => 1],
            [],
            [],
            '/structure'
        );

        $response = $controller->process(
            $request,
            $this->prophesize(DelegateInterface::class)->reveal()
        );

        $expectedData = [
            'id' => [
                'required' => true,
                'type' => 'numeric',
                'name' => 'id',
            ],
            'message' => [
                'required' => true,
                'maxlength' => 255,
                'type' => 'string',
                'name' => 'message',
            ],
            'by' => [
                'required' => true,
                'type' => 'numeric',
                'name' => 'by',
            ],
            'optional' => [
                'required' => false,
                'maxlength' => 255,
                'type' => 'string',
                'name' => 'optional',
            ],
            'changed' => [
                'required' => false,
                'type' => 'string',
                'name' => 'changed',
            ],
            'changed_by' => [
                'required' => true,
                'type' => 'numeric',
                'name' => 'changed_by',
            ],
            'created' => [
                'required' => true,
                'type' => 'string',
                'name' => 'created',
            ],
            'created_by' => [
                'required' => true,
                'type' => 'numeric',
                'name' => 'created_by',
            ],
            'testDateTime' => [
                'type' => 'datetime',
                'name' => 'testDateTime',
            ],
            'testDate' => [
                'type' => 'date',
                'name' => 'testDate',
            ],
            'testTime' => [
                'type' => 'time',
                'name' => 'testTime',
            ],
            'testChildModel' => [
                'type' => 'child_model',
                'name' => 'testChildModel',
            ],
            'testNoValue' => [
                'type' => 'no_value',
                'name' => 'testNoValue',
            ],
            'testNotCorrectType' => [
                'type' => 'no_value',
                'name' => 'testNotCorrectType',
            ]
        ];

        $this->checkResponse($response,JsonResponse::class, 200);

        $this->assertEquals($expectedData, $response->getPayload(), 'parsed body not the same as expected data');
    }

    public function testUnknownMethod()
    {
        $controller = $this->getTestMessageModelRestController();

        $request = $this->getRequest(
            'UNKOWN',
            ['id' => 1]
        );

        $response = $controller->process(
            $request,
            $this->prophesize(DelegateInterface::class)->reveal()
        );

        $this->checkResponse($response,EmptyResponse::class, 501);
    }

    public function testDeleteItem()
    {
        $controller = $this->getTestMessageModelRestController();

        $request = $this->getRequest(
            'DELETE',
            ['id' => null]
        );
        $response = $controller->process(
            $request,
            $this->prophesize(DelegateInterface::class)->reveal()
        );

        $this->checkResponse($response,EmptyResponse::class, 404);

        // Test when no items are deleted
        $request = $this->getRequest(
            'DELETE',
            ['id' => 10]
        );
        $response = $controller->process(
            $request,
            $this->prophesize(DelegateInterface::class)->reveal()
        );
        $this->checkResponse($response,EmptyResponse::class, 400);

        // Test the exception thrown in a model delete. Currently no models throw an exception
        $mockedModelController = $this->getArrayModelRestController();
        $mockedModelProphecy = $this->prophesize(\MUtil_Model_TableModel::class);
        $mockedModelProphecy->getKeys()->willReturn(['id' => 'id']);
        $exception = new \Exception('Deleting of the item has failed');
        $mockedModelProphecy->delete(Argument::type('array'))->willThrow($exception);

        $mockedModel = $mockedModelProphecy->reveal();
        $mockedModelController->setModel($mockedModel);

        $request = $this->getRequest(
            'DELETE',
            ['id' => 1]
        );
        $response = $mockedModelController->process(
            $request,
            $this->prophesize(DelegateInterface::class)->reveal()
        );
        $this->checkResponse($response,EmptyResponse::class, 400);

        // Test the correct deletion of an item
        $request = $this->getRequest(
            'DELETE',
            ['id' => 1]
        );
        $response = $controller->process(
            $request,
            $this->prophesize(DelegateInterface::class)->reveal()
        );
        $this->checkResponse($response,EmptyResponse::class, 204);

        // Test if item is actually removed from the database
        $request = $this->getRequest('GET', ['id' => 1]);
        $response = $controller->process(
            $request,
            $this->prophesize(DelegateInterface::class)->reveal()
        );
        $this->checkResponse($response,EmptyResponse::class, 404);
    }

    public function testApplyApiSettingsOnProcess()
    {
        $controller = $this->getTestMessageModelRestController();

        $model = new TestApiSettingsModel('test_messages', 'test');
        $controller->setModel($model);
        $request = $this->getRequest('GET', ['id' => 1]);

        $response = $controller->process(
            $request,
            $this->prophesize(DelegateInterface::class)->reveal()
        );

        $result = $response->getPayload();

        $this->assertArrayHasKey('apiSetting', $result, 'message has not been replaced with apiSetting');
    }

    public function testGetValidator()
    {
        $controller = $this->getTestMessageModelRestController();
        $validator = new \Zend_Validate_NotEmpty();
        $expectedValidator = $controller->getValidator($validator);
        $this->assertInstanceOf(\Zend_Validate_Interface::class, $expectedValidator, 'Validator not instance of Zend Validator');
    }

    public function testGetNotExistingValidator()
    {
        $controller = $this->getTestMessageModelRestController();
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Validator testValidatorThatDoesNotExist not found');
        $controller->getValidator('testValidatorThatDoesNotExist');
    }

    public function testGetNotValidValidator()
    {
        $controller = $this->getTestMessageModelRestController();
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid validator provided to addValidator; must be string or Zend_Validate_Interface. Supplied array');
        $controller->getValidator(['an_array as validator should fail']);
    }

    public function testTranslateRowWithDate()
    {
        $row = [
            'date' => new \MUtil_Date('1977-12-15 00:00:00'),
        ];

        $controller = $this->getTestMessageModelRestController();

        $model = new TestApiSettingsModel('test_messages', 'test');
        $controller->setModel($model);

        $translatedRow = $controller->translateRow($row);

        $this->assertEquals('1977-12-15T00:00:00+00:00', $translatedRow['date'], 'Date should be translated to ISO_8601 string');
    }


    public function testExtraFieldsOnPost()
    {
        $controller = $this->getTestMessageModelRestController(true);

        $newData = [
            'message' => 'a new entry!',
            'by' => 3,
            'optional' => null,
            'changed' => '2017-09-12 00:00:00',
            'changed_by' => 3,
            'created' => '2017-09-11 00:00:00',
            'created_by' => 3,
            'extra_field' => true,
        ];

        $request = $this->getRequest('POST', [], [], $newData);
        $response = $controller->process(
            $request,
            $this->prophesize(DelegateInterface::class)->reveal()
        );

        $this->checkResponse($response, EmptyResponse::class, 201);

        $request = $this->getRequest('GET', ['id' => 3]);

        $response = $controller->process(
            $request,
            $this->prophesize(DelegateInterface::class)->reveal()
        );

        unset($newData['extra_field']);

        $expectedData = ['id' => 3] + $newData;

        $this->assertEquals($expectedData, $response->getPayload());

    }

    public function testValidatorOnEmptyField()
    {
        $controller = $this->getTestMessageModelRestController(true);

        $newData = [
            'message' => 'a new entry!',
            'by' => 3,
            'optional' => null,
            'changed' => '2017-09-12 00:00:00',
            'changed_by' => 3,
            'created' => '2017-09-11 00:00:00',
            'created_by' => 3,
        ];

        $model = new TestApiSettingsModel('test_messages', 'test');
        $model->set('optional', 'validator', 'Alpha');
        $controller->setModel($model);


        $request = $this->getRequest('POST', [], [], $newData);
        $response = $controller->process(
            $request,
            $this->prophesize(DelegateInterface::class)->reveal()
        );

        $this->checkResponse($response, EmptyResponse::class, 201);

        $request = $this->getRequest('GET', ['id' => 3]);

        $response = $controller->process(
            $request,
            $this->prophesize(DelegateInterface::class)->reveal()
        );

        $expectedData = [
            'id' => 3,
            'apiSetting' => 'a new entry!',
            'by' => 3,
            'optional' => null,
            'changed' => '2017-09-12 00:00:00',
            'changed_by' => 3,
            'created' => '2017-09-11 00:00:00',
            'created_by' => 3,
        ];

        $this->assertEquals($expectedData, $response->getPayload());

    }

    /**
     * @param $responseClass
     * @param $statusCode
     */
    private function checkResponse($response, $responseClass, $statusCode)
    {
        $this->assertInstanceOf($responseClass, $response, 'Response not instance of ' . $responseClass);
        $this->assertSame($statusCode, $response->getStatusCode(), 'Status code is not ' . $statusCode);
    }

    private function getArrayModelRestController()
    {
        $container = $this->prophesize(ContainerInterface::class)->reveal();
        $loader    = $this->prophesize(ProjectOverloader::class)->reveal();
        $urlHelper = $this->prophesize(UrlHelper::class)->reveal();

        return new ArrayModelRestController($container, $loader, $urlHelper);
    }

    private function getTestMessageModelRestController($realLoader=false, $urlHelperRoutes=[])
    {
        $container = $this->prophesize(ContainerInterface::class)->reveal();
        if ($realLoader) {
            $loader = new ProjectOverloader([
                'Gems',
                'MUtil',
            ]);
            $loader->legacyClasses = true;
        } else {
            $loader    = $this->prophesize(ProjectOverloader::class)->reveal();
        }

        $urlHelperProphecy = $this->prophesize(UrlHelper::class);

        foreach ($urlHelperRoutes as $route=>$url) {
            $urlHelperProphecy->generate($route, Argument::cetera())->willReturn($url);
        }

        $urlHelper = $urlHelperProphecy->reveal();

        return new TestMessageModelRestController($container, $loader, $urlHelper);
    }

    private function getRequest($method='GET',$attributes=[], $queryParams=[],$postData=[], $uri='/')
    {
        $requestProphesy = $this->prophesize(ServerRequestInterface::class);
        $requestProphesy->getUri()->willReturn($this->prophesize(UriInterface::class)->reveal());
        $requestProphesy->getUri()->willReturn(new Uri($uri));
        $requestProphesy->getMethod()->willReturn($method);

        $routeResultProphecy = $this->prophesize(RouteResult::class);
        $routeResultProphecy->getMatchedRouteName()->willReturn($uri);
        $routeResult = $routeResultProphecy->reveal();

        $requestProphesy->getAttribute('Zend\Expressive\Router\RouteResult')->willReturn($routeResult);

        foreach($attributes as $attributeName=>$returnValue) {
            $requestProphesy->getAttribute($attributeName)->willReturn($returnValue);
        }
        $requestProphesy->getQueryParams()->willReturn($queryParams);

        $requestProphesy->getParsedBody()->willReturn($postData);

        return $requestProphesy->reveal();
    }


}