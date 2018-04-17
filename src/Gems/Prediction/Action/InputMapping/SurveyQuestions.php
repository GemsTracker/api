<?php


namespace Gems\Prediction\Action\InputMapping;


use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\JsonResponse;

class SurveyQuestions implements MiddlewareInterface
{
    /**
     * @var \Zend_Locale
     */
    protected $locale;

    /**
     * @var \Gems_Tracker
     */
    protected $tracker;

    public function __construct(\Gems_Tracker $tracker, \Zend_Locale $locale)
    {
        $this->locale = $locale;
        $this->tracker = $tracker;
    }

    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $surveyId = $id = $request->getAttribute('surveyId');

        $survey = $this->tracker->getSurvey($surveyId);


        if ($survey) {

            $language = $this->locale->getLanguage();
            $questionAnswers = $survey->getQuestionInformation($language);

            return new JsonResponse($questionAnswers, 200);
        }

        $error = [
            'error' => 'no_survey',
            'message' => 'No survey with supplied ID could be found',
        ];

        return new JsonResponse($error, 404);
    }
}