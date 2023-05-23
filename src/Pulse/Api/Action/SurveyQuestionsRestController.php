<?php


namespace Pulse\Api\Action;


use Gems\Rest\Action\RestControllerAbstract;
use Gems\Rest\Exception\RestException;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Psr\Http\Message\ServerRequestInterface;
use Gems\Rest\Repository\SurveyQuestionsRepository;
use Laminas\Diactoros\Response\JsonResponse;

class SurveyQuestionsRestController extends RestControllerAbstract
{
    public static $definition = [
        'topic' => 'survey question information',
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
                        'gsu_id_survey' => 'int',
                        'questions' => 'array',
                    ],
                    400 => 'survey id missing',
                ],
            ],
        ],
    ];

    /**
     * @var \Gems\Rest\Repository\SurveyQuestionsRepository
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
            'questions' => array_values($this->surveyQuestionsRepository->getSurveyQuestions($id)),
        ];

        return new JsonResponse($surveyQuestions);
    }

}
