<?php

namespace Ichom;

use Gems\Rest\RestModelConfigProviderAbstract;
use Ichom\Model\Diagnosis2TrackModel;
use Ichom\Model\DiagnosisTransformedModel;
use Ichom\Model\MedicalCategoryTransformedModel;
use Ichom\Model\TreatmentModel;
use Ichom\Model\TreatmentTransformedModel;

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
            //'dependencies' => $this->getDependencies(),
            'routes'       => $this->getRoutes(),
        ];
    }

    public function getRestModels()
    {
        return [
            'ichom/diagnosis' => [
                'model' => DiagnosisTransformedModel::class,
                'methods' => ['GET'],
                'applySettings' => [
                    'applyApiSettings',
                    'applyFullManyToMany',
                ],
                'allowed_fields' => [
                    'id',
                    'name',
                    'externalName',
                    'priority',
                    'treatments',
                    'medicalCategory',
                ],
            ],
            'ichom/medical-category' => [
                'model' => MedicalCategoryTransformedModel::class,
                'methods' => ['GET'],
                'applySettings' => [
                    'applySettings',
                ],
                'allowed_fields' => [
                    'id',
                    'name',
                    'active',
                ],
            ],
            'ichom/treatment' => [
                'model' => TreatmentTransformedModel::class,
                'methods' => ['GET'],
                'applySettings' => [
                    'applyBrowseSettings',
                ],
                'allowed_fields' => [
                    'id',
                    'name',
                    'externalName',
                    'method',
                    'active',
                    'medicalCategory',
                ],
            ],
        ];
    }
}
