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

    /**
     * Get a Survey
     *
     * @param $surveyId
     * @return \Gems_Tracker_Survey
     */
    public function getSurvey($surveyId)
    {
        return $this->tracker->getSurvey($surveyId);
    }

    /**
     * Get Survey Question information
     *
     * @param $surveyId
     * @return array
     * @throws RestException
     */
    public function getSurveyQuestions($surveyId)
    {
        $survey = $this->getSurvey($surveyId);
        if (!$survey->exists) {
            throw new RestException('No existing survey ID selected', 2, 'no-valid-survey', 400);
        }

        $questionInformation = $survey->getQuestionInformation($this->locale->getLanguage());

        return $questionInformation;
    }

    /**
     * Get Survey Question List
     *
     * @param $surveyId
     * @return array
     * @throws RestException
     */
    public function getSurveyList($surveyId)
    {
        $survey = $this->getSurvey($surveyId);
        if (!$survey->exists) {
            throw new RestException('No existing survey ID selected', 2, 'no-valid-survey', 400);
        }

        $questionInformation = $survey->getQuestionList($this->locale->getLanguage());

        return $questionInformation;
    }

    /**
     * @param $surveyId
     * @return array
     * @throws RestException
     */
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