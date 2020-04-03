<?php


namespace Pulse\Api\Action;


use Gems\Rest\Action\RestControllerAbstract;
use Gems\Rest\Exception\RestException;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Psr\Http\Message\ServerRequestInterface;
use Gems\Rest\Repository\SurveyQuestionsRepository;
use Laminas\Diactoros\Response\JsonResponse;

class EmmaSurveyQuestionsRestController extends RestControllerAbstract
{
    public static $definition = [
        'topic' => 'Emma Survey questions',
        'methods' => [
            'get' => [
                'params' => [
                    'id' => [
                        'type' => 'int',
                        'required' => true,
                    ]
                ],
                'responses' => [
                    200 => [
                        'survey_id' => 'int',
                        'survey_name' => 'string',
                        'active' => 'boolean',
                        'patient_survey' => 'boolean',
                        'result_field' => 'string',
                        'questions' => 'array',
                    ],
                    400 => 'survey id missing',
                    404 => 'survey not found',
                ],
            ],
        ],
    ];

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
            return new JsonResponse(['error' => 'survey_id_missing', 'message' => 'Survey questions need a survey ID in the id parameter'], 400);
        }

        $survey = $this->surveyQuestionsRepository->getSurvey($id);

        if (!$survey->exists) {
            return new JsonResponse(['error' => 'survey_not_found', 'message' => sprintf('Survey with ID %s could not be found.', $id)], 404);
        }

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
