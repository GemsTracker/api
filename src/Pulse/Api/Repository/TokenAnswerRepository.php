<?php


namespace Pulse\Api\Repository;


class TokenAnswerRepository
{

    /**
     * @var \Zend_Locale
     */
    protected $locale;

    /**
     * @var \Gems_Tracker
     */
    protected $tracker;

    public function __construct(\Gems_Tracker $tracker, $LegacyLocale)
    {
        $this->locale = $LegacyLocale;
        $this->tracker = $tracker;
    }

    public function getTokenAnswers($tokenId)
    {
        $token = $this->tracker->getToken($tokenId);
        return $token->getRawAnswers();
    }

    public function getFormattedTokenAnswers($tokenId)
    {
        $token = $this->tracker->getToken($tokenId);
        $answers = $token->getRawAnswers();
        $survey = $token->getSurvey();
        $surveyQuestionInformation = $survey->getQuestionInformation($this->locale);

        foreach($surveyQuestionInformation as $questionCode=>$questionInformation) {
            if (array_key_exists('answers', $questionInformation) && is_array($questionInformation['answers']) && !empty($questionInformation['answers'])) {
                $answerOptions = $questionInformation['answers'];
                if (array_key_exists($questionCode, $answers) && array_key_exists($answers[$questionCode], $answerOptions)) {
                    $answers[$questionCode] = $answerOptions[$answers[$questionCode]];
                }
            }
        }

        return $answers;
    }
}