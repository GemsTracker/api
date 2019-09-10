<?php


namespace GemsTest\Rest\Api\Repository;


use Gems\Rest\Exception\RestException;
use PHPUnit\Framework\TestCase;
use Gems\Rest\Repository\SurveyQuestionsRepository;

class SurveyQuestionsRepositoryTest extends TestCase
{
    protected $exampleQuestionInformation = [
        'test' => [
            'class' => 'question',
            'group' => 1,
            'type' => 'D',
            'title' => 'test',
            'id' => '1X1X1',
            'question' => 'When has this test question been answered?',
            'answers' => 'Date',
        ]
    ];

    protected $exampleQuestionInformation2 = [
        'test' => [
            'class' => 'question',
            'group' => 2,
            'type' => 'L',
            'title' => 'test',
            'id' => '2X2X2',
            'question' => 'Which would you pick?',
            'answers' => [
                '0' => 'A',
                '1' => 'B',
                '2' => 'C',
                '3' => 'D',
            ],
        ]
    ];

    protected $exampleQuestionList = [
        'test' => 'When has this test question been answered?',
    ];

    public function testGetSurvey()
    {
        $repository = $this->getRepository();
        $survey = $repository->getSurvey(1);
        $this->assertInstanceOf(\Gems_Tracker_Survey::class, $survey);
    }

    public function testGetSurveyQuestions()
    {
        $repository = $this->getRepository();
        $surveyQuestions = $repository->getSurveyQuestions(1);
        $this->assertEquals($this->exampleQuestionInformation, $surveyQuestions);
    }

    public function testGetSurveyList()
    {
        $repository = $this->getRepository();
        $surveyList = $repository->getSurveyList(1);
        $this->assertEquals($this->exampleQuestionList, $surveyList);
    }

    public function testGetSurveyListAndAnswers()
    {
        $repository = $this->getRepository();
        $result = $repository->getSurveyListAndAnswers(2);

        $expectedResult = [
            'test' => [
                'question' => $this->exampleQuestionInformation2['test']['question'],
                'answers' => $this->exampleQuestionInformation2['test']['answers'],
            ]
        ];

        $this->assertEquals($expectedResult, $result);
    }

    public function testGetSurveyQuestionsUnknownSurvey()
    {
        $repository = $this->getRepository();

        $this->expectException(RestException::class);
        $this->expectExceptionMessage('No existing survey ID selected');
        $result = $repository->getSurveyQuestions(3);
    }

    public function testGetSurveyListUnknownSurvey()
    {
        $repository = $this->getRepository();

        $this->expectException(RestException::class);
        $this->expectExceptionMessage('No existing survey ID selected');
        $result = $repository->getSurveyList(3);
    }

    public function testGetSurveyListAndAnswersUnknownSurvey()
    {
        $repository = $this->getRepository();

        $this->expectException(RestException::class);
        $this->expectExceptionMessage('No existing survey ID selected');
        $result = $repository->getSurveyListAndAnswers(3);
    }

    protected function getRepository()
    {
        $survey = $this->prophesize(\Gems_Tracker_Survey::class);
        $survey->getQuestionInformation('en')->willReturn($this->exampleQuestionInformation);
        $survey->getQuestionList('en')->willReturn($this->exampleQuestionList);

        $survey2 = $this->prophesize(\Gems_Tracker_Survey::class);
        $survey2->getQuestionInformation('en')->willReturn($this->exampleQuestionInformation2);

        $survey3 = $this->prophesize(\Gems_Tracker_Survey::class);
        $survey3->exists = false;




        $tracker = $this->prophesize(\Gems_Tracker::class);
        $tracker->getSurvey(1)->willReturn($survey->reveal());
        $tracker->getSurvey(2)->willReturn($survey2->reveal());
        $tracker->getSurvey(3)->willReturn($survey3->reveal());

        $locale = $this->prophesize(\Zend_Locale::class);
        $locale->getLanguage()->willReturn('en');

        return new SurveyQuestionsRepository($tracker->reveal(), $locale->reveal());
    }
}