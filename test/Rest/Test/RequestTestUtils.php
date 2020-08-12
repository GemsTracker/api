<?php


namespace GemsTest\Rest\Test;


use Interop\Http\ServerMiddleware\DelegateInterface;
use Prophecy\Argument;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Laminas\Diactoros\Uri;
use Mezzio\Router\Route;
use Mezzio\Router\RouteResult;

trait RequestTestUtils
{
    /**
     * @param $responseClass
     * @param $statusCode
     */
    protected function checkResponse($response, $responseClass, $statusCode)
    {
        $this->assertInstanceOf($responseClass, $response, 'Response not instance of ' . $responseClass);
        $this->assertSame($statusCode, $response->getStatusCode(), 'Status code is not ' . $statusCode);
    }

    /**
     * @return DelegateInterface
     */
    protected function getDelegator()
    {
        return $this->prophesize(DelegateInterface::class)->reveal();
    }

    /**
     * @param string $method
     * @param array $attributes
     * @param array $queryParams
     * @param array $postData
     * @param string $uri
     * @return ServerRequestInterface
     */
    protected function getRequest($method='GET',$attributes=[], $queryParams=[],$postData=[], $routeOptions=[], $uri='', $contentType='application/json')
    {
        $requestProphesy = $this->prophesize(ServerRequestInterface::class);
        //$requestProphesy->getUri()->willReturn($this->prophesize(UriInterface::class)->reveal());
        $requestProphesy->getUri()->willReturn(new Uri($uri));
        $requestProphesy->getMethod()->willReturn($method);
        $requestProphesy->getHeaderLine('content-type')->willReturn($contentType);
        $requestProphesy->getAttribute('user_id')->willReturn(1);
        $requestProphesy->getAttribute('user_name')->willReturn('testUser');
        $requestProphesy->getAttribute('user_organization')->willReturn(0);
        $requestProphesy->getAttribute(Argument::type('string'))->willReturn(null);

        $routeProphecy = $this->prophesize(Route::class);
        $routeProphecy->getOptions()->willReturn($routeOptions);
        $route = $routeProphecy->reveal();


        $routeResultProphecy = $this->prophesize(RouteResult::class);
        $routeResultProphecy->getMatchedRouteName()->willReturn($uri);
        $routeResultProphecy->getMatchedRoute()->willReturn($route);
        $routeResult = $routeResultProphecy->reveal();

        $requestProphesy->getAttribute('Mezzio\Router\RouteResult')->willReturn($routeResult);

        foreach($attributes as $attributeName=>$returnValue) {
            $requestProphesy->getAttribute($attributeName)->willReturn($returnValue);
        }
        $requestProphesy->getQueryParams()->willReturn($queryParams);

        $requestProphesy->getParsedBody()->willReturn($postData);

        $bodyProphecy = $this->prophesize(StreamInterface::class);
        $bodyProphecy->getContents()->willReturn(json_encode($postData));
        $body = $bodyProphecy->reveal();

        $requestProphesy->getBody()->willReturn($body);

        return $requestProphesy->reveal();
    }
}
