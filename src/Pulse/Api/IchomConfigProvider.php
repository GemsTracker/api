<?php

namespace Pulse\Api;

use Gems\Rest\Factory\ReflectionFactory;
use Gems\Rest\RestModelConfigProviderAbstract;
use Ichom\Action\DiagnosisWizardController;
use Ichom\ConfigProvider;
use Pulse\Api\Action\DiagnosisWizardStructureController;
use Pulse\Model\MedicalCategoryModel;

class IchomConfigProvider extends ConfigProvider
{
    public function getDependencies()
    {
        $dependencies = parent::getDependencies();
        $dependencies['factories'][DiagnosisWizardStructureController::class] = ReflectionFactory::class;
        return $dependencies;
    }

    public function getRestModels()
    {
        $restModels = parent::getRestModels();
        $restModels['ichom/medical-category']['model'] = MedicalCategoryModel::class;
        array_push($restModels['ichom/medical-category']['allowed_fields'],
            'organizations',
            'noteTemplate',

        );
        return $restModels;
    }

    public function getRoutes($includeModelRoutes=true)
    {
        $modelRoutes = RestModelConfigProviderAbstract::getRoutes($includeModelRoutes);

        $newRoutes = [
            [
                'name' => 'diagnosis-wizard-structures',
                'path' => '/diagnosis-wizard-structures',
                'middleware' => $this->getCustomActionMiddleware(DiagnosisWizardStructureController::class),
                'allowed_methods' => ['GET', 'OPTIONS'],
            ],
            [
                'name' => 'diagnosis-wizard',
                'path' => '/diagnosis-wizard',
                'middleware' => $this->getCustomActionMiddleware(DiagnosisWizardController::class),
                'allowed_methods' => ['POST', 'OPTIONS'],
            ]
        ];


        return array_merge($modelRoutes, $newRoutes);
    }
}