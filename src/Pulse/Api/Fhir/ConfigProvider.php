<?php

namespace Pulse\Api\Fhir;


use Pulse\Api\Fhir\Model\CarePlanModel;
use Pulse\Api\Fhir\Model\PrefixedCodeTreatmentModel;
use Pulse\Api\Fhir\Model\SoulveAppointmentModel;
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

        $restModels['fhir/soulve/appointment'] = [
            'model' => SoulveAppointmentModel::class,
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

        $restModels['fhir/care-plan'] = [
            'model' => CarePlanModel::class,
            'methods' => ['GET'],
            'allowed_fields' => [
                'resourceType',
                'id',
                'status',
                'staffOnly',
                'intent',
                'title',
                'code',
                'created',
                'subject',
                'period',
                'contributor',
                'supportingInfo',
                'activity',
            ],
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
                'endDate',
                'status',
                'info',
            ],
            'idField' => 'id',
            'idFieldRegex' => '[A-Za-z0-9]+',
        ];

        $restModels['fhir/prefixed-treatment'] =[
            'model' => PrefixedCodeTreatmentModel::class,
            'methods' => ['GET'],
            'allowed_fields' => [
                'resourceType',
                'id',
                'subject',
                'code',
                'title',
                'created',
                'endDate',
                'status',
                'info',
            ],
            'idField' => 'id',
            'idFieldRegex' => '[A-Za-z0-9]+',
        ];

        return $restModels;
    }
}
