<?php


namespace Pulse\Api\Action;


use Gems\Rest\Action\RestControllerAbstract;
use Gems\Rest\Exception\RestException;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Psr\Http\Message\ServerRequestInterface;
use Pulse\Api\Repository\SurveyQuestionsRepository;
use Zend\Diactoros\Response\JsonResponse;

class EmmaSurveyQuestionsRestController extends RestControllerAbstract
{
    /**
     * @var SurveyQuestionsRepository
     */
    protected $surveyQuestionsRepository;

    /**
     * @var \Gems_Tracker
     */
    protected $tracker;

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

        $survey = $this->surveyQuestionsRepository->getSurvey($id);

        $surveyInformation = [
            'survey_id' => $id,
            'survey_name' => $survey->getName(),
            'active' => $survey->isActive(),
            'patient_survey' => !$survey->isTakenByStaff(),
            'result_field' => $survey->getResultField(),
            'questions' => $this->surveyQuestionsRepository->getSurveyList($id),
        ];

        return new JsonResponse($surveyInformation);
    }

}