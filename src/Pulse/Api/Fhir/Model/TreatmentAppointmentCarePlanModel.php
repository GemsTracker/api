<?php

namespace Pulse\Api\Fhir\Model;

use Ichom\Repository\Diagnosis2TreatmentRepository;
use Pulse\Api\Fhir\Model\Transformer\CarePlanMedicalCategoryFilterTransformer;
use Pulse\Api\Fhir\Model\Transformer\TreatmentAppointmentCarePlanTransformer;
use Pulse\Api\Fhir\Repository\AppointmentMedicalCategoryRepository;
use Zalt\Loader\ProjectOverloader;

class TreatmentAppointmentCarePlanModel extends CarePlanModel
{

    /**
     * @var AppointmentMedicalCategoryRepository|null
     */
    protected $appointmentMedicalCategoryRepository = null;

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

        $this->addTransformer(new CarePlanMedicalCategoryFilterTransformer());
        $this->addTransformer(new TreatmentAppointmentCarePlanTransformer(
            $this->db,
            $dbLookup,
            $agenda,
            $this->getDiagnosisRepository(),
            $this->getAppointmentMedicalCategoryRepository()
        ));

        $this->set('medicalCategory', 'filterValue', true);
        $this->set('medical-category', 'filterValue', true);
    }

    protected function getAppointmentMedicalCategoryRepository()
    {
        if (null === $this->appointmentMedicalCategoryRepository) {
            $this->appointmentMedicalCategoryRepository = $this->overLoader->getServiceManager()->get(AppointmentMedicalCategoryRepository::class);
        
        }
        return $this->appointmentMedicalCategoryRepository;
    }

    protected function getDiagnosisRepository()
    {
        if (!$this->diagnosisRepository) {
            $this->diagnosisRepository = $this->overLoader->create('Repository\\Diagnosis2TreatmentRepository');
        }
        return $this->diagnosisRepository;
    }

    public function getItemCount($filter = true, $sort = true)
    {
        return count($this->load($filter, $sort));
    }

    public function load($filter = true, $sort = true, $refresh = false)
    {
        $filterHash = md5(serialize($filter) . serialize($sort));

        $select = $this->_createSelect($this->_checkFilterUsed($filter), $this->_checkSortUsed($sort));

        if ($refresh || !isset($this->loadedData[$filterHash])) {
            $this->loadedData[$filterHash] = parent::load($filter, $sort);
        }


        return $this->loadedData[$filterHash];
    }
}