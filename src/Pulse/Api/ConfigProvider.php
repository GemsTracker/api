<?php


namespace Pulse\Api;

use Gems\Rest\Factory\ReflectionFactory;
use Gems\Rest\RestModelConfigProviderAbstract;
use Pulse\Api\Action\ChartsController;
use Pulse\Api\Action\RespondentTrackfieldsRestController;
use Pulse\Api\Action\RespondentTrackRestController;
use Pulse\Api\Action\SurveyQuestionsRestController;
use Pulse\Api\Action\TokenAnswersRestController;
use Pulse\Api\Action\TrackfieldsRestController;
use Pulse\Api\Action\TreatmentEpisodesRestController;
use Pulse\Api\Action\TreatmentsWithNormsController;
use Pulse\Api\Model\RespondentTrackModel;
use Pulse\Api\Repository\ChartRepository;
use Pulse\Api\Repository\RespondentResults;
use Pulse\Api\Repository\RespondentTrackfieldsRepository;
use Pulse\Api\Repository\SurveyQuestionsRepository;
use Pulse\Api\Repository\TokenAnswerRepository;
use Pulse\Api\Repository\TrackfieldsRepository;
use Pulse\Api\Repository\TreatmentEpisodesRepository;
use Pulse\Api\Repository\TreatmentsWithNormsRepository;

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

                RespondentTrackfieldsRestController::class => ReflectionFactory::class,
                RespondentTrackfieldsRepository::class => ReflectionFactory::class,

                ChartsController::class => ReflectionFactory::class,
                ChartRepository::class => ReflectionFactory::class,

                TreatmentsWithNormsController::class => ReflectionFactory::class,
                TreatmentsWithNormsRepository::class => ReflectionFactory::class,

                RespondentResults::class => ReflectionFactory::class,

                RespondentTrackRestController::class => ReflectionFactory::class,
            ]
        ];
    }

    public function getRestModels()
    {
        return [
            'organizations2' => [
                'model' => 'Model\\OrganizationModel',
                'methods' => ['GET', 'POST', 'PATCH', 'DELETE'],
                'allowed_fields' => [
                    'gor_id_organization',
                    'gor_name',
                    'gor_url',
                    'gor_task',
                ],
                /*'disallowed_fields' => [
                    'gor_user_class'
                ],
                'readonly_fields' => [
                    'gor_name',
                ],*/
                'organizationId' => 'gor_id_organization',
            ],
            'respondents' => [
                'model' => 'Model_RespondentModel',
                'methods' => ['GET', 'POST', 'PATCH'],
                'applySettings' => 'applyEditSettings',
                'idField' => [
                    'gr2o_patient_nr',
                    'gr2o_id_organization',
                ],
                'idFieldRegex' => [
                    '[0-9]{6}-A[0-9]{3}',
                    '\d+',
                ],
            ],
            'tracks' => [
                'model' => 'Tracker_Model_TrackModel',
                'methods' => ['GET'],
                'hasMany' => ['rounds' => 'rounds'],
                'multiOranizationField' => [
                    'field' => 'gtr_organizations',
                    'separator' => '|',
                ],
            ],
            'rounds' => [
                'model' => 'Tracker\\Model\\RoundModel',
                'methods' => ['GET'],
            ],
            'surveys' => [
                'model' => 'Model\\SimpleSurveyModel',
                'methods' => ['GET'],
                'multiOranizationField' => [
                    'field' => 'gsu_insert_organizations',
                    'separator' => '|',
                ],
            ],
            'tokens' => [
                'model' => 'Tracker_Model_StandardTokenModel',
                'methods' => ['GET'],
                'idFieldRegex' => '[a-zA-Z0-9]{4}-[a-zA-Z0-9]{4}',
            ],
            'respondent-tracks' => [
                'model' => RespondentTrackModel::class,
                'methods' => ['GET', 'PATCH', 'POST'],
                'idField' => 'gr2t_id_respondent_track',
                'customAction' => RespondentTrackRestController::class,
            ],
            'extreme-values' => [
                'model' => 'Model\\EvdModel',
                'methods' => ['GET'],
            ],
            'outcome-variables' => [
                'model' => 'Model\\OutcomeVariableModel',
                'methods' => ['GET'],
            ],
            /*'treatments-with-norms' => [
                'model' => TreatmentWithNormsModel::class,
                'methods' => ['GET'],
            ],*/
        ];
    }

    protected function getRoutes()
    {
        $routes = parent::getRoutes();

        $newRoutes = [
            [
                'name' => 'survey-questions',
                'path' => '/survey-questions/[{id:\d+}]',
                'middleware' => $this->getCustomActionMiddleware(SurveyQuestionsRestController::class),
                'allowed_methods' => ['GET'],
            ],
            [
                'name' => 'token-answers',
                'path' => '/token-answers/[{id:[a-zA-Z0-9-_]+}]',
                'middleware' => $this->getCustomActionMiddleware(TokenAnswersRestController::class),
                'allowed_methods' => ['GET'],
            ],
            [
                'name' => 'treatment-episodes',
                'path' => '/treatment-episodes/[{id:\d+}]',
                'middleware' => $this->getCustomActionMiddleware(TreatmentEpisodesRestController::class),
                'allowed_methods' => ['GET'],
            ],
            [
                'name' => 'track-fields',
                'path' => '/track-fields/[{id:\d+}]',
                'middleware' => $this->getCustomActionMiddleware(TrackfieldsRestController::class),
                'allowed_methods' => ['GET'],
            ],
            [
                'name' => 'respondent-track-fields',
                'path' => '/respondent-track-fields/[{id:\d+}]',
                'middleware' => $this->getCustomActionMiddleware(RespondentTrackfieldsRestController::class),
                'allowed_methods' => ['GET', 'PATCH'],
            ],
            [
                'name' => 'chartdata',
                'path' => '/chartdata/[{id:\d+}]',
                'middleware' => $this->getCustomActionMiddleware(ChartsController::class),
                'allowed_methods' => ['GET'],
            ],
            [
                'name' => 'treatments-with-norms',
                'path' => '/treatments-with-norms',
                'middleware' => $this->getCustomActionMiddleware(TreatmentsWithNormsController::class),
                'allowed_methods' => ['GET'],
            ],
        ];


        return array_merge($routes, $newRoutes);
    }
}