<?php

namespace Pulse\Api\Model;

use Zalt\Loader\ProjectOverloader;

class OkAppointmentModel extends \MUtil_Model_UnionModel
{
    /**
     * @var ProjectOverloader
     */
    protected $overLoader;

    public function __construct()
    {
        parent::__construct('okAppointmentModel');
    }

    public function afterRegistry()
    {
        /**
         * @var TreatmentAppointmentNotificationModel $treatmentAppointmentModel
         */
        $treatmentAppointmentModel = $this->overLoader->create(TreatmentAppointmentNotificationModel::class);
        /**
         * @var TreatmentTrackNotificationModel $treatmentTrackModel
         */
        $treatmentTrackModel = $this->overLoader->create(TreatmentTrackNotificationModel::class);
        $this->addUnionModel($treatmentAppointmentModel, null, 'treatmentAppointmentNotificationModel');
        $this->addUnionModel($treatmentTrackModel, null, 'treatmentTrackNotificationModel');
    }
}