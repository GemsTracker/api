<?php


namespace PulseTest\Rest\Api\Repository;


use PHPUnit\Framework\TestCase;
use Pulse\Api\Repository\TokenAnswerRepository;

class TokenAnswerRepositoryTest extends TestCase
{
    protected $exampleQuestionInformation = [
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

    protected $exampleTokenRawAnswers = [
        'id' => 1,
        'token' => 'abcd_1234',
        'submitdate' => '2019-01-01 02:34:56',
        'lastpage' => 1,
        'startlanguage' => 'nl',
        'startdate' => '2019-01-01 01:23:45',
        'datestamp' => '2019-01-01 01:23:45',
        'ipaddr' => '127.0.0.1',
        'test' => '2',
    ];

    public function testGetTokenAnswers()
    {
        $repository = $this->getRepository();
        $result = $repository->getTokenAnswers('abcd-1234');

        $this->assertEquals($this->exampleTokenRawAnswers, $result);
    }

    public function testGetFormattedTokenAnswers()
    {
        $repository = $this->getRepository();
        $result = $repository->getFormattedTokenAnswers('abcd-1234');
        $expectedResult = $this->exampleTokenRawAnswers;
        $expectedResult['test'] = 'C';

        $this->assertEquals($expectedResult, $result);
    }

    protected function getRepository()
    {
        $survey = $this->prophesize(\Gems_Tracker_Survey::class);
        $survey->getQuestionInformation('en')->willReturn($this->exampleQuestionInformation);

        $token = $this->prophesize(\Gems_Tracker_Token::class);
        $token->getSurvey()->willReturn($survey->reveal());
        $token->getRawAnswers()->willReturn($this->exampleTokenRawAnswers);

        $tracker = $this->prophesize(\Gems_Tracker::class);
        $tracker->getToken('abcd-1234')->willReturn($token->reveal());

        $locale = $this->prophesize(\Zend_Locale::class);
        $locale->getLanguage()->willReturn('en');
        $locale->__toString()->willReturn('en');

        return new TokenAnswerRepository($tracker->reveal(), $locale->reveal());
    }
}