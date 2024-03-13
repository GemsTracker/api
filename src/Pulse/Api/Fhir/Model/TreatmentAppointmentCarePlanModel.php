<?php

namespace Pulse\Api\Fhir\Model;

use Ichom\Repository\Diagnosis2TreatmentRepository;
use Pulse\Api\Fhir\Model\Transformer\TreatmentAppointmentCarePlanTransformer;
use Zalt\Loader\ProjectOverloader;

class TreatmentAppointmentCarePlanModel extends CarePlanModel
{
    /**
     * @var Diagnosis2TreatmentRepository
     */
    protected $diagnosisRepository;

    /**
     * @var \Zend_Db_Adapter_Abstract
     */
    protected $db;

    protected $loadedData = [];

    /**
     * @var \Pulse_Loader
     */
    protected $loader;

    /**
     * @var ProjectOverloader
     */
    protected $overLoader;

    protected function addTransformers()
    {
        parent::addTransformers();

        $dbLookup = $this->loader->getUtil()->getDbLookup();
        $agenda = $this->loader->getAgenda();
        $this->loader->

        $this->addTransformer(new TreatmentAppointmentCarePlanTransformer(
            $this->db,
            $dbLookup,
            $agenda,
            $this->getDiagnosisRepository(),
        ));
    }

    protected function getDiagnosisRepository()
    {
        if (!$this->diagnosisRepository) {
            $this->diagnosisRepository = $this->overLoader->create('Repository\\Diagnosis2TreatmentRepository');
        }
        return $this->diagnosisRepository;
    }

    public function load($filter = true, $sort = true, $refresh = false)
    {
        $filterHash = md5(serialize($filter) . serialize($sort));

        if ($refresh || !isset($this->loadedData[$filterHash])) {
            $this->loadedData[$filterHash] = parent::load($filter, $sort);
        }

        return $this->loadedData[$filterHash];
    }
}