<?php

declare(strict_types=1);


namespace Pulse\Api\Emma\Fhir;


use Gems\Rest\Factory\ReflectionFactory;
use Gems\Rest\RestModelConfigProviderAbstract;
use Pulse\Api\Emma\Fhir\Action\AppointmentResourceAction;
use Pulse\Api\Emma\Fhir\Action\ConditionResourceAction;
use Pulse\Api\Emma\Fhir\Action\EncounterResourceAction;
use Pulse\Api\Emma\Fhir\Action\EpisodeOfCareResourceAction;
use Pulse\Api\Emma\Fhir\Action\PatientResourceAction;
use Pulse\Api\Emma\Fhir\Model\AppointmentModel;
use Pulse\Api\Emma\Fhir\Model\ConditionModel;
use Pulse\Api\Emma\Fhir\Model\EncounterModel;
use Pulse\Api\Emma\Fhir\Model\EpisodeOfCareModel;
use Pulse\Api\Emma\Fhir\Model\RespondentModel;
use Pulse\Api\Emma\Fhir\Repository\AgendaActivityRepository;
use Pulse\Api\Emma\Fhir\Repository\AgendaStaffRepository;
use Pulse\Api\Emma\Fhir\Repository\AppointmentRepository;
use Pulse\Api\Emma\Fhir\Repository\ConditionRepository;
use Pulse\Api\Emma\Fhir\Repository\CurrentUserRepository;
use Pulse\Api\Emma\Fhir\Repository\EpdRepository;
use Pulse\Api\Emma\Fhir\Repository\EpisodeOfCareRepository;
use Pulse\Api\Emma\Fhir\Repository\EscrowOrganizationRepository;
use Pulse\Api\Emma\Fhir\Repository\ImportDbLogRepository;
use Pulse\Api\Emma\Fhir\Repository\ImportEscrowLinkRepository;
use Pulse\Api\Emma\Fhir\Repository\ImportLogRepository;
use Pulse\Api\Emma\Fhir\Repository\IntakeAnaesthesiaLinkRepository;
use Pulse\Api\Emma\Fhir\Repository\OrganizationRepository;

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
            'events'        => $this->getEvents(),
            'migrations'    => $this->getMigrations(),
            'routes'        => $this->getRoutes(),
        ];
    }

    public function getDependencies()
    {
        return [
            'factories' => [
                // Actions
                AppointmentResourceAction::class => ReflectionFactory::class,
                ConditionResourceAction::class => ReflectionFactory::class,
                EncounterResourceAction::class => ReflectionFactory::class,
                EpisodeOfCareResourceAction::class => ReflectionFactory::class,
                PatientResourceAction::class => ReflectionFactory::class,

                // Model
                AppointmentModel::class => ReflectionFactory::class,
                ConditionModel::class => ReflectionFactory::class,
                EncounterModel::class => ReflectionFactory::class,
                EpisodeOfCareModel::class => ReflectionFactory::class,
                RespondentModel::class => ReflectionFactory::class,

                // Repository
                AgendaActivityRepository::class => ReflectionFactory::class,
                AgendaStaffRepository::class => ReflectionFactory::class,
                AppointmentRepository::class => ReflectionFactory::class,
                ConditionRepository::class => ReflectionFactory::class,
                CurrentUserRepository::class => ReflectionFactory::class,
                EpdRepository::class => ReflectionFactory::class,
                EpisodeOfCareRepository::class => ReflectionFactory::class,
                ImportEscrowLinkRepository::class => ReflectionFactory::class,
                ImportDbLogRepository::class => ReflectionFactory::class,
                ImportLogRepository::class => ReflectionFactory::class,
                ExistingEpdPatientRepository::class => ReflectionFactory::class,
                OrganizationRepository::class => ReflectionFactory::class,
                IntakeAnaesthesiaLinkRepository::class => ReflectionFactory::class,
                EscrowOrganizationRepository::class => ReflectionFactory::class,

                // EventSubscribers
                ModelLogEventSubscriber::class => ReflectionFactory::class,
                CheckRespondentOrganizationEventSubscriber::class => ReflectionFactory::class,
                ImportEscrowEventSubscriber::class => ReflectionFactory::class,
                AppointmentEventSubscriber::class => ReflectionFactory::class,
                RespondentEventSubscriber::class => ReflectionFactory::class,
                RespondentMergeEventSubscriber::class => ReflectionFactory::class,
            ],
        ];
    }

    public function getEvents()
    {
        return [
            ModelLogEventSubscriber::class,
            CheckRespondentOrganizationEventSubscriber::class,
            ImportEscrowEventSubscriber::class,
            AppointmentEventSubscriber::class,
            RespondentEventSubscriber::class,
            RespondentMergeEventSubscriber::class,
        ];
    }

    protected function getMigrations()
    {
        return [
            'migrations' => [
                '%%PHINX_CONFIG_DIR%%/src/Pulse/Api/Emma/Fhir/config/db/migrations',
            ],
            'seeds' => [
                '%%PHINX_CONFIG_DIR%%/src/Pulse/Api/Emma/Fhir/config/db/seeds',
            ],
        ];
    }

    /**
     * get a list of routes generated from the rest models defined in getRestModels()
     *
     * @return array
     */
    protected function getModelRoutes()
    {
        return parent::getModelRoutes();
    }


    public function getRestModels()
    {
        return [
            'emma/Patient' => [
                'model' => RespondentModel::class,
                'methods' => ['PUT', 'DELETE'],
                'idFieldRegex' => '[A-Za-z0-9\-]+',
                'customAction' => PatientResourceAction::class,
            ],
            'emma/Appointment' => [
                'model' => AppointmentModel::class,
                'methods' => ['PUT', 'DELETE'],
                'idFieldRegex' => '[A-Za-z0-9\-]+',
                'customAction' => AppointmentResourceAction::class,
            ],
            'emma/Encounter' => [
                'model' => EncounterModel::class,
                'methods' => ['PUT', 'DELETE'],
                'idFieldRegex' => '[A-Za-z0-9\-]+',
                'customAction' => EncounterResourceAction::class,
            ],
            'emma/EpisodeOfCare' => [
                'model' => EpisodeOfCareModel::class,
                'methods' => ['PUT', 'DELETE'],
                'idFieldRegex' => '[A-Za-z0-9\-]+',
                'customAction' => EpisodeOfCareResourceAction::class,
            ],
            'emma/Condition' => [
                'model' => ConditionModel::class,
                'methods' => ['PUT', 'DELETE'],
                'idFieldRegex' => '[A-Za-z0-9\-]+',
                'customAction' => ConditionResourceAction::class,
            ],
        ];
    }
}
