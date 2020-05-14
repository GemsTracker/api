<?php


namespace Gems\Rest\Fhir;


use Gems\Rest\Fhir\Model\AppointmentModel;
use Gems\Rest\Fhir\Model\EpisodeOfCareModel;
use Gems\Rest\Fhir\Model\LocationModel;
use Gems\Rest\Fhir\Model\OrganizationModel;
use Gems\Rest\Fhir\Model\PatientModel;
use Gems\Rest\Fhir\Model\PractitionerModel;
use Gems\Rest\Fhir\Model\ServiceTypeModel;
use Gems\Rest\Fhir\Model\TreatmentModel;
use Gems\Rest\RestModelConfigProviderAbstract;

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
            'routes'       => $this->getRoutes(),
        ];
    }

    public function getDependencies()
    {
        return [];
    }

    public function getRestModels()
    {
        return [
            'fhir/patient' => [
                'model' => PatientModel::class,
                'methods' => ['GET'],
                'allowed_fields' => [
                    'id',
                    'active',
                    'gender',
                    'birthDate',
                    'name',
                    'telecom',
                    'managingOrganization',
                    'created',
                    'changed',
                ],
                'idField' => 'id',
                'idFieldRegex' => '[A-Za-z0-9\-@]+',
            ],
            'fhir/appointment' => [
                'model' => AppointmentModel::class,
                'methods' => ['GET'],
                'allowed_fields' => [
                    'id',
                    'status',
                    'start',
                    'end',
                    'created',
                    'comment',
                    'description',
                    'serviceType',
                    'participant',
                    'created',
                    'changed',
                ],
                'idField' => 'id',
            ],
            'fhir/treatment' => [
                'model' => TreatmentModel::class,
                'methods' => ['GET'],
                'allowed_fields' => [
                    'id',
                    'subject',
                    'title',
                    'created',
                    'status',
                ],
            ],
            'fhir/episode-of-care' => [
                'model' => EpisodeOfCareModel::class,
                'methods' => ['GET'],
                'allowed_fields' => [
                    'id',
                    'status',
                    'start',
                    'end',
                    'created',
                    'comment',
                    'description',
                    'serviceType',
                    'participant',
                ],
                'idField' => 'id',
            ],
            'fhir/location' => [
                'model' => LocationModel::class,
                'methods' => ['GET'],
                'allowed_fields' => [
                    'id',
                    'status',
                    'name',
                    'telecom',
                    'address',
                ],
            ],
            'fhir/practitioner' => [
                'model' => PractitionerModel::class,
                'methods' => ['GET'],
                'allowed_fields' => [
                    'id',
                    'active',
                    'name',
                    'gender',
                    'telecom',
                ],
            ],
            'fhir/organization' => [
                'model' => OrganizationModel::class,
                'methods' => ['GET'],
                'allowed_fields' => [
                    'id',
                    'active',
                    'name',
                    'code',
                    'telecom',
                    'contact',
                ],
            ],
            'fhir/codesystem/service-type' => [
                'model' => ServiceTypeModel::class,
                'methods' => ['GET'],
                'allowed_fields' => [
                    'code',
                    'display',
                    'name',
                    'telecom',
                    'contact',
                ],
                'idField' => 'code',
            ],
        ];
    }

    public function getRoutes($includeModelRoutes = true)
    {
        $modelRoutes = parent::getRoutes($includeModelRoutes);

        $routes = [];
        /*    [
                'name' => 'api.prediction/chart-definitions',
                'path' => '/prediction/chart-definitions',
                'middleware' => $this->getCustomActionMiddleware(ChartsDefinitionsAction::class),
                'allowed_methods' => ['GET'],
            ],
        ];*/

        return array_merge($routes, $modelRoutes);


    }

}
