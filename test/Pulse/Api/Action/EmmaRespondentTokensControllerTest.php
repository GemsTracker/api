<?php


namespace Pulse\Api\Action;


use GemsTest\Rest\Test\RequestTestUtils;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Zalt\Loader\ProjectOverloader;
use Zend\Diactoros\Response\EmptyResponse;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Expressive\Helper\UrlHelper;

class EmmaRespondentTokensControllerTest extends TestCase
{
    use RequestTestUtils;

    public function testNoId()
    {
        $controller = $this->getControlller();
        $request = $this->getRequest('GET');
        $delegate = $this->getDelegator();

        $response = $controller->get($request, $delegate);

        $this->checkResponse($response, JsonResponse::class, 400);

        $responseData = $response->getPayload();
        $this->assertEquals('missing_data', $responseData['error'], 'Error code is missing or not "missing_data"');
    }

    public function testNoModelKeys()
    {
        $controller = $this->getControlller(false);
        $request = $this->getRequest('GET', ['id' => 1]);
        $delegate = $this->getDelegator();

        $response = $controller->get($request, $delegate);

        $this->checkResponse($response, EmptyResponse::class, 404);
    }

    public function testLoad()
    {
        $expected = [['test' => 'test data']];
        $controller = $this->getControlller(true, $expected);
        $request = $this->getRequest('GET', ['id' => 1]);
        $delegate = $this->getDelegator();

        $response = $controller->get($request, $delegate);

        $this->checkResponse($response, JsonResponse::class, 200);

        $responseData = $response->getPayload();
        $this->assertEquals($expected, $responseData, 'Data returned is not the same as expected data');
    }

    protected function getControlller($loadKeys=true, $loadData=null)
    {
        $projectOverloaderProphecy = $this->prophesize(ProjectOverloader::class);
        $urlHelperProphecy = $this->prophesize(UrlHelper::class);
        $legacyDbProphecy = $this->prophesize(\Zend_Db_Adapter_Abstract::class);

        $controller = new EmmaRespondentTokensController(
            $projectOverloaderProphecy->reveal(),
            $urlHelperProphecy->reveal(),
            $legacyDbProphecy->reveal()
        );

        $modelProphecy = $this->prophesize(\Gems_Tracker_Model_StandardTokenModel::class);
        if ($loadKeys) {
            $modelProphecy->getKeys()->willReturn(['id' => 'gr2o_patient_nr']);
        } else {
            $modelProphecy->getKeys()->willReturn([]);
        }
        $modelProphecy->load(Argument::type('array'))->willReturn($loadData);

        $controller->setModelName($modelProphecy->reveal());

        return $controller;
    }
}