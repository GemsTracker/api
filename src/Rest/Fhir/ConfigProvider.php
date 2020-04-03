<?php


namespace Gems\Rest\Fhir;


use Gems\Rest\Fhir\Model\PatientModel;
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
            'fhir/patients' => [
                'model' => PatientModel::class,
                'methods' => ['GET'],
                'allowed_fields' => [
                    'identifier',
                    'active',
                    'gender',
                    'birthDate',
                    'name',
                    'telecom',
                ],
                'idField' => 'identifier',
                'idFieldRegex' => '[A-Za-z0-9\-]+',
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
