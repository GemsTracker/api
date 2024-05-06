<?php


namespace Pulse\Api;

use Gems\DataSetMapper\Repository\DataSetRepository;
use Gems\Rest\Factory\ProjectOverloaderFactory;
use Gems\Rest\Factory\ReflectionFactory;
use Gems\Rest\Log\Formatter\SimpleMulti;

use Gems\Rest\Repository\SurveyQuestionsRepository;
use Gems\Rest\RestModelConfigProviderAbstract;
use Pulse\Api\Action\ActivityLogAction;
use Pulse\Api\Action\ActivityMatcher;
use Pulse\Api\Action\AnesthesiaCheckHandler;
use Pulse\Api\Action\AppointmentRestController;
use Pulse\Api\Action\ChartsController;
use Pulse\Api\Action\CorrectTokenController;
use Pulse\Api\Action\CurrentIntakeController;
use Pulse\Api\Action\EmmaRespondentTokensController;
use Pulse\Api\Action\EmmaSurveyQuestionsRestController;
use Pulse\Api\Action\EmmaSurveysRestController;
use Pulse\Api\Action\EmmaTokenAnswersRestController;
use Pulse\Api\Action\EnvTestController;
use Pulse\Api\Action\FhirAppointmentWithIntramedSynchHandler;
use Pulse\Api\Action\InsertTrackTokenController;
use Pulse\Api\Action\LastAnsweredTokenController;
use Pulse\Api\Action\OtherPatientNumbersController;
use Pulse\Api\Action\PatientNumberPerOrganizationController;
use Pulse\Api\Action\PermissionGeneratorController;
use Pulse\Api\Action\PreviewDossierTemplateController;
use Pulse\Api\Action\RefreshIntramedController;
use Pulse\Api\Action\RespondentBulkRestController;
use Pulse\Api\Action\RespondentDossierTemplatePreviewController;
use Pulse\Api\Action\RespondentRestController;
use Pulse\Api\Action\RespondentTrackfieldsRestController;
use Pulse\Api\Action\RespondentTrackRestController;
use Pulse\Api\Action\SurveyQuestionsRestController;
use Pulse\Api\Action\TokenAnswersRestController;
use Pulse\Api\Action\TokenController;
use Pulse\Api\Action\TrackfieldsRestController;
use Pulse\Api\Action\TreatmentEpisodesRestController;
use Pulse\Api\Action\TreatmentsWithNormsController;
use Pulse\Api\Fhir\Repository\AppointmentMedicalCategoryRepository;
use Pulse\Api\Model\ActivityDiagnosisModel;
use Pulse\Api\Model\ActivityLogModel;
use Pulse\Api\Model\AgendaActivityModel;
use Pulse\Api\Model\AppointmentNotificationModel;
use Pulse\Api\Model\DossierTemplatesModel;
use Pulse\Api\Model\Emma\AgendaDiagnosisRepository;
use Pulse\Api\Model\Emma\AppointmentRepository;
use Pulse\Api\Model\Emma\OrganizationRepository;
use Pulse\Api\Model\Emma\RespondentRepository;
use Pulse\Api\Model\OutcomeVariableModel;
use Pulse\Api\Model\RespondentDossierTemplatesModel;
use Pulse\Api\Model\RespondentModel;
use Pulse\Api\Model\RespondentTrackModel;
use Pulse\Api\Model\TokenAnswerModel;
use Pulse\Api\Repository\ActivityActionRepository;
use Pulse\Api\Repository\ChartRepository;
use Pulse\Api\Repository\IntakeAnesthesiaCheckRepository;
use Pulse\Api\Repository\IntramedSyncRepository;
use Pulse\Api\Repository\RequestRepository;
use Pulse\Api\Repository\SelectTranslator;
use Pulse\Api\Repository\RespondentResults;
use Pulse\Api\Repository\RespondentTrackfieldsRepository;
use Pulse\Api\Repository\TokenAnswerRepository;
use Pulse\Api\Repository\TokenRepository;
use Pulse\Api\Repository\TrackfieldsRepository;
use Pulse\Api\Repository\TreatmentEpisodesRepository;
use Pulse\Api\Repository\TreatmentsWithNormsRepository;
use Laminas\Log\Logger;
use Pulse\Model\AppointmentTokenModel;
use Pulse\Model\RespondentDossierNoteModel;
use Pulse\Model\SmartTagModel;
use Pulse\Model\TokenAnswerLogModel;
use Pulse\Tracker\DossierTemplateRepository;

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
            'acl-groups'    => $this->getAclGroups(),
            'dependencies'  => $this->getDependencies(),
            //'templates'   => $this->getTemplates(),
            'routes'        => $this->getRoutes(),
            'log'           => $this->getLoggers(),
        ];
    }

    /**
     * Return the acl group config in which route access groups can be made
     *
     * @return array
     */
    public function getAclGroups()
    {
        $aclGroupsConfig = include(__DIR__ . '/Acl/AclGroupsConfig.php');
        return $aclGroupsConfig;
    }

    public function getDependencies()
    {
        return [
            'factories'  => [
                CurrentIntakeController::class => ReflectionFactory::class,

                SurveyQuestionsRestController::class => ReflectionFactory::class,

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

                DataSetRepository::class => ReflectionFactory::class,
                RespondentDossierTemplatePreviewController::class => ReflectionFactory::class,

                RespondentTrackRestController::class => ReflectionFactory::class,
                RespondentRestController::class => ReflectionFactory::class,
                RespondentBulkRestController::class => ReflectionFactory::class,
                AppointmentRestController::class => ReflectionFactory::class,
                EmmaRespondentTokensController::class => ReflectionFactory::class,
                EmmaSurveyQuestionsRestController::class => ReflectionFactory::class,
                EmmaTokenAnswersRestController::class => ReflectionFactory::class,
                EmmaSurveysRestController::class => ReflectionFactory::class,
                PatientNumberPerOrganizationController::class => ReflectionFactory::class,
                PreviewDossierTemplateController::class => ReflectionFactory::class,
                OtherPatientNumbersController::class => ReflectionFactory::class,
                AnesthesiaCheckHandler::class => ReflectionFactory::class,

                ActivityMatcher::class => ReflectionFactory::class,

                AgendaDiagnosisRepository::class => ReflectionFactory::class,
                AppointmentRepository::class => ReflectionFactory::class,
                IntakeAnesthesiaCheckRepository::class => ReflectionFactory::class,
                OrganizationRepository::class => ReflectionFactory::class,
                RespondentRepository::class => ReflectionFactory::class,
                SurveyQuestionsRepository::class => ReflectionFactory::class,
                DossierTemplateRepository::class => ProjectOverloaderFactory::class,

                TokenRepository::class => ReflectionFactory::class,
                SelectTranslator::class => ReflectionFactory::class,

                TokenController::class => ReflectionFactory::class,
                LastAnsweredTokenController::class => ReflectionFactory::class,

                EnvTestController::class => ReflectionFactory::class,

                InsertTrackTokenController::class => ReflectionFactory::class,
                CorrectTokenController::class => ReflectionFactory::class,

                RefreshIntramedController::class => ReflectionFactory::class,

                PermissionGeneratorController::class => ReflectionFactory::class,

                \Pulse\Api\Repository\RespondentRepository::class => ReflectionFactory::class,

                ActivityLogAction::class => ReflectionFactory::class,
                ActivityLogModel::class => ReflectionFactory::class,
                ActivityActionRepository::class => ReflectionFactory::class,
                RequestRepository::class => ReflectionFactory::class,

                IntramedSyncRepository::class => ReflectionFactory::class,
                FhirAppointmentWithIntramedSynchHandler::class => ReflectionFactory::class,

                AppointmentMedicalCategoryRepository::class => ReflectionFactory::class,
            ]
        ];
    }

    public function getLoggers()
    {
        return [
            'logDir' => GEMS_LOG_DIR,
            'EmmaImportLogger' => [
                'writers' => [
                    'stream' => [
                        'name' => 'stream',
                        'priority' => Logger::DEBUG,
                        'options' => [
                            'formatter' => [
                                'name' => SimpleMulti::class,
                            ],
                            'stream' => GEMS_LOG_DIR . '/emma-import.log',

                        ],
                    ],
                ],
            ],
            'EmmaRespondentErrorLogger' => [
                'writers' => [
                    'stream' => [
                        'name' => 'stream',
                        'priority' => Logger::DEBUG,
                        'options' => [
                            'formatter' => [
                                'name' => SimpleMulti::class,
                            ],
                            'stream' => GEMS_LOG_DIR . '/emma-respondent-error.log',
                        ],
                    ],
                ],
            ],
            'errorLogger' => [
                'writers' => [
                    'stream' => [
                        'name' => 'stream',
                        'priority' => Logger::DEBUG,
                        'options' => [
                            'stream' => GEMS_LOG_DIR . '/api-error.log',
                        ],
                    ],
                ],
            ],
        ];
    }

    public function getRestModels()
    {
        return [
            /*'emma/appointments' => [
                'model' => 'Model_AppointmentModel',
                'methods' => ['GET', 'POST', 'PATCH'],
                'customAction' => AppointmentRestController::class,
                'idField' => 'gap_id_in_source',

            ],*/
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
                'applySettings' => [
                    'applyEditSettings',
                ],
            ],
            'emma/tokens' => [
                'model' => 'Tracker_Model_StandardTokenModel',
                'methods' => ['GET'],
                'idField' => 'gr2o_patient_nr',
                'idFieldRegex' => '[0-9]{4,9}',
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
                'applySettings' => [
                    'applyFormatting',
                ],
            ],
            'respondents' => [
                'model' => RespondentModel::class,
                'methods' => ['GET', 'POST', 'PATCH'],
                'applySettings' => [
                    'applyEditSettings',
                ],
                'allowed_fields' => [
                    'gr2o_patient_nr',
                    'gr2o_id_organization',
                    'grs_first_name',
                    'grs_initials_name',
                    'grs_surname_prefix',
                    'grs_last_name',
                    'grs_gender',
                    'grs_birthday',
                    'gr2o_location',
                    'gr2o_consent',
                    'gr2o_mailable',
                    'gr2o_opened',
                    'gr2o_opened_by',
                    'gr2o_changed',
                    'gr2o_changed_by',
                    'gr2o_created',
                    'gr2o_created_by',
                    'gr2o_highrisk_token',
                ],
                'allowed_save_fields' => [
                    'gr2o_patient_nr',
                    'gr2o_id_organization',
                    'grs_id_user',
                    'gr2o_id_user',
                    'grs_first_name',
                    'grs_initials_name',
                    'grs_surname_prefix',
                    'grs_last_name',
                    'grs_gender',
                    'grs_birthday',
                    'gr2o_location',
                    'gr2o_consent',
                    'gr2o_mailable',
                    'gr2o_opened',
                    'gr2o_opened_by',
                    'gr2o_changed',
                    'gr2o_changed_by',
                    'gr2o_created',
                    'gr2o_created_by',
                    'gr2o_highrisk_token',
                    'old_gr2o_consent',
                ],
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
                'organizationIdField' => 'gor_id_organization',
            ],
            'patient-numbers' => [
                'model' => 'Model_RespondentModel',
                'methods' => ['GET'],
                'customAction' => PatientNumberPerOrganizationController::class,
                'applySettings' => [
                    'applyBrowseSettings',
                ],
                'idField' => [
                    'gr2o_patient_nr',
                    'gr2o_id_organization',
                ],
                'idFieldRegex' => [
                    '[A-Za-z0-9\-]+',
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
                'organizationIdField' => 'gtr_organizations',
                'allowed_fields' => [
                    'gtr_id_track',
                    'gtr_track_name',
                    'gtr_organizations',
                ],
                'applySettings' => [
                    'applyFormatting',
                ],
            ],
            /*'rounds' => [
                'model' => 'Tracker\\Model\\RoundModel',
                'methods' => ['GET'],
            ],*/
            'surveys' => [
                'model' => 'Model\\SimpleSurveyModel',
                'methods' => ['GET'],
                'multiOranizationField' => [
                    'field' => 'gsu_insert_organizations',
                    'separator' => '|',
                ],
                'allowed_fields' => [
                    'gsu_id_survey',
                    'gsu_survey_name',
                    'ggp_staff_members',
                ],
            ],
            'tokens/last-answered' => [
                'model' => 'Tracker_Model_StandardTokenModel',
                'methods' => ['GET'],
                'customAction' => LastAnsweredTokenController::class,
                'allowed_fields' => [
                    'gto_id_token',
                    'gto_id_survey',
                    'gto_id_respondent_track',
                    'gto_round_description',
                    'gto_round_order',
                    'gto_id_relationfield',
                    'gto_completion_time',
                    'gto_valid_from',
                    'gto_valid_until',

                    'gr2o_patient_nr',
                    'gr2o_id_organization',

                    'gsu_survey_name',
                    'gsu_code',
                    'gsu_result_field',

                    'ggp_staff_members',

                    'grc_success',
                ],
            ],
            'tokens' => [
                'model' => 'Tracker_Model_StandardTokenModel',
                'methods' => ['GET', 'PATCH'],
                'customAction' => TokenController::class,
                'idFieldRegex' => '[a-zA-Z0-9]{4}-[a-zA-Z0-9]{4}',
                'allowed_fields' => [
                    'gto_id_token',
                    'gto_id_survey',
                    'gto_id_respondent_track',
                    'gto_round_description',
                    'gto_round_order',
                    'gto_completion_time',
                    'gto_valid_from',
                    'gto_valid_until',

                    'gsu_survey_name',
                    'gsu_code',
                    'gsu_result_field',

                    'ggp_staff_members',
                ],
                'allowed_save_fields' => [
                    'gto_id_token',
                    'gto_id_survey',
                    'gto_round_description',
                    'gto_completion_time',
                    'gto_valid_from',
                    'gto_valid_from_manual',
                    'gto_valid_until',
                    'gto_valid_until_manual',
                    'gto_id_respondent_track',
                    'gto_id_round',
                    'gto_id_respondent',
                    'gto_id_organization',
                    'gto_id_track',

                    'gsu_survey_name',
                    'gsu_code',
                    'gsu_result_field',

                    'ggp_staff_members',
                ],
            ],
            'respondent-tracks' => [
                'model' => RespondentTrackModel::class,
                'methods' => ['GET', 'PATCH', 'POST', 'DELETE'],
                'idField' => 'gr2t_id_respondent_track',
                'customAction' => RespondentTrackRestController::class,

                'organizationIdField' => 'gr2t_id_organization',
                'respondentIdField' => 'gr2t_id_user',
                'allowed_fields' => [
                    'gr2t_id_respondent_track',
                    'gtr_track_name',
                    'gr2t_id_organization',
                    'gr2t_start_date',
                    'gr2t_end_date',
                    'gr2o_patient_nr',
                ],
                'allowed_save_fields' => [
                    'gr2t_id_respondent_track',
                    'gtr_track_name',
                    'gr2t_id_organization',
                    'gr2t_start_date',
                    'gr2t_end_date',
                    'gr2t_id_track',
                    'gr2t_mailable',
                    'gr2o_id_organization',
                    'gr2o_patient_nr',
                ],
            ],
            'extreme-values' => [
                'model' => 'Model\\EvdModel',
                'methods' => ['GET'],
                'allowed_fields' => [
                    'pev_id_token',
                    'gto_round_description',
                ],
            ],
            'outcome-variables' => [
                'model' => OutcomeVariableModel::class,
                'methods' => ['GET'],
                'applySettings' => [
                    'applySettings',
                ],
                'allowed_fields' => [
                    'id',
                    'name',
                    'diagnosisId',
                    'treatmentId',
                    'surveyId',
                    'graph',
                    'questionCode',
                    'order',
                    'pt2o_id',
                    'pt2o_name',
                    'pt2o_id_treatment',
                    'pt2o_id_survey',
                    'pt2o_graph',
                    'pt2o_question_code',
                    'pt2o_order',
                ]
            ],
            'survey-answer-info' => [
                'model' => SurveyAnswerInfoModel::class,
                'methods' => ['GET'],
                'applySettings' => [
                    'applyBrowseSettings',
                ],
            ],
            /*'treatments-with-norms' => [
                'model' => TreatmentWithNormsModel::class,
                'methods' => ['GET'],
            ],*/
            'dossier-templates' => [
                'model' => DossierTemplatesModel::class,
                'methods' => ['GET', 'POST', 'PATCH', 'OPTIONS'],
                'applySettings' => [
                    'applyApiSettings',
                    'applyDiagnosesTreatments',
                ],
                'allowed_fields' => [
                    'id',
                    'name',
                    'dataSet',
                    'method',
                    'medicalCategory',
                    'diagnosis',
                    'treatment',
                    'active',
                    'template',
                ],
                'allowed_save_fields' => [
                    'id',
                    'name',
                    'dataSet',
                    'medicalCategory',
                    'method',
                    'diagnosis',
                    'treatment',
                    'active',
                    'template',
                    'gdot_id_dossier_template',
                    'gdot_name',
                    'gdot_id_data_set',
                    'gdot_active',
                    'gdot_template',
                ],
            ],
            'respondent-dossier-templates' => [
                'model' => RespondentDossierTemplatesModel::class,
                'methods' => ['GET'],
                'applySettings' => [
                    'applyBrowseSettings',
                    'applyDetailSettings',
                    'applyDiagnosisSort',
                ],
                'allowed_fields' => [
                    'id',
                    'trackName',
                    'trackInfo',
                    'startDate',
                    'hasTemplate',
                    'dossierTemplate',
                    'patientNr',
                    'organizationId',
                    'success',
                    'diagnosis',
                    'diagnosisName',
                    'treatment',
                    'treatmentName',
                    'trackStartDate',
                    'patientFullName',
                ],
            ],
            'agenda-activities' => [
                'model' => AgendaActivityModel::class,
                'methods' => ['GET'],
                'allowed_fields' => [
                    'id',
                    'name',
                    'organization',
                    'active',
                ],
            ],
            'activity-diagnosis' => [
                'model' => ActivityDiagnosisModel::class,
                'methods' => ['GET', 'POST', 'PATCH'],
                'allowed_fields' => [
                    'id',
                    'activity',
                    'medicalCategory',
                    'diagnosis',
                    'active',
                    'order',
                ],
                'allowed_save_fields' => [
                    'pa2d_id_activity2diagnosis',
                    'pa2d_activity',
                    'pa2d_id_diagnosis',
                    'pa2d_active',
                    'pa2d_order',
                ],
            ],
            'activity-log' => [
                'model' => ActivityLogModel::class,
                'methods' => ['POST'],
                'idFieldRegex' => '[A-Za-z0-9\-]+',
                'customAction' => ActivityLogAction::class,
            ],
            'appointment-token' => [
                'model' => AppointmentTokenModel::class,
                'methods' => ['GET'],
                'idField' => 'gap_id_appointment',
                'allowed_fields' => [
                    'id',
                    'token',
                    'tokenReceptionCode',
                    'assignedTo',
                    'tokenStatus',
                    'contactAttempts',
                    'patientNr',
                    'organizationId',
                    'gender',
                    'birthdate',
                    'respondentName',
                    'email',
                    'phone',
                    'phoneWork',
                    'phoneMobile',
                    'appointmentStart',
                    'practitioner',
                    'activity',
                    'location',
                    'surveyName',
                    'surveyId',
                    'highRiskIntakeToken',
                    'tokenCompleted',
                    'tokenCanBeAnswered',
                    'anesthesiaComment',
                ],
            ],
            'token-answer-log' => [
                'model' => TokenAnswerLogModel::class,
                'methods' => ['GET'],
                'idField' => 'plta_id_token_answer',
                'allowed_fields' => [
                    'id',
                    'token',
                    'questionCode',
                    'oldValue',
                    'newValue',
                    'created',
                    'createdBy',
                ],
            ],
            'smart-tags' => [
                'model' => SmartTagModel::class,
                'methods' => ['GET', 'POST', 'PATCH', 'DELETE'],
                'allowed_fields' => [
                    'id',
                    'from',
                    'to',
                    'medicalCategory',
                    'context',
                    'active',
                ],
                'allowed_save_fields' => [
                    'gsta_id_smart_tag',
                    'gsta_from',
                    'gsta_to',
                    'gsta_context',
                    'gsta_id_medical_category',
                    'gsta_active',
                ],
            ],
            'respondent-dossier-note' => [
                'model' => RespondentDossierNoteModel::class,
                'methods' => ['GET', 'POST', 'PATCH', 'DELETE'],
                'allowed_fields' => [
                    'id',
                    'patientNr',
                    'organizationId',
                    'note',
                    'changed',
                    'changedBy',
                ],
                'allowed_save_fields' => [
                    'grdn_id_note',
                    'grdn_patient_nr',
                    'grdn_id_organization',
                    'grdn_note',
                ],
            ],
            'questionnaire-answers' => [
                'model' => TokenAnswerModel::class,
                'methods' => ['GET', 'PATCH'],
                'idFieldRegex' => '[a-zA-Z0-9]{4}-[a-zA-Z0-9]{4}',
                'allowed_fields' => [
                    'id',
                    'status',
                    'subject',
                    'organization',
                    'answers',
                ],
                'allowed_save_fields' => [
                    'gto_id_token',
                    'gto_id_respondent_track',
                    'gto_id_round',
                    'gto_id_track',
                    'gto_id_survey',
                    'gto_changed_by',
                    'gto_created_by',
                    'answers',
                ]
            ],
            'questionnaire-start' => [
                'model' => TokenAnswerModel::class,
                'methods' => ['GET'],
                'idFieldRegex' => '[a-zA-Z0-9]{4}-[a-zA-Z0-9]{4}',
                'applySettings' => [
                    'applyInitSurveySettings',
                ],
                'allowed_fields' => [
                    'id',
                    'status',
                    'subject',
                    'organization',
                    'answers',
                ],
                'allowed_save_fields' => [
                    'gto_id_token',
                    'answers',
                ]
            ],
            'appointment-notification' => [
                'model' => AppointmentNotificationModel::class,
                'methods' => ['GET'],
                'allowed_fields' => [
                    'patientNr',
                    'appointmentId',
                    'appointmentTime',
                    'start',
                    'end',
                ],
            ],
        ];
    }

    public function getRoutes($includeModelRoutes=true)
    {
        $modelRoutes = parent::getRoutes($includeModelRoutes);

        $newRoutes = [
            [
                'name' => 'other-patient-numbers',
                'path' => '/other-patient-numbers/{patientNr:[a-zA-Z0-9-_]+}/{organizationId:\d+}',
                'middleware' => $this->getCustomActionMiddleware(OtherPatientNumbersController::class),
                'allowed_methods' => ['GET'],
            ],
            [
                'name' => 'survey-questions',
                'path' => '/survey-questions/[{id:\d+}]',
                'middleware' => $this->getCustomActionMiddleware(SurveyQuestionsRestController::class),
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
                'allowed_methods' => ['PATCH'],
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
                'name' => 'current-intake',
                'path' => '/current-intake',
                'middleware' => $this->getCustomActionMiddleware(CurrentIntakeController::class),
                'allowed_methods' => ['GET'],
            ],
            [
                'name' => 'permission-generator',
                'path' => '/permission-generator',
                'middleware' => $this->getCustomActionMiddleware(PermissionGeneratorController::class),
                'allowed_methods' => ['GET'],
            ],
            [
                'name' => 'emma/surveys',
                'path' => '/emma/surveys',
                'middleware' => $this->getCustomActionMiddleware(EmmaSurveysRestController::class),
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
                'name' => 'preview-dossier-template',
                'path' => '/preview-dossier-template',
                'middleware' => $this->getCustomActionMiddleware(PreviewDossierTemplateController::class),
                'allowed_methods' => ['GET'],
            ],
            [
                'name' => 'refresh-intramed',
                'path' => '/refresh-intramed',
                'middleware' => $this->getCustomActionMiddleware(RefreshIntramedController::class),
            ],
            [
                'name' => 'respondent-dossier-template-preview',
                'path' => '/respondent-dossier-template-preview',
                'middleware' => $this->getCustomActionMiddleware(RespondentDossierTemplatePreviewController::class),
                'allowed_methods' => ['GET', 'OPTIONS'],
            ],
            [
                'name' => 'anesthesia-check',
                'path' => '/anesthesia-check/{id:[a-zA-Z0-9-_]+}',
                'middleware' => $this->getCustomActionMiddleware(AnesthesiaCheckHandler::class),
                'allowed_methods' => ['GET', 'PATCH'],
            ],
            [
                'name' => 'env-test',
                'path' => '/env-test',
                'middleware' => EnvTestController::class,
                'allowed_methods' => ['GET'],
            ],
        ];


        return array_merge($modelRoutes, $newRoutes);
    }
}
