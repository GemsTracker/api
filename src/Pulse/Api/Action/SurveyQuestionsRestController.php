<?php


namespace Pulse\Api\Action;


use Gems\Rest\Action\RestControllerAbstract;
use Gems\Rest\Exception\RestException;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Psr\Http\Message\ServerRequestInterface;
use Pulse\Api\Repository\SurveyQuestionsRepository;
use Zend\Diactoros\Response\JsonResponse;

class SurveyQuestionsRestController extends RestControllerAbstract
{
    /**
     * @var SurveyQuestionsRepository
     */
    protected $surveyQuestionsRepository;

    public function __construct(SurveyQuestionsRepository $surveyQuestionsRepository)
    {
        $this->surveyQuestionsRepository = $surveyQuestionsRepository;
    }

    public function get(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $id = $request->getAttribute('id');
        if ($id === null) {
            throw new RestException('Survey questions need a survey ID in the id parameter', 1, 'survey_id_missing', 400);
        }

        $surveyQuestions = [
            'gsu_id_survey' => $id,
            'questions' => $this->surveyQuestionsRepository->getSurveyQuestions($id),
        ];

        return new JsonResponse($surveyQuestions);
    }

}