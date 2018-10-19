<?php


namespace Pulse\Api\Repository;


use Gems\Rest\Exception\RestException;

class SurveyQuestionsRepository
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

    public function getSurvey($surveyId)
    {
        return $this->tracker->getSurvey($surveyId);
    }

    public function getSurveyQuestions($surveyId)
    {
        $survey = $this->getSurvey($surveyId);
        if (!$survey->exists) {
            throw new RestException('No existing survey ID selected', 2, 'no-valid-survey', 400);
        }

        $questionInformation = $survey->getQuestionInformation($this->locale->getLanguage());

        return $questionInformation;
    }

    public function getSurveyList($surveyId)
    {
        $survey = $this->getSurvey($surveyId);
        if (!$survey->exists) {
            throw new RestException('No existing survey ID selected', 2, 'no-valid-survey', 400);
        }

        $questionInformation = $survey->getQuestionList($this->locale->getLanguage());

        return $questionInformation;
    }



    public function getSurveyListAndAnswers($surveyId)
    {
        $survey = $this->getSurvey($surveyId);
        if (!$survey->exists) {
            throw new RestException('No existing survey ID selected', 2, 'no-valid-survey', 400);
        }

        $questionInformation = $survey->getQuestionInformation($this->locale->getLanguage());

        $questionList = [];
        foreach($questionInformation as $questionCode=>$questionInformation) {
            $questionList[$questionCode] = [
                'question' => $questionInformation['question'],
            ];
            if (array_key_exists('answers', $questionInformation) && is_array($questionInformation['answers']) && !empty($questionInformation['answers'])) {
                $questionList[$questionCode]['answers'] = $questionInformation['answers'];
            }
        }

        return $questionList;
    }
}