<?php


namespace Pulse\Api\Repository;


use Gems\Rest\Exception\RestException;

class SurveyQuestionsRepository
{
    /**
     * @var \Gems_Tracker
     */
    protected $tracker;


    public function __construct(\Gems_Tracker $tracker, $LegacyLocale)
    {
        $this->locale = $LegacyLocale;
        $this->tracker = $tracker;

    }

    public function getSurveyQuestions($surveyId)
    {
        $survey = $this->tracker->getSurvey($surveyId);
        if (!$survey->exists) {
            throw new RestException('No existing survey ID selected', 2, 'no-valid-survey', 400);
        }

        $questionInformation = $survey->getQuestionInformation($this->locale->getLanguage());

        return $questionInformation;
    }

}