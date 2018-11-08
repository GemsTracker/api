<?php


namespace Pulse\Api\Action;


use GemsTest\Rest\Test\RequestTestUtils;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Pulse\Api\Repository\TokenAnswerRepository;
use Zend\Diactoros\Response\JsonResponse;

class EmmaTokenAnswersRestControllerTest extends TestCase
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
        $this->assertEquals('token_id_missing', $responseData['error'], 'Error code is missing or not "token_id_missing"');
    }

    public function testTokenAnswers()
    {
        $expected = [
            'SCORE' => 10,
            'id' => 1
        ];

        $controller = $this->getControlller($expected);
        $request = $this->getRequest('GET', ['id' => 'ABCD-1234']);
        $delegate = $this->getDelegator();

        $response = $controller->get($request, $delegate);

        $this->checkResponse($response, JsonResponse::class, 200);

        $responseData = $response->getPayload();

        // id will get stripped from the answers!
        unset($expected['id']);

        $this->assertEquals($expected, $responseData, 'Response data does not match expected');
    }

    protected function getControlller($answers = [])
    {
        $tokenAnswerRepositoryPropecy = $this->prophesize(TokenAnswerRepository::class);
        $tokenAnswerRepositoryPropecy->getFormattedTokenAnswers(Argument::type('string'))->willReturn($answers);

        $controller = new EmmaTokenAnswersRestController($tokenAnswerRepositoryPropecy->reveal());
        return $controller;
    }
}