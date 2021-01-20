<?php

namespace Pulse\Api\Fhir;


class ConfigProvider extends \Gems\Rest\Fhir\ConfigProvider
{
    public function getRestModels()
    {
        $restModels = parent::getRestModels();

        $restModels['fhir/appointment'] = [
            'model' => AppointmentModel::class,
            'methods' => ['GET'],
            'allowed_fields' => [
                'resourceType',
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
                'info',
            ],
            'idField' => 'id',
        ];

        return $restModels;
    }
}
