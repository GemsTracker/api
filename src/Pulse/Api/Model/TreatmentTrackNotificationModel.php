<?php

namespace Pulse\Api\Model;

use Gems\Rest\Db\ResultFetcher;
use Gems\Rest\Fhir\Model\Transformer\IntTransformer;
use Psr\Cache\CacheItemPoolInterface;
use Pulse\Api\Model\Transformer\DigitalClinicAccountTransformer;
use Pulse\Api\Model\Transformer\OkAppointmentsFilterTransformer;
use Pulse\Api\Model\Transformer\RangeTransformer;
use Pulse\Api\Model\Transformer\TreatmentTrackDiagnosisInfoTransformer;
use Pulse\Api\Model\Transformer\TreatmentTrackFilterTransformer;
use Pulse\Api\Model\Transformer\TreatmentTrackSedationInfoTransformer;
use Pulse\Api\Model\Transformer\TreatmentTrackTreatmentInfoTransformer;
use Zalt\Loader\ProjectOverloader;

class TreatmentTrackNotificationModel extends AppointmentNotificationModel
{
    /**
     * @var ProjectOverloader
     */
    protected $overLoader;

    public function __construct()
    {
        parent::__construct();
        $this->addLeftTable('gems__respondent2track2appointment', ['gr2t2a_id_appointment' => 'gap_id_appointment']);
        $this->addLeftTable('gems__track_appointments', [
            'gr2t2a_id_app_field' => 'gtap_id_app_field',
            'gtap_field_code' => new \Zend_Db_Expr('\'treatmentAppointment\''),
        ], 'gr2t2a', false);

        $this->addLeftTable('gems__respondent2track', [
            'gr2t_id_user' => 'gap_id_user',
            'gr2t_id_organization' => 'gap_id_organization',
            'gr2t2a_id_respondent_track' => 'gr2t_id_respondent_track',
        ]);
    }

    public function afterRegistry()
    {
        parent::afterRegistry();
        $cache = $this->overLoader->getServiceManager()->get(CacheItemPoolInterface::class);
        $resultFetcher = $this->overLoader->getServiceManager()->get(ResultFetcher::class);

        $this->addTransformer(new TreatmentTrackFilterTransformer());
        $this->addTransformer(new OkAppointmentsFilterTransformer($cache, $resultFetcher));
        $this->addTransformer(new TreatmentTrackSedationInfoTransformer());
        $this->addTransformer(new TreatmentTrackTreatmentInfoTransformer());
        $this->addTransformer(new TreatmentTrackDiagnosisInfoTransformer());

    }
}