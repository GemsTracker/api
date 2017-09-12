<?php


namespace GemsTest\Rest\Action;


use Interop\Container\ContainerInterface;
use Interop\Http\ServerMiddleware\DelegateInterface;
use PHPUnit\DbUnit\DataSet\YamlDataSet;
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

class RestControllerTest extends ZendDbTestCase
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

        $routeResultProphecy = $this->prophesize(RouteResult::class);
        $routeResultProphecy->getMatchedRouteName()->willReturn('/');
        $routeResult = $routeResultProphecy->reveal();

        $request = $this->getRequest('GET', ['id' => 1, 'Zend\Expressive\Router\RouteResult' => $routeResult]);

        $response = $controller->process(
            $request,
            $this->prophesize(DelegateInterface::class)->reveal()
        );

        $this->checkResponse($response,JsonResponse::class, 200);

        $expectedData = [
            'id' => 1,
            'message' => 'Test',
            'by' => 1,
            'changed' => '2017-09-12 00:00:00',
            'changed_by' => 1,
            'created' => '2017-09-11 00:00:00',
            'created_by' => 1,
        ];

        $this->assertEquals($expectedData, json_decode($response->getBody(), true), 'parsed body not the same as expected data');
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

        $routeResultProphecy = $this->prophesize(RouteResult::class);
        $routeResultProphecy->getMatchedRouteName()->willReturn('/');
        $routeResult = $routeResultProphecy->reveal();

        $request = $this->getRequest('GET', ['id' => 1, 'Zend\Expressive\Router\RouteResult' => $routeResult]);

        $response = $controller->process(
            $request,
            $this->prophesize(DelegateInterface::class)->reveal()
        );

        $this->checkResponse($response,EmptyResponse::class, 404);
    }

    public function testGetList()
    {
        $controller = $this->getArrayModelRestController();
        $controller->setData([
            [
                'id' => 1,
                'message' => 'hello',
                'created_on' => '2017-09-12 00:00:00'
            ]
        ]);

        $routeResultProphecy = $this->prophesize(RouteResult::class);
        $routeResultProphecy->getMatchedRouteName()->willReturn('/');
        $routeResult = $routeResultProphecy->reveal();

        $request = $this->getRequest('GET', ['id' => null, 'Zend\Expressive\Router\RouteResult' => $routeResult]);

        $response = $controller->process(
            $request,
            $this->prophesize(DelegateInterface::class)->reveal()
        );

        $this->checkResponse($response,JsonResponse::class, 200);
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

        $routeResultProphecy = $this->prophesize(RouteResult::class);
        $routeResultProphecy->getMatchedRouteName()->willReturn('/');
        $routeResult = $routeResultProphecy->reveal();

        $request = $this->getRequest('GET', ['id' => null, 'Zend\Expressive\Router\RouteResult' => $routeResult]);

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

        $routeResultProphecy = $this->prophesize(RouteResult::class);
        $routeResultProphecy->getMatchedRouteName()->willReturn('/');
        $routeResult = $routeResultProphecy->reveal();

        $request = $this->getRequest('GET', ['id' => null, 'Zend\Expressive\Router\RouteResult' => $routeResult]);

        $response = $controller->process(
            $request,
            $this->prophesize(DelegateInterface::class)->reveal()
        );

        $totalCount = $response->getHeader('X-total-count');

        $this->assertTrue(is_array($totalCount));

        $count = reset($totalCount);

        $this->assertEquals(2, $count);
    }

    /**
     * @param $responseClass
     * @param $statusCode
     */
    private function checkResponse($response, $responseClass, $statusCode)
    {
        $this->assertInstanceOf($responseClass, $response, 'Response not instance of Zend\Diactoros\Response\EmptyResponse');
        $this->assertSame($statusCode, $response->getStatusCode(), 'Status code is not 204');
    }

    private function getArrayModelRestController()
    {
        $container = $this->prophesize(ContainerInterface::class)->reveal();
        $loader    = $this->prophesize(ProjectOverloader::class)->reveal();
        $urlHelper = $this->prophesize(UrlHelper::class)->reveal();

        return new ArrayModelRestController($container, $loader, $urlHelper);
    }

    private function getTestMessageModelRestController()
    {
        $container = $this->prophesize(ContainerInterface::class)->reveal();
        $loader    = $this->prophesize(ProjectOverloader::class)->reveal();
        $urlHelper = $this->prophesize(UrlHelper::class)->reveal();

        return new TestMessageModelRestController($container, $loader, $urlHelper);
    }

    private function getRequest($method='GET',$attributes=[], $queryParams=[])
    {
        $requestProphesy = $this->prophesize(ServerRequestInterface::class);
        $requestProphesy->getUri()->willReturn($this->prophesize(UriInterface::class)->reveal());
        $requestProphesy->getUri()->willReturn(new Uri('/'));
        $requestProphesy->getMethod()->willReturn('GET');
        foreach($attributes as $attributeName=>$returnValue) {
            $requestProphesy->getAttribute($attributeName)->willReturn($returnValue);
        }
        $requestProphesy->getQueryParams()->willReturn($queryParams);

        return $requestProphesy->reveal();
    }
}