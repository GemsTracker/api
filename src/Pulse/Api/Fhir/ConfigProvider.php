<?php

namespace Pulse\Api\Fhir;


use Pulse\Api\Fhir\Model\TemporaryAppointmentModel;
use Pulse\Api\Fhir\Model\TreatmentModel;
use Pulse\Api\Fhir\Model\AppointmentModel;

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

        $restModels['fhir/temp/appointment'] = [
            'model' => TemporaryAppointmentModel::class,
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

        $restModels['fhir/treatment'] =[
            'model' => TreatmentModel::class,
            'methods' => ['GET'],
            'allowed_fields' => [
                'resourceType',
                'id',
                'subject',
                'code',
                'title',
                'created',
                'status',
                'info',
            ],
            'idField' => 'id',
            'idFieldRegex' => '[A-Za-z0-9]+',
        ];

        return $restModels;
    }
}
