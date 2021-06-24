<?php

namespace Gems\ReferenceData;

use Gems\ReferenceData\Action\ReferenceCollectionAction;
use Gems\ReferenceData\Model\ReferenceCollectionModelWithOrganization;
use Gems\Rest\Factory\ReflectionFactory;
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
            'dependencies'  => $this->getDependencies(),
            'routes'        => $this->getRoutes(),
        ];
    }

    /**
     * Returns the container dependencies
     *
     * @return array
     */
    public function getDependencies()
    {
        return [
            'factories'  => [
                ReferenceCollectionAction::class => ReflectionFactory::class,
            ],
        ];
    }

    public function getRestModels()
    {
        return [
            'reference-data' => [
                'model' => ReferenceCollectionModelWithOrganization::class,
                'customAction' => ReferenceCollectionAction::class,
                'methods' => ['GET'],
                'allowed_fields' => [
                    'subject',
                    'description',
                    'source',
                    'organization',
                    'data',
                    'availableFields',
                    'usedFields',
                ],
            ],
        ];
    }
}
