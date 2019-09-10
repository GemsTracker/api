<?php


namespace Gems\Rest\Repository;


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

    protected $questionTypeTranslations = [
        '!' => 'dropdown-list',
        'L' => 'list',
        'O' => 'list-comment',
        '1' => null,
        'H' => null,
        'F' => null,
        'R' => null,
        'A' => 'array-5',
        'B' => 'array-10',
        ':' => 'multi-array-10',
        '5' => '5-point-radio',
        'N' => 'number',
        'K' => 'multi-number',
        'Q' => 'multi-short-text',
        ';' => 'multi-array',
        'S' => 'short-text',
        'T' => 'long-text',
        'U' => 'huge-text',
        'M' => 'multiple-choice',
        'P' => 'multiple-choice-comment',
        'D' => 'date',
        '*' => 'equation',
        'I' => 'language',
        '|' => 'file-upload',
        'X' => 'empty',
        'G' => 'gender',
        'Y' => 'yes-no',
        'C' => 'yes-uncertain-no',
        'E' => 'increase-same-decrease',
    ];


    public function __construct(\Gems_Tracker $tracker, $LegacyLocale)
    {
        $this->locale = $LegacyLocale;
        $this->tracker = $tracker;
    }

    public function getQuestionType($type)
    {
        if (array_key_exists($type, $this->questionTypeTranslations)) {
            return $this->questionTypeTranslations[$type];
        }
        return null;
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
    public function getSurveyListAndAnswers($surveyId, $addTypes=false)
    {
        $survey = $this->getSurvey($surveyId);
        if (!$survey->exists) {
            throw new RestException('No existing survey ID selected', 2, 'no-valid-survey', 400);
        }

        $surveyInformation = $survey->getQuestionInformation($this->locale->getLanguage());

        $questionList = [];
        foreach($surveyInformation as $questionCode=>$questionInformation) {
            $questionList[$questionCode] = [
                'question' => $questionInformation['question'],
            ];
            if (array_key_exists('answers', $questionInformation) && is_array($questionInformation['answers']) && !empty($questionInformation['answers'])) {
                $questionList[$questionCode]['answers'] = $questionInformation['answers'];
            }
            if ($addTypes && array_key_exists('type', $questionInformation)) {
                $questionList[$questionCode]['type'] = $this->getQuestionType($questionInformation['type']);
            }
        }

        return $questionList;
    }


}