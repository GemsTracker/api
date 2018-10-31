<?php


namespace Pulse\Api;

use Gems\Rest\Factory\ReflectionFactory;
use Gems\Rest\RestModelConfigProviderAbstract;
use Pulse\Api\Action\ActivityMatcher;
use Pulse\Api\Action\AppointmentRestController;
use Pulse\Api\Action\ChartsController;
use Pulse\Api\Action\CorrectTokenController;
use Pulse\Api\Action\EmmaRespondentTokensController;
use Pulse\Api\Action\EmmaRespondentTrackRestController;
use Pulse\Api\Action\EmmaSurveyQuestionsRestController;
use Pulse\Api\Action\EmmaTokenAnswersRestController;
use Pulse\Api\Action\EnvTestController;
use Pulse\Api\Action\InsertTrackTokenController;
use Pulse\Api\Action\PermissionGeneratorController;
use Pulse\Api\Action\RespondentBulkRestController;
use Pulse\Api\Action\RespondentRestController;
use Pulse\Api\Action\RespondentTrackfieldsRestController;
use Pulse\Api\Action\AllRespondentTrackRestController;
use Pulse\Api\Action\SurveyQuestionsRestController;
use Pulse\Api\Action\TokenAnswersRestController;
use Pulse\Api\Action\TokenController;
use Pulse\Api\Action\TrackfieldsRestController;
use Pulse\Api\Action\TreatmentEpisodesRestController;
use Pulse\Api\Action\TreatmentsWithNormsController;
use Pulse\Api\Model\Emma\AgendaDiagnosisRepository;
use Pulse\Api\Model\Emma\AppointmentRepository;
use Pulse\Api\Model\Emma\OrganizationRepository;
use Pulse\Api\Model\Emma\RespondentRepository;
use Pulse\Api\Model\RespondentTrackModel;
use Pulse\Api\Repository\ChartRepository;
use Pulse\Api\Repository\RespondentResults;
use Pulse\Api\Repository\RespondentTrackfieldsRepository;
use Pulse\Api\Repository\SurveyQuestionsRepository;
use Pulse\Api\Repository\TokenAnswerRepository;
use Pulse\Api\Repository\TrackfieldsRepository;
use Pulse\Api\Repository\TreatmentEpisodesRepository;
use Pulse\Api\Repository\TreatmentsWithNormsRepository;
use Zend\Log\Logger;

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
            'dependencies'  => $this->getDependencies(),
            //'templates'   => $this->getTemplates(),
            'routes'        => $this->getRoutes(),
            'log'           => $this->getLoggers(),
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

                AllRespondentTrackRestController::class => ReflectionFactory::class,
                RespondentRestController::class => ReflectionFactory::class,
                RespondentBulkRestController::class => ReflectionFactory::class,
                AppointmentRestController::class => ReflectionFactory::class,
                EmmaRespondentTokensController::class => ReflectionFactory::class,
                EmmaSurveyQuestionsRestController::class => ReflectionFactory::class,
                EmmaTokenAnswersRestController::class => ReflectionFactory::class,

                ActivityMatcher::class => ReflectionFactory::class,

                OrganizationRepository::class => ReflectionFactory::class,
                RespondentRepository::class => ReflectionFactory::class,
                AgendaDiagnosisRepository::class => ReflectionFactory::class,
                AppointmentRepository::class => ReflectionFactory::class,

                TokenController::class => ReflectionFactory::class,

                EnvTestController::class => ReflectionFactory::class,

                InsertTrackTokenController::class => ReflectionFactory::class,
                CorrectTokenController::class => ReflectionFactory::class,

                PermissionGeneratorController::class => ReflectionFactory::class,
            ]
        ];
    }

    public function getLoggers()
    {
        return [
            'EmmaImportLogger' => [
                'writers' => [
                    'stream' => [
                        'name' => 'stream',
                        'priority' => Logger::DEBUG,
                        'options' => [
                            'stream' => GEMS_ROOT_DIR . '/data/logs/emma-import.log',
                        ]
                    ]
                ]
            ],
            'errorLogger' => [
                'writers' => [
                    'stream' => [
                        'name' => 'stream',
                        'priority' => Logger::DEBUG,
                        'options' => [
                            'stream' => GEMS_ROOT_DIR . '/data/logs/api-error.log',
                        ]
                    ]
                ]
            ]
        ];
    }

    public function getRestModels()
    {
        return [
            'emma/appointments' => [
                'model' => 'Model_AppointmentModel',
                'methods' => ['GET', 'POST', 'PATCH'],
                'customAction' => AppointmentRestController::class,
                'idField' => 'gap_id_in_source',

            ],
            'emma/respondents' => [
                'model' => 'Model_RespondentModel',
                'methods' => ['GET', 'POST', 'PATCH'],
                'customAction' => RespondentBulkRestController::class,
                'idField' => [
                    'gr2o_patient_nr',
                    'gr2o_id_organization',
                ],
                'idFieldRegex' => [
                    '[0-9]{6}-A[0-9]{3}',
                    '\d+',
                ],
            ],
            'emma/tokens' => [
                'model' => 'Tracker_Model_StandardTokenModel',
                'methods' => ['GET'],
                'idField' => 'gr2o_patient_nr',
                'allowed_fields' => [
                    'patient_nr',
                    'organization',
                    'token',
                    'survey_id',
                    'survey_name',
                    'track_name',
                    'round_description',
                    'valid_from',
                    'valid_until',
                    'completion_time',
                    'reception_code',
                    'status_ok',
                ],
                'customAction' => EmmaRespondentTokensController::class,
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
                    '[A-Za-z0-9\-]+',
                    '\d+',
                ],
            ],
            'organizations' => [
                'model' => 'Model\\OrganizationModel',
                'methods' => ['GET', 'POST', 'PATCH', 'DELETE'],
                'allowed_fields' => [
                    'gor_id_organization',
                    'gor_name',
                    'gor_code',
                    'gor_style',
                ],
                /*'disallowed_fields' => [
                    'gor_user_class'
                ],
                'readonly_fields' => [
                    'gor_name',
                ],*/
                'organizationId' => 'gor_id_organization',
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
                'methods' => ['GET', 'PATCH'],
                'customAction' => TokenController::class,
                'idFieldRegex' => '[a-zA-Z0-9]{4}-[a-zA-Z0-9]{4}',
            ],
            'all-respondent-tracks' => [
                'model' => RespondentTrackModel::class,
                'methods' => ['GET', 'PATCH', 'POST'],
                'idField' => 'gr2t_id_respondent_track',
                'customAction' => AllRespondentTrackRestController::class,
                'organizationId' => 'gr2t_id_organization',
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
            [
                'name' => 'insert-track-token',
                'path' => '/insert-track-token',
                'middleware' => $this->getCustomActionMiddleware(InsertTrackTokenController::class),
                'allowed_methods' => ['POST'],
            ],
            [
                'name' => 'correct-token',
                'path' => '/correct-token/[{id:[a-zA-Z0-9]{4}-[a-zA-Z0-9]{4}}]',
                'middleware' => $this->getCustomActionMiddleware(CorrectTokenController::class),
                'allowed_methods' => ['PATCH'],
            ],

            [
                'name' => 'permission-generator',
                'path' => '/permission-generator',
                'middleware' => $this->getCustomActionMiddleware(PermissionGeneratorController::class),
                'allowed_methods' => ['GET'],
            ],
            [
                'name' => 'emma/survey-questions',
                'path' => '/emma/survey-questions/[{id:\d+}]',
                'middleware' => $this->getCustomActionMiddleware(EmmaSurveyQuestionsRestController::class),
                'allowed_methods' => ['GET'],
            ],
            [
                'name' => 'emma/token-answers',
                'path' => '/emma/token-answers/[{id:[a-zA-Z0-9-_]+}]',
                'middleware' => $this->getCustomActionMiddleware(EmmaTokenAnswersRestController::class),
                'allowed_methods' => ['GET'],
            ],
            [
                'name' => 'match-activities',
                'path' => '/match-activities',
                'middleware' => $this->getCustomActionMiddleware(ActivityMatcher::class),
                'allowed_methods' => ['POST'],
            ],
            [
                'name' => 'env-test',
                'path' => '/env-test',
                'middleware' => EnvTestController::class,
                'allowed_methods' => ['GET'],
            ]
        ];


        return array_merge($routes, $newRoutes);
    }
}