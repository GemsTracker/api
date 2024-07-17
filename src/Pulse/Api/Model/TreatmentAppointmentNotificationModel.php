<?php

namespace Pulse\Api\Model;

use Gems\Rest\Db\ResultFetcher;
use Gems\Rest\Fhir\Model\Transformer\IntTransformer;
use Ichom\Repository\Diagnosis2TreatmentRepository;
use Psr\Cache\CacheItemPoolInterface;
use Pulse\Api\Model\Transformer\DigitalClinicAccountTransformer;
use Pulse\Api\Model\Transformer\OkAppointmentsFilterTransformer;
use Pulse\Api\Model\Transformer\RangeTransformer;
use Pulse\Api\Model\Transformer\TreatmentAppointmentDiagnosisInfoTransformer;
use Pulse\Api\Model\Transformer\TreatmentAppointmentFilterTransformer;
use Pulse\Api\Model\Transformer\TreatmentAppointmentSedationInfoTransformer;
use Pulse\Api\Model\Transformer\TreatmentAppointmentTreatmentInfoTransformer;
use Zalt\Loader\ProjectOverloader;

class TreatmentAppointmentNotificationModel extends AppointmentNotificationModel
{
    /**
     * @var ProjectOverloader
     */
    protected $overLoader;
    public function __construct()
    {
        parent::__construct();
        $this->addTable('gems__agenda_activities', ['gap_id_activity' => 'gaa_id_activity']);
        $this->addTable('pulse__activity2treatment', ['pa2t_activity' => 'gaa_name']);
    }

    public function afterRegistry()
    {
        parent::afterRegistry();
        $cache = $this->overLoader->getServiceManager()->get(CacheItemPoolInterface::class);
        $resultFetcher = $this->overLoader->getServiceManager()->get(ResultFetcher::class);

        $this->addTransformer(new OkAppointmentsFilterTransformer($cache, $resultFetcher));
        $this->addTransformer(new TreatmentAppointmentTreatmentInfoTransformer($this->translateAdapter));
        $this->addTransformer(new TreatmentAppointmentSedationInfoTransformer($resultFetcher));
        $this->addTransformer(new TreatmentAppointmentDiagnosisInfoTransformer());
        $this->addTransformer(new TreatmentAppointmentFilterTransformer());
        $this->addTransformer(new IntTransformer(['treatment', 'sedation']));

        $this->set('with-sedation', [
            'filterValue' => true,
        ]);
        $this->set('with-treatment', [
            'filterValue' => true,
        ]);$this->set('with-diagnosis', [
            'filterValue' => true,
        ]);
    }
}