<?php

namespace Ichom;

use Gems\Rest\Factory\ReflectionFactory;
use Gems\Rest\RestModelConfigProviderAbstract;
use Ichom\Action\DiagnosisWizardController;
use Ichom\Action\DiagnosisWizardStructureController;
use Ichom\Model\Diagnosis2TrackModel;
use Ichom\Model\DiagnosisTransformedModel;
use Ichom\Model\MedicalCategoryTransformedModel;
use Ichom\Model\TreatmentModel;
use Ichom\Model\TreatmentTransformedModel;
use Ichom\Repository\Diagnosis2TreatmentRepository;

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
        return [
            'factories'  => [
                Diagnosis2TreatmentRepository::class => ReflectionFactory::class,
                DiagnosisWizardStructureController::class => ReflectionFactory::class,
                DiagnosisWizardController::class => ReflectionFactory::class,
            ]
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
                    'trackId',
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
            'respondent-diagnosis-tracks' => [
                'model' => DiagnosisTracksModel::class,
                'methods' => ['GET'],
                'applySettings' => [
                    'applyBrowseSettings',
                    'applyDiagnosisSort',
                ],
                'allowed_fields' => [
                    'id',
                    'track',
                    'trackName',
                    'trackInfo',
                    'startDate',
                    'patientNr',
                    'organizationId',
                    'success',
                    'primaryTrack',
                    'diagnosisName',
                    'treatmentName',
                    'trackStartDate',
                    'patientFullName',
                ],
            ],
        ];
    }

    public function getRoutes($includeModelRoutes=true)
    {
        $modelRoutes = parent::getRoutes($includeModelRoutes);

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
