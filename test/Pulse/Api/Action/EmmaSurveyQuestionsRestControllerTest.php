<?php


namespace Pulse\Api\Action;


use GemsTest\Rest\Test\RequestTestUtils;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Pulse\Api\Repository\SurveyQuestionsRepository;
use Zend\Diactoros\Response\JsonResponse;

class EmmaSurveyQuestionsRestControllerTest extends TestCase
{
    use RequestTestUtils;

    public function testNoId()
    {
        $controller = $this->getSurveyQuestionRestController();
        $request = $this->getRequest('GET');
        $delegate = $this->getDelegator();

        $response = $controller->get($request, $delegate);

        $this->checkResponse($response, JsonResponse::class, 400);

        $responseData = $response->getPayload();
        $this->assertEquals('survey_id_missing', $responseData['error'], 'Error code is missing or not "survey_id_missing"');
    }

    public function testGetSurveyInformation()
    {
        $controller = $this->getSurveyQuestionRestController();
        $request = $this->getRequest('GET', ['id' => 1]);
        $delegate = $this->getDelegator();

        $response = $controller->get($request, $delegate);

        $this->checkResponse($response, JsonResponse::class, 200);

        $responseData = $response->getPayload();

        $expected = [
            'survey_id' => 1,
            'survey_name' => 'Test survey',
            'active' => true,
            'patient_survey' => true,
            'result_field' => 'SCORE',
            'questions' => []
        ];

        $this->assertEquals($expected, $responseData, 'Response not matching expected result');
    }

    public function testSurveyNotFound()
    {
        $controller = $this->getSurveyQuestionRestController(false);
        $request = $this->getRequest('GET', ['id' => 1]);
        $delegate = $this->getDelegator();

        $response = $controller->get($request, $delegate);

        $this->checkResponse($response, JsonResponse::class, 404);

        $responseData = $response->getPayload();
        $this->assertEquals('survey_not_found', $responseData['error'], 'Survey with ID 1 could not be found.');
    }

    public function getSurveyQuestionRestController($surveyFound = true)
    {
        $survey = $this->prophesize(\Gems_Tracker_Survey::class);
        $survey->exists = $surveyFound;
        $survey->getName()->willReturn('Test survey');
        $survey->isActive()->willReturn(true);
        $survey->isTakenByStaff()->willReturn(false);
        $survey->getResultField()->willReturn('SCORE');

        $surveyQuestionRepositoryProphecy = $this->prophesize(SurveyQuestionsRepository::class);
        $surveyQuestionRepositoryProphecy->getSurvey(Argument::type('int'))->willReturn($survey->reveal());
        $surveyQuestionRepositoryProphecy->getSurveyList(Argument::type('int'))->willReturn([]);
        $surveyQuestionRepositoryProphecy->getSurveyList(Argument::type('int'))->willReturn([]);
        $surveyQuestionRepositoryProphecy->getSurveyQuestions(Argument::type('int'))->willReturn([]);

        $controller = new EmmaSurveyQuestionsRestController($surveyQuestionRepositoryProphecy->reveal());
        return $controller;
    }
}