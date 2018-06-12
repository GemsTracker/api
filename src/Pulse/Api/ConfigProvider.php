<?php


namespace Pulse\Api;


use Gems\Rest\Auth\AuthorizeGemsAndOauthMiddleware;
use Gems\Rest\Factory\ReflectionFactory;
use Gems\Rest\RestModelConfigProviderAbstract;
use Pulse\Api\Action\SurveyQuestionsRestController;
use Pulse\Api\Action\TokenAnswersRestController;
use Pulse\Api\Action\TrackfieldsRestController;
use Pulse\Api\Action\TreatmentEpisodesRestController;
use Pulse\Api\Repository\SurveyQuestionsRepository;
use Pulse\Api\Repository\TokenAnswerRepository;
use Pulse\Api\Repository\TrackfieldsRepository;
use Pulse\Api\Repository\TreatmentEpisodesRepository;

class ConfigProvider extends RestModelConfigProviderAbstract
{
    /**
     * Returns the configuration array
     *
     * To add a bit of a structure, each section is defined in a separate
     * method which returns an array with its configuration.
     *
     * @return array
     */
    public function __invoke()
    {
        return [
            'dependencies' => $this->getDependencies(),
            //'templates'    => $this->getTemplates(),
            'routes'       => $this->getRoutes(),
        ];
    }

    public function getDependencies()
    {
        return [
            'factories'  => [

                SurveyQuestionsRestController::class => ReflectionFactory::class,
                SurveyQuestionsRepository::class => ReflectionFactory::class,

                TokenAnswersRestController::class => ReflectionFactory::class,
                TokenAnswerRepository::class => ReflectionFactory::class,

                TreatmentEpisodesRestController::class => ReflectionFactory::class,
                TreatmentEpisodesRepository::class => ReflectionFactory::class,

                TrackfieldsRestController::class => ReflectionFactory::class,
                TrackfieldsRepository::class => ReflectionFactory::class,
            ]
        ];
    }

    public function getRestModels()
    {
        return [
            'organizations2' => [
                'model' => 'Model\\OrganizationModel',
                'methods' => ['GET', 'POST', 'PATCH', 'DELETE'],
            ],
            'respondents' => [
                'model' => 'Model_RespondentModel',
                'methods' => ['GET'],
                'applySettings' => 'applyEditSettings',
                'idField' => 'gr2o_patient_nr',
                'idFieldRegex' => '[0-9]{6}-A[0-9]{3}',
                //'idFieldRegex' => '\d+',
            ],
            'tracks' => [
                'model' => 'Tracker_Model_TrackModel',
                'methods' => ['GET'],
                'hasMany' => ['rounds' => 'rounds'],
            ],
            'rounds' => [
                'model' => 'Tracker\\Model\\RoundModel',
                'methods' => ['GET'],
            ],
            'surveys' => [
                'model' => 'Model\\SimpleSurveyModel',
                'methods' => ['GET'],
            ],
            'tokens' => [
                'model' => 'Tracker_Model_StandardTokenModel',
                'methods' => ['GET'],
                'idFieldRegex' => '[a-zA-Z0-9]{4}-[a-zA-Z0-9]{4}',
            ],
            'respondent-tracks' => [
                'model' => 'Tracker_Model_RespondentTrackModel',
                'methods' => ['GET'],
            ],
            'extreme-values' => [
                'model' => 'Model\\EvdModel',
                'methods' => ['GET'],
            ],
            'outcome-variables' => [
                'model' => 'Model\\OutcomeVariableModel',
                'methods' => ['GET'],
            ],
        ];
    }

    protected function getRoutes()
    {
        $routes = parent::getRoutes();

        $newRoutes = [
            [
                'name' => 'survey-questions',
                'path' => '/survey-questions/[{id:\d+}]',
                'middleware' => [
                    AuthorizeGemsAndOauthMiddleware::class,
                    SurveyQuestionsRestController::class
                ],
                'allowed_methods' => ['GET'],
            ],
            [
                'name' => 'token-answers',
                'path' => '/token-answers/[{id:[a-zA-Z0-9-_]+}]',
                'middleware' => [
                    AuthorizeGemsAndOauthMiddleware::class,
                    TokenAnswersRestController::class
                ],
                'allowed_methods' => ['GET'],
            ],
            [
                'treatment-episodes',
                'path' => '/treatment-episodes/[{id:\d+}]',
                'middleware' => [
                    AuthorizeGemsAndOauthMiddleware::class,
                    TreatmentEpisodesRestController::class
                ],
                'allowed_methods' => ['GET'],
            ],
            [
                'track-fields',
                'path' => '/track-fields/[{id:\d+}]',
                'middleware' => [
                    AuthorizeGemsAndOauthMiddleware::class,
                    TrackfieldsRestController::class
                ],
                'allowed_methods' => ['GET'],
            ],
        ];


        return array_merge($routes, $newRoutes);
    }
}