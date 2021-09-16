<?php


namespace Gems\DataSetMapper\Action\InputMapping;


use Gems\Rest\Repository\SurveyQuestionsRepository;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Laminas\Diactoros\Response\JsonResponse;

class SurveyQuestions implements MiddlewareInterface
{
    /**
     * @var SurveyQuestionsRepository
     */
    protected $surveyQuestionsRepository;

    public function __construct(SurveyQuestionsRepository $surveyQuestionsRepository)
    {
        $this->surveyQuestionsRepository = $surveyQuestionsRepository;
    }

    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $surveyId = $id = $request->getAttribute('surveyId');

        if ($surveyId) {
            $surveyQuestions = $this->surveyQuestionsRepository->getSurveyListAndAnswers($surveyId, true);

            return new JsonResponse($surveyQuestions, 200);
        }

        $error = [
            'error' => 'no_survey',
            'message' => 'No survey with supplied ID could be found',
        ];

        return new JsonResponse($error, 404);
    }
}
