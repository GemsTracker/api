<?php


namespace Pulse\Api;


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
            //'dependencies' => $this->getDependencies(),
            //'templates'    => $this->getTemplates(),
            'routes'       => $this->getRoutes(),
        ];
    }

    public function getRestModels()
    {
        return [
            'organizations2' => [
                'model' => 'Model\\OrganizationModel',
                'methods' => ['GET', 'POST', 'PATCH', 'DELETE'],
            ],
            'tracks' => [
                'model' => 'Tracker_Model_TrackModel',
                'methods' => ['GET'],
                'hasMany' => ['rounds' => 'rounds'],
            ],
            'rounds' => [
                'model' => 'Tracker\\Model\\RoundModel',
                'methods' => ['GET'],
            ],
            /*'surveys' => [
                'model' => 'Tracker_SurveyModel',
                'methods' => ['GET'],
            ],*/
            'tokens' => [
                'model' => 'Tracker_Model_StandardTokenModel',
                'methods' => ['GET'],
            ],
        ];
    }
}